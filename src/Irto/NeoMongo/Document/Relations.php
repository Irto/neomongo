<?php
namespace Irto\NeoMongo\Document;

use Irto\NeoMongo\Relashionship\Instance as Relashionship;

trait Relations {
	/**
	 * Create a relationship between $this class and $doc
	 *
	 * @param Use Document $doc
	 * @param String $type
	 *
	 * @return Bool
	 */
	public function relateTo($doc, $type){
		if( !in_array('Irto\NeoMongo\Document\Model', class_uses($doc)) ) return false;

		if(class_exists($type))
			$relashionship = $type::make($this, $doc);
		else
			$relashionship = Relashionship::make($this, $doc, $type);

		return $relashionship;
	}

	/**
	 * Return all relashionships. 
	 * $all can be string to filter by type, or bool to get all.
	 * 
	 *
	 * @param Bool|String $all 
	 * @param
	 *
	 */
	public function getRelationships($all = true, $direction = Relashionship::DIRECTION_ALL){
		if( !in_array('Irto\NeoMongo\Document\Model', class_uses($doc)) ) return false;

		
	}
}