<?php
namespace Irto\NeoMongo\Relationship\Query;

use Irto\NeoMongo\Relationship\Query\Parser\Instance as Parser;

class Engine {

	/**
	 *
	 */
	protected $data = null;

	/**
	 *
	 */
	protected $projection = [];

	/**
	 *
	 */
	protected $queries = [];

	/**
	 *
	 */
	protected $parser = null;

	/**
	 *
	 */
	public static function factory(){
		return new static();
	}

	/**
	 *
	 */
	protected function __construct(){
		$this->parser = Parser::factory();
	}

	/**
	 *
	 */
	public function load(Array $query, Array $projection = null){
		$this->queries = $query;

		if(!is_null($projection))
			$this->projection = $projection;
	}

	/**
	 *
	 */
	public function start(Array $projection = null){
		$query = $this->queries;
		$this->queries = $this->parser->parse($query);

		if(!is_null($projection))
			$this->projection = $projection;
	}

	/**
	 *
	 */
	public function run(){
		$queries = $this->queries;

		$result = $this->doQueries($queries);

		return $result;
	}

	/**
	 *
	 */
	protected function doQueries($queries){
		$result = array();

		$query = &$queries[0];
		if(isset($query['from'])){
			$from = $query['from'];
			unset($query['from']);
		} else trigger_error('Syntax error: has no from on query on ' . print_r($query, true) . '.');

		foreach($from as $_from){
			$collection = $_from['class']::collection();
			
			$_result = call_user_func_array([$collection, 'find'], [$query['$match'], $query['$project']]);
			$result = array_merge($result, iterator_to_array($_result));
		}
		unset($query, $queries[0]);
		$relationships = $this->parser->getRelationships();

		foreach($queries as $k => &$query)
			$this->doSubquery($query, $relationships, $result);
			
		return $result;
	}

	/**
	 *
	 */
	protected function doSubquery($query, $relationships, &$result){
		$_query = $query['$match'];
		$_ids = [];

		foreach($result as &$_result){
			foreach($relationships as $relationship){
				$relPrefixed = $relationship['prefixed'];

				if(isset($_result[$relPrefixed])){
					$rel = $_result[$relPrefixed][0];
					
					$_ids[$rel['_class']][] = $rel['_id'];
				}
			}
		}

		$docs = [];
		foreach ($_ids as $key => $value) {
			$collection = $key::collection();

			$criteria = array_merge( [ '_id' => ['$in' => $value] ], $_query);
			$_docs = call_user_func_array([$collection, 'find'], [$criteria, ['_id' => 1]]);

			foreach($_docs as $id => $doc)
				$docs[$id] = true;
		}

		$result = array_filter($result, function($_result) use ($relationships, $docs){
			foreach($relationships as $relationship){
				$relPrefixed = $relationship['prefixed'];

				if(isset($_result[$relPrefixed])){
					$id = $_result[$relPrefixed][0]['_id']->__toString();
					if(array_key_exists($id, $docs)) return true; // jump unset
				}
			}

			return false;
		});
	}
}