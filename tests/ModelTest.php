<?php

use Irto\NeoMongo\Document\Abstraction as Model;

Class ModelTest extends PHPUnit_Framework_TestCase {

	public function testMake(){
		$instance = DBCollection::make('teste1', 'teste2');

		$this->assertTrue($instance instanceof DBCollection);
		$this->assertEquals('teste1', $instance->var1);
		$this->assertEquals('teste2', $instance->var2);

		return $instance;
	}

	/**
	 * @depends testMake
	 */
	public function testSetAndGetProperties($collection){
		$collection->setProperty('any', 'some');

		$prop = $collection->getProperty('any', false);
		$this->assertEquals('some', $prop);

		$prop = $collection->getProperty('any');
		$this->assertEquals(null, $prop);

		return $collection;
	}

	/**
	 * @depends testSetAndGetProperties
	 */
	public function testCRUDOperations($collection){
		
	}
}

Class DBCollection extends Model {
	protected static $collection = 'collection';

	public $var1 = null;
	public $var2 = null;

	public function setup($var1, $var2){
		$this->var1 = $var1;
		$this->var2 = $var2;
	}
}
