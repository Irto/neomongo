<?php
namespace Irto\NeoMongo\Document;

use Irto\NeoMongo\Client;
use Irto\NeoMongo\PropertiesContainer;
use MongoDB;

Abstract Class Abstraction {

	/**
	 * Loads Document model functions and properties container
	 */
	use PropertiesContainer;
	use Helper;
	use Model;

	/**
	 * Document client
	 *
	 * @var MongoClient
	 */
	protected static $client = null;

	/**
	 * Name of collection on database
	 *
	 * @var String
	 */
	protected static $database = null;

	/**
	 * Name of collection on database
	 *
	 * @var String
	 */
	protected static $collection = null;

	/**
	 * Set mongodb client to this model
	 *
	 * @param MongoClient $client
	 */
	public static function setClient(MongoClient $client){
		static::$client = $client;
	}

	/**
	 * Get mongodb client to this model
	 *
	 * @return MongoClient
	 */
	public static function client(){
		return static::$client;
	}

	/**
	 * Set database name for model
	 *
	 * @param String $name
	 */
	public static function setDb($name){
		if(!is_string($name)) trigger_error("Failed to set database name, string expected, \'" . gettype($name) . "\' given.");

		static::$database = $name;
	}

	/**
     * Returns the database object (the connection)
     *
     * @return MongoDB
     */
	public static function db(){
		if(isset(static::$database) && static::$database instanceof MongoDB)
			return static::$database;

		if(is_null(static::$client))
			return Client::db(static::$database);

		return static::client()->selectDB(static::$database);
	}

	/**
     * Returns the Mongo collection object
     *
     * @return MongoCollection
     */
	public static function collection(){
		return static::db()->selectCollection(static::getCollection());
	}

	/**
     * Returns the Mongo collection name
     *
     * @return String
     */
	public static function getCollection(){
		return static::$collection;
	}

	public function __construct(){
		
	}
}