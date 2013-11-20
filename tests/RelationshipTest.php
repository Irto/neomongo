<?php

use Irto\NeoMongo\Document\Abstraction as Model;
use Irto\NeoMongo\Document\Relations;
use Irto\NeoMongo\Client;

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

		$relationship = $doc1->relateTo($doc2, 'know');

		$this->assertTrue($relationship instanceof Irto\NeoMongo\Relationship\Instance);
		$this->assertEquals('relCollection', $relationship->getProperty('_collection', false));
		$this->assertNotNull($relationship->getProperty('_id', false));

		$result = $relationship->save();
		$this->assertTrue($result);

		$return = [$relationship];

		$relationship = $doc2->relateTo($doc1, 'know');

		$relationship->setProperty('status', 'relationship');
		$relationship->setProperty('since', 12333333);

		$result = $relationship->save();
		$this->assertTrue($result);

		$result = $relationship->start_doc->collection()->findOne(['_id' => $relationship->start_doc->getProperty('_id')]);
		$this->assertTrue(isset($result['__dbrel_know'][0]['status']) && $result['__dbrel_know'][0]['status'] == 'relationship');
		$this->assertTrue(isset($result['__dbrel_know'][0]['since']) && $result['__dbrel_know'][0]['since'] == 12333333);

		$return[] = $relationship;
		return $return;
	}

	/**
	 * @depends testCreateRelation
	 */
	public function testUpdateRelation($rels){
		list($rel1, $rel2) = $rels;

		$rel1->setProperty('status', 'relationship');
		$rel1->setProperty('since', 12333);

		$rel1->save();

		$rel2->setProperty('status', 'friend');

		$time = time();
		$rel2->setProperty('since', $time);

		$rel2->save();

		$result = $rel2->start_doc->collection()->findOne(['_id' => $rel2->start_doc->getProperty('_id')]);
		$this->assertTrue(isset($result['__dbrel_know'][0]['status']) && $result['__dbrel_know'][0]['status'] == 'friend');
		$this->assertTrue(isset($result['__dbrel_know'][0]['since']) && $result['__dbrel_know'][0]['since'] == $time);
	}

	public static function tearDownAfterClass(){
		Client::_dropDb('test');
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
