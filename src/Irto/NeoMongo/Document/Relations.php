<?php
namespace Irto\NeoMongo\Document;

use Irto\NeoMongo\Relationship\Instance as Relationship;

trait Relations {
	/**
	 * Create a relationship between $this class and $doc
	 *
	 * @param Use Document $doc
	 * @param String $relType
	 *
	 * @return Irto\NeoMongo\Relationship\Instance|Bool
	 */
	public function relateTo($doc, $relType){
		if(class_exists($relType))
			$relationship = $relType::make($this, $doc);
		else
			$relationship = Relationship::make($this, $doc, $relType);

		return $relationship;
	}

	/**
	 * Return all relationships. 
	 * $relType can be string to filter by type, or bool to get all.
	 *
	 * @param Bool|String $all 
	 * @param String $direction [Irto\NeoMongo\Relationship\Instance::DIRECTION_ALL]
	 *
	 */
	public function getRelationships($relType = true, $direction = Relationship::DIRECTION_ALL){
		// todo: implements
	}

	/**
	 * Creates or update indexes for $relType in this collection
	 *
	 * @param String $relType
	 */
	public function performRelationIndexes($relType){
		return Relationship::performIndexes($this->collection(), $relType);
	}
}