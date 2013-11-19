<?php
namespace Irto\NeoMongo;

use Illuminate\Support\ServiceProvider;

class NeomongoServiceProvider extends ServiceProvider {
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('irto/neomongo');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerConnector();
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

    /**
     * Register MongoDbConnector within the application
     *
     * @return void
     */
    public function registerConnector()
    {
        $connectionString = $this->buildConnectionString();

        $connection = new MongoDbConnector;
        $connection->getConnection( $connectionString );

        $this->app['NeoMongoConnector'] = $this->app->share(function($app) use ($connection)
        {
            return $connection;
        });
    }

    /**
     * Builds the connection string based in the laravel's config/database.php
     * config file.
     *
     * @return string The connection string
     */
    protected function buildConnectionString()
    {
        $config = $this->app->make('config');

        if (! $result = $config->get('database.mongodb.connectionString')) {

            // Connection string should begin with "mongodb://"
            $result = 'mongodb://';

            // If username is present, append "<username>:<password>@"
            if ($config->get('database.mongodb.username')) {
                $result .=
                    $config->get('database.mongodb.username').':'.
                    $config->get('database.mongodb.password', '').'@';
            }

            // Append "<host>:<port>/<database>"
            $result .=
                $config->get('database.mongodb.host', '127.0.0.1').':'.
                $config->get('database.mongodb.port', 27017).'/'.
                $config->get('database.mongodb.database', 'neomongo');

        }

        return $result;
    }
}