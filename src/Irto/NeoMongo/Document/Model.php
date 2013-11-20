<?php
namespace Irto\NeoMongo\Document;

use Irto\NeoMongo\Client;
use Irto\NeoMongo\OdmCursor;
use ReflectionClass;
use MongoId;

Trait Model {

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
	 * Exemple: array('_id' => ['unique'])
	 *
	 * @var Array
	 */
	protected $indexes = array();

	/**
	 * Create a new document, but don't save yet
	 *
	 * @return Self
	 */
	public static function make(){
		$instance = new static();

		if(method_exists($instance, 'setup'))
			call_user_method_array('setup', $instance, func_get_args());

		return $instance;
	}

	/**
	 * Find $criteria on document.
	 * If only one document matched return this model,
	 * or OdmCursor if more than one.
	 *
	 * @param Array $criteria
	 * @param Array $projection
	 *	
	 * @return Instanced this
	 */
	public static function find(Array $criteria, Array $projection){
		$result = static::where( $criteria, $projection );

	    if( $result->count() == 1 )
	        return $result->first();
	    else
	        return $result;

		return false;
	}

	/**
	 * Shortcut to static::first
	 */
	public static function findOne($criteria, Array $projection){
		return static::first($criteria, $projection);
	}

	/**
	 * Get first occurence of $criteria on collection
	 *
	 * @param Mixed $criteria
	 * @param Array $projection
	 *
	 * @return Instanced this
	 */
	public static function first($criteria, Array $projection = []){
		// Can use a String or MongoID class to search an document
		if($criteria instanceof MongoId || Client::isMongoId($criteria)){
			$criteria = ['_id' => $criteria];
		}

		$criteria = static::prepareMongoAttributes($criteria);

		$result = static::where( $criteria, $projection );

		if( $result->count() != 0 )
        	return $result->first();

        return null;
	}

	/**
	 * Find documents from the collection within the criteria
	 *
	 * @param Array $criteria
	 * @param Array $projection
	 *
	 * @return Bool|Irto\NeoMongo\OdmCursor
	 */
	public static function where(Array $criteria, Array $projection){
		if($criteria instanceof MongoId || Client::isMongoId($criteria)){
			$criteria = ['_id' => $criteria];
		}

		$criteria = static::prepareMongoAttributes($criteria);

		if(is_array($criteria)){
			$projection = static::prepareProjection($projection);

			$instance = new static;
			if(method_exists($instance, 'setup'))
				$instance->setup();

			$cursor = new OdmCursor(
                	static::collection()->find( $criteria, $projection ),
                	$instance
            	);

			return $cursor;
		}

		return false;
	}

	/**
	 * Insert or update document on collection
	 *
	 * @return Bool
	 * @throws Irto\NeoMongo\Exception\DuplicatedException
	 */
	public function save(){
		if($this->hasUniqueIndex())
			return $this->update();
		else
			return $this->insert();

		return false;
	}

	/**
	 * Insert properties to collection.
	 * This DONT do updates
	 *
	 * @return Bool|Array
	 */
	public function insert(){
		$properties = $this->getProperties();
		$result = $this
					->collection()
					->insert($properties);

		$this->setProperty('_id', $properties['_id']);

		if(isset($result['ok']) && $result['ok']){
			$this->toggleProperties();
			return true;
		}

		return $result;
	}

	/**
	 * Update this properties on collection,
	 * use _id to criteria, or some setted unique index (see this->$indexes)
	 * If $criteria or update is set, will use it to perform update
	 * for criteria some unique index will be automatic provided.
	 *
	 * @param Array $criteria [array())
	 * @param Array $update [null]
	 *
	 * @return Bool|Array
	 */
	public function update($criteria = [], $update = null){
		// only can update if it's has a unique index for match on update query.
		if(!$this->hasUniqueIndex()) return false;

		$_criteria = $this->getUniqueIndexes();
		$criteria = array_merge($_criteria, $criteria); // merge criteria with unique indexes

		$query = array();

		if(empty($update)){
			foreach($this->getProperties() as $key => $value){
				$_query = $this->makeUpdateQuery($key, $value, $this->getProperty($key));

				if(isset($query[$_query['key']]))
					$query[$_query['key']] = array_merge($query[$_query['key']], $_query['value']);
				else
					$query[$_query['key']] = $_query['value'];
			}
		} else // if update is setted will use it
			$query = $update;

		$result = $this
					->collection()
					->update($criteria, $query);

		if(isset($result['ok']) && $result['ok']){
			$this->toggleProperties();
			return true;
		}
		
		return $result;
	}

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
	 * Remove document from collection
	 *
	 * @return Bool
	 */
	public function delete(){
		if($this->hasUniqueIndex()){
			$data = $this->getUniqueIndexes();

			return $this
					->collection()
					->remove($data);
		}

		return false;
	}

	/**
	 * Return setted unique indexes with respective values,
	 * if original is false, original and modified data will be verified, if is true, only original.
	 *
	 * @param Bool $original [false]
	 *
	 * @return Array
	 */
	public function getUniqueIndexes($original = false){
		$_id = array('_id' => ['unique']);
		$indexes = array_merge($this->indexes, $_id);

		foreach($indexes as $key => $types){
			if(!in_array('unique', $types)) unset($indexes[$key]);

			if($this->hasProperty($key, $original))
				$indexes[$key] = $this->getProperty($key, $original);
			else if ($this->hasProperty($key, !$original) && !$original)
				$indexes[$key] = $this->getProperty($key, !$original);
			else
				unset($indexes[$key]);
		}

		return $indexes;
	}

	/**
	 * Return if has setted unique indexes
	 *
	 * @param Bool $original [false]
	 *
	 * @return Bool
	 */
	public function hasUniqueIndex($original = false){
		$indexes = $this->getUniqueIndexes($original);
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
                $attr['_id'] = new \MongoId( $attr['_id'] );
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
	 * Unset a property
	 *
	 * @param String $key
	 */
	public function unsetProperty($key){
		parent::unsetProperty($key);

		if(empty($this->original_props))
			$this->pushProperty('$unset', [$key => 1]);
	}
}