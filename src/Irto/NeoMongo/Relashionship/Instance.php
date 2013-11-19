<?php
namespace Irto\NeoMongo\Relashionship;

use Irto\NeoMongo\PropertiesContainer;

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
	 * Load relashionship capatibility and properties container
	 */
	use PropertiesContainer;
	use Model;

}