<?php

use Irto\NeoMongo\Document\Abstraction as Model;

Class DocumentTest extends PHPUnit_Framework_TestCase {

	public function testMake(){
		$instance = DBDocument::make('teste1', 'teste2');

		$this->assertTrue($instance instanceof DBDocument);
		$this->assertEquals('teste1', $instance->var1);
		$this->assertEquals('teste2', $instance->var2);

		return $instance;
	}

	/**
	 * @depends testMake
	 */
	public function testSetAndGetProperties($document){
		$document->setProperty('any', 'some');

		$prop = $document->getProperty('any', false);
		$this->assertEquals('some', $prop);

		$prop = $document->getProperty('any');
		$this->assertEquals(null, $prop);

		$document->setProperty('name', 'Fulano da Silva');
		$document->setProperty('number', 14);
		$document->setProperty('onumber', '10');
		$document->setProperty('array', ['teste', 'teste', 50]);

		$this->assertNull($document->getProperty('name'));
		$this->assertEquals('Fulano da Silva', $document->getProperty('name', false));

		$document->pushProperty('array', 'item');
		$this->assertTrue(in_array('item', $document->getProperty('array', false)));

		return $document;
	}

	/**
	 * @depends testSetAndGetProperties
	 */
	public function testInsert($document){
		$this->assertNull($document->getProperty('_id'), 'need to don\'t have id');

		$document->save();

		$this->assertNull($document->getProperty('name', false), 'must to be null');
		$this->assertNotNull($document->getProperty('_id'), 'don\'t have id');
		$this->assertNotNull($document->getProperty('name'), 'saved properties must to be original');

		$result = $document->collection()->findOne(['_id' => $document->getProperty('_id')]);
		$this->assertTrue(isset($result['name']) && $result['name'] == 'Fulano da Silva');

		return $document;
	}

	/**
	 * @depends testInsert
	 */
	public function testFind($document){
		$otrdoc = DBDocument::first($document->getProperty('_id'), ['name', 'number' => 0]);

		$this->assertTrue($otrdoc instanceof DBDocument);
		$this->assertEquals($document->getProperty('name'), $otrdoc->getProperty('name'));
		$this->assertNull($otrdoc->getProperty('number'));

		$otrdoc = DBDocument::first($document->getProperty('_id'), ['number' => 0]);

		$this->assertTrue($otrdoc instanceof DBDocument);
		$this->assertEquals($document->getProperty('name'), $otrdoc->getProperty('name'));
		$this->assertNull($otrdoc->getProperty('number'));

		$otrdoc = DBDocument::first($document->getProperty('_id'));

		$this->assertTrue($otrdoc instanceof DBDocument);
		$this->assertEquals($document->getProperty('name'), $otrdoc->getProperty('name'));
		$this->assertEquals($document->getProperty('number'), $otrdoc->getProperty('number'));
		$this->assertCount(4, $otrdoc->getProperty('array'));

		return $document;
	}

	/**
	 * @depends testFind
	 */
	public function testUpdate($document){
		$document->setProperty('name', 'Fulana da Silvana');
		$this->assertEquals('Fulana da Silvana', $document->getProperty('name', false));

		$document->setProperty('number', 10);
		$this->assertEquals(10, $document->getProperty('number', false));

		$document->setProperty('onumber', 18);
		$this->assertEquals(18, $document->getProperty('onumber', false));

		$document->pushProperty('array', 'updateItem');
		$this->assertTrue(in_array('updateItem', $document->getProperty('array', false)), 'has no updateItem on array');
		$this->assertCount(4, $document->getProperty('array'));
		$this->assertCount(5, $document->getProperty('array', false));

		$document->save();

		$this->assertEquals('Fulana da Silvana', $document->getProperty('name'), 'name not updated');
		$this->assertEquals(10, $document->getProperty('number'));
		$this->assertEquals(18, $document->getProperty('onumber'));
		$this->assertTrue(in_array('updateItem', $document->getProperty('array')), 'has no updateItem on array (2)');

		$otrdoc = DBDocument::first($document->getProperty('_id'));

		$this->assertEquals('Fulana da Silvana', $otrdoc->getProperty('name'), 'name not updated (2)');
		$this->assertEquals(10, $otrdoc->getProperty('number'));
		
		$this->assertTrue(in_array('updateItem', $otrdoc->getProperty('array')), 'has no updateItem on array (3)');
		$this->assertCount(5, $otrdoc->getProperty('array'));

		return $document;
	}

	/**
	 * @depends testUpdate
	 */
	public function testUpdateExtended($document){
		$document->unsetProperty('onumber');
		$document->pushProperty('array', 'updateExItem1');
		$document->pushProperty('array', 'updateExItem2');
		$this->assertTrue(in_array('updateExItem1', $document->getProperty('array', false)), 'has no updateExItem1 on array');
		$this->assertTrue(in_array('updateExItem2', $document->getProperty('array', false)), 'has no updateExItem2 on array');
		$this->assertCount(5, $document->getProperty('array'));
		$this->assertCount(7, $document->getProperty('array', false));

		$document->save();

		$this->assertNull($document->getProperty('onumber'));
		$this->assertTrue(in_array('updateExItem1', $document->getProperty('array')), 'has no updateExItem1 on array 2');
		$this->assertTrue(in_array('updateExItem2', $document->getProperty('array')), 'has no updateExItem2 on array 2');
		$this->assertCount(7, $document->getProperty('array'));
		$this->assertNull($document->getProperty('array', false));

		$otrdoc = DBDocument::first($document->getProperty('_id'));

		$this->assertNull($otrdoc->getProperty('onumber'));
		$this->assertTrue(in_array('updateExItem1', $otrdoc->getProperty('array')), 'has no updateExItem1 on array 3');
		$this->assertTrue(in_array('updateExItem2', $otrdoc->getProperty('array')), 'has no updateExItem2 on array 3');
		$this->assertCount(7, $otrdoc->getProperty('array'));
		$this->assertNull($otrdoc->getProperty('array', false));
	}

	/**
	 * @depends testInsert
	 */
	public function testDelete($document){
		$document->delete();

		$otrdoc = DBDocument::first($document->getProperty('_id'));

		$this->assertNull($otrdoc);
	}
}

Class DBDocument extends Model {
	protected static $collection = 'collection';

	public $var1 = null;
	public $var2 = null;

	public function setup($var1 = null, $var2 = null){
		$this->var1 = $var1;
		$this->var2 = $var2;
	}
}
