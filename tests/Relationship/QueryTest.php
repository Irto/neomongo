<?php

use Irto\NeoMongo\Relationship\Query\Builder as Query;
use Irto\NeoMongo\Client;

Class QueryTest extends PHPUnit_Framework_TestCase {
	public function testQuery(){
		$query = [
				'$from' => 'RelDocument',
				'$to' => 'OtrDocument',
				'$type' => 'know',
				'$toMatch' => ['name' => 'Fulano']
			];
			
		$query = new Query($query);
		$result = $query->execute(['name']);

//		$this->assertTrue(is_array($result) && !empty($result));
	}


	public static function tearDownAfterClass(){
		//Client::_dropDb('test');v && phpunit
	}
}