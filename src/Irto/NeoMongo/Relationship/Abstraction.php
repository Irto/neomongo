<?php
namespace Irto\NeoMongo\Relationship;

use Irto\NeoMongo\PropertiesContainer;
use Irto\NeoMongo\Document\Helper;
use Irto\NeoMongo\Collection as MongoCollection;

abstract class Abstraction {

	/**
	 * Load relationship capatibility and properties container
	 */
	use PropertiesContainer;
	use Helper;
	use Model {
		Model::prefixProperty insteadof Helper;
	}

	/**
	 * This relationship type
	 *
	 * @var String
	 */
	protected $type = null;

	/**
	 * Create or update indexes, it needs to be called a least one time and when have some
	 * operation that not use this lib. Index operation are executed in background.
	 *
	 * @param MongoCollection $collection
	 * @param String $relType
	 */
	public static function performIndexes(MongoCollection $collection, $relType){
		$relType = strtolower($relType);
		$prefix = Instance::REL_PREFIX . $relType . '.';

		$collection->ensureIndex([ '_id' => 1, $prefix . '_collection' => 1 ], ['background' => true]); // for incoming searches
		$collection->ensureIndex([ '_id' => 1, $prefix .  '_id' => 1, $prefix .  '_collection' => 1 ], ['unique' => true, 'background' => true]); // for ensure integrity
	}

	/**
	 *
	 *
	 */
	public static function getCollections(){
		return static::$collections;
	}

	/**
	 * Set type of relationship
	 *
	 * @param String $type
	 */
	public function setType($type){
		$this->type = $type;
	}

	/**
	 * Return type of relationship
	 *
	 * @return String
	 */
	public function getType(){
		return strtolower($this->type);
	}

}