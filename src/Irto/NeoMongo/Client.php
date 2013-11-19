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
	 * Returns $name database
	 *
	 * @param String $name
	 *
	 * @return MongoDB
	 */
	public static function db($name){
		$connection = static::getConnection();
		return $connection->{$name};
	}

	public function setDefaultDatabase($name){
		$this->default_database = $name;

		Model::setDb($name);
	}

	public function getDefaultDatabase(){
		if(is_string($this->default_database))
			return static::db($this->default_database);

		return null;
	}

}