<?php
namespace Irto\NeoMongo\Relationship;

use Irto\NeoMongo\PropertiesContainer;
use Irto\NeoMongo\Document\Helper;
use MongoCollection;

class Instance {

	/**
	 * Relationship direction constants
	 */
	CONST DIRECTION_ALL = 'all';
	CONST DIRECTION_OUT = 'out';
	CONST DIRECTION_IN 	= 'in';

	/**
	 * Constant prefix for property on document
	 */
	CONST REL_PREFIX = '__dbrel_';

	/**
	 * Load relationship capatibility and properties container
	 */
	use PropertiesContainer;
	use Helper;
	use Model {
		Model::parseDocument insteadof Helper;
		Model::prefixProperty insteadof Helper;
	}

	/**
	 * Create or update indexes, it needs to be called a least one time and when have some
	 * operation that not use this lib.
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
}