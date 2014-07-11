<?php
namespace Rails\Application\Config;

use Rails\Routing\Route\RouteSet;
use Rails\Routing\Route\BuiltRoute;
use Rails\Application\Base as AppBase;
use Rails\ActiveRecord\Connection\Manager as ConnectionManager;
use Rails\ActiveRecord\Base as ActiveRecordBase;
use Rails\Assets\Assets;
use Rails\Yaml\Parser as Yaml;
use Rails\Cache\Cache;
use Rails\Loader\Exception\ClassNotFoundException;

class Loader
{
    protected $app;
    
    public function __construct(AppBase $app)
    {
        $this->app = $app;
    }
    
    public function loadDefaultConfig()
    {
        $config = $this->app->config();
        require __DIR__ . '/default_config.php';
    }
    
    public function loadRoutes()
    {
        if ($this->app->config()['use_cache']) {
            $appName = get_class();
            $appName = substr($appName, 0, strpos($appName, '\\'));
            
            $key = 'rails.routes.' . $appName;
            
            if (!$routes = $this->app->getService('rails.cache')->read($key)) {
                $routeSet = $this->initRouteSet();
                $jsonRoutes = [];
                foreach ($routeSet as $route) {
                    $jsonRoutes[] = $route->build()->toArray();
                }
                $this->app->getService('rails.cache')->write($key, $jsonRoutes);
                
            } else {
                $routeSet = new RouteSet();
                
                foreach ($routes as $route) {
                    $routeSet[] = BuiltRoute::fromArray($route);
                }
            }
        } else {
            $routeSet = $this->initRouteSet();
        }
        
        if ($this->app->request()) {
            $routeSet->setBasePath($this->app->request()->basePath());
        }
        
        if (!$this->app->config()['serve_static_assets']) {
            $routeSet[] = BuiltRoute::fromArray(
                Routes::assetsRoute($this->app->config()['assets']['prefix'])
            );
        }
        
        if ($this->app->config()['environment'] == 'development') {
            $routeSet[] = BuiltRoute::fromArray(
                Routes::railsInfoRoute()
            );
        }
        
        return $routeSet;
    }
    
    public function loadEnvironmentConfig()
    {
        $config     = $this->app->config();
        $env        = $config['environment'];
        $file       = $config['paths']['config']->expand('environments', $env . '.php');
        $configurer = require $file;
        $configurer($config);
    }
    
    public function loadInitializers()
    {
        $pattern = $this->app->config()['paths']['config']->expand('initializers', '*.php');
        $files   = glob($pattern);
        
        if ($files) {
            foreach ($files as $file) {
                require $file;
            }
        }
    }
    
    public function setUpDatabase()
    {
        $dbConfig = $this->getDatabaseConfig();
        
        if (!$dbConfig[0]) {
            return;
        }
        
        $this->app->services()->setFactory('defaultConnectionManager', function() use ($dbConfig) {
            list (
                $connections,
                $defaultConnection,
                $options
            ) = $dbConfig;
            
            $connectionManager  = new ConnectionManager(
                $connections,
                $defaultConnection,
                $options
            );
            
            return $connectionManager;
        });
    }
    
    public function assetsFactory()
    {
        $rootPath = $this->app->config()['paths']['root'];
        $assets   = new Assets();
        $assets->addPathsFrom([
            $rootPath->expand('app', 'assets'),
            $rootPath->expand('lib', 'assets'),
            $rootPath->expand('vendor', 'assets')
        ]);
        
        return $assets;
    }
    
    public function setUpAppCache()
    {
        $this->app->services()->setFactory('cache', function() {
            return $this->createAppCache();
        });
        $this->app->services()->setFactory('rails.cache', function() {
            return $this->createRailsAppCache();
        });
    }
    
    protected function initRouteSet()
    {
        $builder  = require $this->app->config()['paths']['config']->expand('routes.php');
        $routeSet = new RouteSet();
        $routeSet->resources()->draw($builder);
        
        if ($this->app->config()['use_cache']) {
            $routeSet->setCache($this->app->getService('rails.cache'));
        }
        
        return $routeSet;
    }
    
    protected function createAppCache()
    {
        $cache  = new Cache();
        $config = $this->app->config()['cache_store'];
        if (!$config) {
            $store  = 'FileStore';
            $config = [$this->app->config()['paths']['root']->expand('tmp', 'cache', 'app')];
        } else {
            $store = array_shift($config);
        }
        
        $store = $this->createCacheStore($store, $config);
        
        $cache->setStore($store);
        return $cache;
    }
    
    protected function createRailsAppCache()
    {
        $cache  = new Cache();
        $config = $this->app->config()['rails_cache_store'];
        if (!$config) {
            $store  = 'FileStore';
            $config = [$this->app->config()['paths']['root']->expand('tmp', 'cache', 'rails')];
        } else {
            $store = array_shift($config);
        }
        
        $store = $this->createCacheStore($store, $config);
        
        $cache->setStore($store);
        return $cache;
    }
    
    protected function createCacheStore($store, array $config)
    {
        
        switch ($store) {
            case 'FileStore':
                $store = new \Rails\Cache\Store\FileStore(array_shift($config));
                break;
            
            # TODO: setup other stores
            
            default:
                try {
                    $store = new $store($config);
                } catch (ClassNotFoundException $e) {
                    throw new Exception\RuntimeException(
                        sprintf(
                            "Cache store class '%s' couldn't be found",
                            $store
                        ),
                        $e
                    );
                }
                break;
        }
        
        return $store;
    }
    
    /**
     * Get database configuration
     *
     * @return array
     */
    protected function getDatabaseConfig()
    {
        if ($this->app->config()['use_cache']) {
            $key = 'rails.ar.dbconfig';
            if ($dbConfig = $this->app->getService('rails.cache')->read($key)) {
                return $dbConfig;
            }
        }
        
        $connections = $this->getDatabaseConnections();
        if (!$connections) {
            return [[], null, null];
        }
        
        $config  = $this->app->config();
        
        if ($config['active_record']['allow_profiler'] === null) {
            $allowProfiler = ($config['environment'] == 'production' || $config['environment'] == 'test');
        } else {
            $allowProfiler = (bool)$config['active_record']['allow_profiler'];
        }
        
        $options = [
            'allowProfiler' => $allowProfiler
        ];
        
        if (isset($connections['default'])) {
            $defaultConnction = 'default';
        } else {
            $defaultConnction = key($connections);
        }
        
        $dbConfig = [
            $connections,
            $defaultConnction,
            $options
        ];
        
        if ($this->app->config()['use_cache']) {
            $this->app->getService('rails.cache')->write($key, $dbConfig);
        }
        
        return $dbConfig;
    }
    
    /**
     * Get database connections
     * Gets the configuration from the database file.
     *
     * @return array|bool
     */
    protected function getDatabaseConnections()
    {
        $configFile = $this->findDatabaseFile();
        
        if (!$configFile) {
            return false;
        }
        
        switch (substr($configFile, -3)) {
            case 'php':
                $connections = require $configFile;
                break;
            
            case 'yml':
                $connections = Yaml::readFile($configFile);
                break;
        }
        
        return $connections;
    }
    
    /**
     * Find database file
     * Will search for `config/database.php|yml`.
     *
     * @return string|bool
     */
    protected function findDatabaseFile()
    {
        $pattern = $this->app->config()['paths']['config']->expand('database.{php,yml}');
        $files = glob($pattern, GLOB_BRACE);
        if ($files) {
            return current($files);
        }
        return false;
    }
}
