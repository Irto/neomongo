<?php
namespace Irto\NeoMongo\Document;

use Irto\NeoMongo\Client;
use Irto\NeoMongo\OdmCursor;
use MongoId;

Trait Model {

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

		$_criteria = $this->getUniqueIndex();
		$criteria = array_merge($_criteria, $criteria); // merge criteria with unique index

		$query = array();

		if(is_null($update)){
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
	 * Remove document from collection
	 *
	 * @return Bool
	 */
	public function delete(){
		if($this->hasUniqueIndex()){
			$data = $this->getUniqueIndex();

			return $this
					->collection()
					->remove($data);
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