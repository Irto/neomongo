<?php
namespace Irto\NeoMongo\Relationship\Query\Parser;

use MongoId;

class Instance {

	/**
	 *
	 */
	use Parsers;

	/**
	 *
	 * @var Array
	 */
	protected $parsed_data = [[]];

	/**
	 *
	 */
	public static function factory(){
		return new static();
	}

	protected function __construct(){

	}

	/**
	 * Parse relationship query to mongo collection queries
	 *
	 * @param Array $query
	 *
	 * @return Array
	 */
	public function parse($query){
		if(is_array($query))
			$this->parseArray($query);

		$return = $this->parsed_data;
		foreach($return as &$data){
			unset($data['relationships']);
			if(isset($data['$match']) && !isset($data['$project']))
				$data['$project'] = [];
		}

		return $return;
	}

	/**
	 *
	 */
	public function parseString(){

	}

	/**
	 *
	 */
	public function parseArray($query){
		// order and parse data
		foreach($this->supported_parsers as $parser)
			if(array_key_exists('$'.$parser, $query))
				call_user_func_array(
					[$this, '_parse' . ucfirst($parser)],
					[$query['$'.$parser], &$this->parsed_data] );
			
		
	}
}