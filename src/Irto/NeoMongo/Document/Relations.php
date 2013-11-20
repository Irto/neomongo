<?php
namespace Irto\NeoMongo\Document;

use Irto\NeoMongo\Relationship\Instance as Relationship;

trait Relations {
	/**
	 * Create a relationship between $this class and $doc
	 *
	 * @param Use Document $doc
	 * @param String $type
	 *
	 * @return Irto\NeoMongo\Relationship\Instance|Bool
	 */
	public function relateTo($doc, $type){
		if(class_exists($type))
			$relationship = $type::make($this, $doc);
		else
			$relationship = Relationship::make($this, $doc, $type);

		return $relationship;
	}

	/**
	 * Return all relationships. 
	 * $all can be string to filter by type, or bool to get all.
	 * 
	 *
	 * @param Bool|String $all 
	 * @param
	 *
	 */
	public function getRelationships($all = true, $direction = Relationship::DIRECTION_ALL){

		
	}

	public function performRelationsIndexes($relType){
		return Relationship::performIndexes($this->collection(), $relType);
	}
}