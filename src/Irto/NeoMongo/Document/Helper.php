<?php
namespace Irto\NeoMongo\Document;

use Irto\NeoMongo\Client;
use MongoId;

Trait Helper {

	/**
	 * Common used attrs from database
	 *
	 * If it's not empty and on find don't use projection, 
	 * will return only this keys from document.
	 *
	 * @var Array
	 */
	protected static $attributes = array();
	
	/**
	 * Index on document
	 *
	 * Exemple: array('_id' => ['unique'], [['_id', 'slug'], 'unique'])
	 *
	 * @var Array
	 */
	protected $indexes = array();

		/**
	 * Genarate a query with best MongoDB operator to update 
	 *
	 * @param String $key
	 * @param Mixed $new
	 * @param Bool $old
	 *
	 * @return Array
	 */
	protected function makeUpdateQuery($key, $new, $old = null){
		if($key[0] == '$')
			return [
				'key' => $key,
				'value' => $new
			];
		

		if(is_null($old)){
			// if is array will push itens on $key
			if(is_array($new))
				return [
					'key' => '$push',
					'value' => [$key => ['$each' => $new]]
				];

			return [
				'key' => '$set',
				'value' => [$key => $new]];
		}

		// use $inc operator to update if $new and $old are integer
		if(is_numeric($old) && is_numeric($new))
			return [
				'key' => '$inc',
				'value' => [$key => ($new - $old) ]
			];
		
		return [
			'key' => '$set',
			'value' => [$key => $new]];
	}

	/**
	 * Return a setted unique index with respective value,
	 * if original is false, original and modified data will be verified, if is true, only original.
	 *
	 * @param Bool $original [false]
	 *
	 * @return Array
	 */
	public function getUniqueIndex($original = false){
		$_id = array('_id' => ['unique']);
		$indexes = array_merge($_id, $this->indexes);

		$index = [];
		foreach($indexes as $key => $types){
			if(!is_array($types) || !in_array('unique', $types)) continue;

			if(is_numeric($key) && is_array($types[0]))
				$keys = $types[0];
			else
				$keys = [$key];

			foreach($keys as $_key){
				if($this->hasProperty($_key, $original))
					$index[$_key] = $this->getProperty($_key, $original);
				else if ($this->hasProperty($_key, !$original) && !$original)
					$index[$_key] = $this->getProperty($_key, !$original);
				else {
					$index = [];
					continue 2;
				}
			}
		}

		// prefix keys
		$_index = [];
		foreach($index as $key => $value)
			$_index[$this->prefixProperty($key)] = $value;

		return $_index;
	}

	/**
	 * Return if has setted unique indexes
	 *
	 * @param Bool $original [false]
	 *
	 * @return Bool
	 */
	public function hasUniqueIndex($original = false){
		$indexes = $this->getUniqueIndex($original);
		if(is_array($indexes) && !empty($indexes))
			return true;

		return false;
	}

	/**
     * Prepare attributes to be used in MongoDb.
     * especially the _id.
     *
     * @param array $attr
     * @return array
     */
    protected static function prepareMongoAttributes($attr){
        // Translate the primary key field into _id
        if( isset($attr['_id']) ) {
            // If its a 24 digits hexadecimal, then it's a MongoId
            if (Client::isMongoId($attr['_id'])) {
                $attr['_id'] = new MongoId( $attr['_id'] );
            } elseif(is_numeric($attr['_id'])) {
                $attr['_id'] = (int)$attr['_id'];
            } else {
                $attr['_id'] = $attr['_id'];
            }
        }

        return $attr;
    }

	/**
	 * Prepare array to use as projection on MongoDB query
	 *
	 * @param Array $array
	 *
	 * @return Array;
	 */
	protected static function prepareProjection(Array $array){
		$result = array();

		foreach($array as $key => $value){
			if(is_numeric($key)) $result[$value] = 1;
			else $result[$key] = $value;
		}

		// If have only 0 values, will add to projection
		// this->$attributes removing existing attr on $result
		// bacause 0 is to don't return data
		if( (!in_array(1, $result) || empty($result)) && !empty(static::$attributes) )
			foreach(static::$attributes as $key)
				if(!array_key_exists($key, static::$attributes))
					$result[$key] = 1;

		// Cannot mix including and excluding fields
		if(in_array(1, $result))
			foreach($result as $key => $include)
				if(!$include) unset($result[$key]);

		return $result;
	}

	/**
	 * Parse a given array to model properties
	 *
	 * @param Array $document
	 *
	 * @return Bool
	 */
	public function parseDocument(Array $document){
		try {
			// For each attribute, feed the model object
            foreach ($document as $field => $value)
                $this->setOriginalProp($field, $value);

            // Returns success
            return true;
        } catch( Exception $e ){
            // Returns fail;
            return false;
        }

        return false;
	}

	/** 
	 * Add a prefix to $key
	 *
	 * @param String $key
	 */
	protected function prefixProperty($key){
		return $key;
	}
}
