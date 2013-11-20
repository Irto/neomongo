<?php 
namespace Irto\NeoMongo;

use MongoClient;
use Irto\NeoMongo\Document\Abstraction as Model;

Class Client extends MongoClient {
	/**
	 * Shared connection that will be used as default to static magic methods.
	 *
	 * @var MongoDB
	 */
	protected static $shared_connection;

	/**
	 * Default values to make connection string
	 */
	protected static $_config = array(
			'database' => '',
			'host' => 'localhost',
			'port' => 27017,
			'username' => '',
			'password' => ''
		);

	/**
	 * Default database name
	 *
	 * @var string
	 */
	protected $default_database = null;

	/**
	 * Create and estabilish a new connection with mongodb
	 *
	 * @param String $connection_string [array()]
	 *
	 * @return Irto\NeoMongoClient
	 */
	public static function make(Array $config = array()){
		$connection_string = static::makeConnectionString($config);

		try {
            $connection = new static($connection_string);

            if(isset($config['database']))
            	$connection->setDefaultDatabase($config['database']);
        } catch(\MongoConnectionException $e) {
            trigger_error('Failed to connect with string: "' . $connection_string . '"');
        }

        return $connection;
	}

	/**
	 * Make a connection script with $config options
	 *
	 * @param Array $conf [see default values]
	 *
	 * @return String
	 */
	public static function makeConnectionString(Array $config = array()){
		$config = array_merge(static::$_config, $config);

		$string = 'mongodb://';

		if(! empty($config['username']))
			$string .= $config['username'] . ':'
					. $config['password'] . '@';
		
		$string .= $config['host'] . ':'
				. $config['port'] . '/'
				. $config['database'];

		return $string;
	}

	/**
	 * Share connection to call functions stactically with connection as default
	 */
	public static function shareConnection(MongoClient $connection){
		static::$shared_connection = $connection;

		Model::setDb($connection->getDefaultDatabase());
	}

	/**
	 * Return shared connection
	 *
	 * @param Array $config
	 *
	 * @return Irto\NeoMongo\Client
	 */
	public static function getConnection(Array $config = array()){
		if(static::$shared_connection instanceof MongoClient)
			return static::$shared_connection;
		
		return static::make($config);
	}

	/**
	 * Returns $name mongodb database from shared connection
	 *
	 * @param String $name
	 *
	 * @return MongoDB
	 */
	public static function db($name){
		$connection = static::getConnection();
		return $connection->selectDB($name);
	}

	/**
	 * Set default database name
	 *
	 * @param String $name
	 */
	public function setDefaultDatabase($name){
		$this->default_database = $name;
	}

	/** 
	 * Return default database, setted on client connection
	 *
	 * @return String|Null
	 */
	public function getDefaultDatabase(){
		return $this->default_database;
	}

	/**
	 * Verify if the string is a mongoid
	 *
	 * @param String $string
	 *
	 * @return Bool
	 */
	public function isMongoId($string){
		return (is_string($string) && strlen($string) == 24 && ctype_xdigit($string));
	}

	public static function __callStatic($name, $args){
		$name = substr($name, 1);
		if(method_exists(static::$shared_connection, $name))
			return call_user_method_array($name, static::$shared_connection, $args);

		trigger_error('Method ' . $name . ' dont exists on Client or in Shared Connection.');
	}

}