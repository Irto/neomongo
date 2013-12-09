<?php
namespace Irto\NeoMongo\Relationship;

use Irto\NeoMongo\PropertiesContainer;
use Irto\NeoMongo\Document\Helper;
use MongoCollection;

class Instance extends Abstraction {

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

}