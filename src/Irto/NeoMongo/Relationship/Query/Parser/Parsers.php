<?php
namespace Irto\NeoMongo\Relationship\Query\Parser;

use Irto\NeoMongo\Relationship\Instance as Relationship;
use MongoID;

Trait Parsers {
	/**
	 * 
	 */
	protected $supported_parsers = array(
			'type',
			'from',
			'to',
			'fromMatch',
			'toMatch',
			'relMatch',
		);

	/**
	 *
	 */
	public function _parseType ( $query, &$parsed ){
		$_parsed = &$parsed[ key($parsed) ];

		if(is_string($query))
			$query = [$query];

		foreach ($query as $key => $relation){
			$type = strtolower( (is_int($key)) ? $relation : $key );

			$_rel = array();
			$_rel['name'] = $type;
			$_rel['prefix'] = Relationship::REL_PREFIX;
			$_rel['prefixed'] = Relationship::REL_PREFIX . $type;

			if(is_string($relation) && class_exists($relation))
				$_rel['class'] = $relation;
			elseif(is_array($relation) && isset($relation['class']))
				$_rel['class'] = $relation['class'];

			$_parsed['relationships'][] = $_rel;
		}
	}

	/**
	 *
	 */
	public function _parseFrom ( $query, &$parsed ){
		$_parsed = &$parsed[ key($parsed) ];

		if(is_string($query))
			$query = [$query];

		foreach ($query as $from) {
			$_from = array();

			if(is_object($from)){
				$_from['class'] = get_class($from);
				$_parsed['$match']['_id']['$in'][] = $from->getProperty('_id');
			} elseif(is_string($from)){
				$_from['class'] = $from;
			}

			$_parsed['from'][] = $_from;
		}
	}

	/**
	 *
	 */
	public function _parseTo ( $query, &$parsed ){
		$_parsed = &$parsed[ key($parsed) ];

		if(is_string($query))
			$query = [$query];

		foreach ($query as $to) {
			$_to = array();

			// through the relationships to set $match condictions
			foreach($_parsed['relationships'] as $relationship){
				$relPrefixed = $relationship['prefixed'];

				if(is_object($to) && !$to instanceof MongoID){
					$_parsed['$match'][$relPrefixed . '._class']['$in'][] = get_class($to);
					$_parsed['$match'][$relPrefixed . '._id']['$in'][] = $to->getProperty('_id');
				} elseif(is_string($to)){
					$_parsed['$match'][$relPrefixed . '._class']['$in'][] = $to;
				} elseif($to instanceof MongoID){
					$_parsed['$match'][$relPrefixed . '._id']['$in'][] = $to;
				}
			}
		}
	}

	/**
	 *
	 */
	public function _parseFromMatch ( $query, &$parsed ){
		$_parsed = &$parsed[ key($parsed) ];

		foreach($query as $key => $value)
			$_parsed['$match'][$key] = $value;
	}

	/**
	 *
	 */
	public function _parseToMatch ( $query, &$parsed ){
		if(next($parsed) === false) $_parsed = &$parsed[];
		else $_parsed = &$parsed[ key($parsed) ];

		foreach($query as $key => $value)
			$_parsed['$match'][$key] = $value;

		prev($parsed);
	}

	/**
	 *
	 */
	public function _parseRelMatch ( $query, &$parsed ){
		$_parsed = &$parsed[ key($parsed) ];

		foreach($_parsed['relationships'] as &$relationship)
			foreach($query as $key => $value)
				$relationship['$match'][$relationship['prefixed'] . '.' . $key] = $value;
	}

	/**
	 *
	 */
	public function getRelationships(){
		return $this->parsed_data[0]['relationships'];
	}
}