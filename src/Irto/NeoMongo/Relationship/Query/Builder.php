<?php
namespace Irto\NeoMongo\Relationship\Query;

use Irto\NeoMongo\Relationship\Query\Parser\Instance as Parser;
use Irto\NeoMongo\Relationship\Instance as Relationship;
use MongoId;

class Builder {
	/**
	 * @var Array
	 */
	protected $engine = null;

	/**
	 * @var Array
	 */
	protected $projection_defaults = ['_id' => 1, '_collection' => 1, '_class' => 1];

	/**
	 *
	 *
	 * @param Array $query
	 */
	public function __construct($query = null){
		$this->initEngine();

		if(!is_null($query)){
			$this->parseQuery($query);
		}
	}

	/**
	 *
	 */
	public function initEngine(){
		$this->engine = Engine::factory();
	}

	/**
	 *
	 */
	public function parseQuery($query){
		$this->engine->load($query);
	}

	/**
	 *
	 *
	 */
	public function execute(Array $projection, Array $defaults = []){
		$this->engine->start($projection);
		$result = $this->engine->run();

		return $result;
	}

	/**
	 * 
	 *
	 */
	protected function prepareProjection(Array $projection, Array $values, $typeProp, Array $defaults = []){
		foreach($projection as $key => $value){
			if(is_numeric($key)) $projection[$value] = 1;
			else continue; // skip unset

			unset($projection[$key]);
		}
		
		$projection = array_merge($this->projection_defaults, $defaults, $projection);

		$_return = [];
		foreach($projection as $key => $value){
			if(isset($values[$typeProp . '.' . $key]))
				$_return[$typeProp]['$elemMatch'][$key] = $values[$typeProp . '.' . $key];
		}

		return $_return;
	}
}