<?php
use Irto\NeoMongo\Client;
use Irto\NeoMongo\Document\Abstraction as Model;

Class ClientTest extends PHPUnit_Framework_TestCase {
	public function testConnectionString(){
		$string = Client::makeConnectionString();
		$string_expected = 'mongodb://localhost:27017/';

		$this->assertEquals($string_expected, $string);

		$config = [
			'username' => 'username',
			'password' => 'password',
			'database' => 'test'];
		$string = Client::makeConnectionString($config);
		$string_expected = 'mongodb://username:password@localhost:27017/test';

		$this->assertEquals($string_expected, $string);
	}

	/**
	 * @depends testConnectionString
	 */
	public function testMakeConnection(){
		$client = Client::make(['database' => 'test']);

		$this->assertTrue($client instanceof Client);

		return $client;
	}

	/**
	 * @depends testMakeConnection
	 */
	public function testShareConnection($conn){
		Client::shareConnection($conn);

		$_conn = Client::getConnection();

		$this->assertTrue($_conn == $conn);
	}

	/**
	 * @depends testMakeConnection
	 */
	public function testGetDB($conn){
		$db = Client::db('test');

		$this->assertTrue($db instanceof MongoDB);

		$database = $conn->getDefaultDatabase();
		$this->assertEquals('test', $database);

		$conn->setDefaultDatabase('test2');
		$database = $conn->getDefaultDatabase();
		$this->assertEquals('test2', $database);
		$this->assertTrue(Model::db() instanceof MongoDB);

		$conn->setDefaultDatabase('test');
	}
}