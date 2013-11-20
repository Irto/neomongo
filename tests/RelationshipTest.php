<?php

use Irto\NeoMongo\Document\Abstraction as Model;
use Irto\NeoMongo\Document\Relations;

Class RelationshipTest extends PHPUnit_Framework_TestCase {

	public function testGenerateDocuments(){
		$doc1 = OtrDocument::make();
		$doc2 = RelDocument::make();

		$doc1->setProperty('name', 'Fulano');
		$doc2->setProperty('name', 'Ciclano');

		$doc1->save();
		$doc2->save();
		
		return [$doc1, $doc2];
	}

	/**
	 * @depends testGenerateDocuments
	 */
	public function testCreateRelation($docs){
		list($doc1, $doc2) = $docs;

		$doc1->relateTo($doc2, 'know');
	}
}

Class RelDocument extends Model {
	use relations;

	protected static $collection = 'relCollection';

}

Class OtrDocument extends Model {
	use relations;

	protected static $collection = 'otrCollection';

}
