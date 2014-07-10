<?php
use Rails\Config\Config;
use Rails\Config\Path;

class Rails
{
    protected static $serviceManager;
    
    protected static $env;
    
    protected static $root;
    
    // protected static $publicPath;
    
    protected static $application;
    
    protected static $booted = false;
    
    public static function setApplication($application)
    {
        self::$application = $application;
    }
    
    public static function application()
    {
        return self::$application;
    }
    
    public static function setEnv($env)
    {
        self::$env = $env;
    }
    
    public static function env()
    {
        return self::$env;
    }
    
    public static function setRoot($root)
    {
        self::$root = $root;
    }
    
    public static function root()
    {
        return self::$root;
    }
    
    // public static function path()
    // {
        // return __DIR__ . '/Rails';
    // }
    
    public static function setPublicPath($publicPath)
    {
        self::$publicPath = $publicPath;
    }
    
    public static function publicPath()
    {
        return self::$publicPath;
    }
    
    public static function cli()
    {
        return PHP_SAPI == 'cli';
    }
    
    public static function setServiceManager($serviceManager)
    {
        self::$serviceManager = $serviceManager;
    }
    
    public static function serviceManager()
    {
        return self::$serviceManager;
    }
    
    /**
     * Shortcut/alias for serviceManager().
     */
    public static function services()
    {
        return self::$serviceManager;
    }
    
    /**
     * Shortcut for serviceManager().
     */
    public static function service($name)
    {
        return self::$serviceManager->get($name);
    }
    
    public static function getService($name)
    {
        return self::$serviceManager->get($name);
    }
    
    public static function cache()
    {
        return self::getService('cache');
    }
    
    public static function log()
    {
        return self::getService('log');
    }
    
    public static function loader()
    {
        return self::getService('loader');
    }
    
    public static function assets()
    {
        return self::getService('assets');
    }
    
    public static function i18n()
    {
        return self::getService('i18n');
    }
    
    /**
     * Boot Rails.
     *
     * It requires at least paths to libraries and/or Composer Autoloader
     * to autoload classes. An instance of Rails\Loader\Loader can also be
     * passed instead.
     *
     * @param array $config
     */
    public static function boot(array $config)
    {
        if (self::$booted) {
            return;
        }
        
        /**
         * Loader.
         */
        if (!isset($config['loader'])) {
            throw new BadMethodCallException(
                'Requires at least loader instance or configuration'
            );
        } else {
            if (is_array($config['loader'])) {
                // require_once __DIR__ . '/Rails/Loader/Loader.php';
                $loader = new Rails\Loader\Loader();
                
                if (isset($config['loader']['paths'])) {
                    $loader->addPaths($config['loader']['paths']);
                }
                
                if (isset($config['loader']['composerAutoloader'])) {
                    $loader->setComposerAutoloader(
                        $config['loader']['composerAutoloader']
                    );
                } else {
                    $loader->addPath(__DIR__ . '/Rails');
                }
                $loader->register();
            } elseif ($config['loader'] instanceof Rails\Loader\Loader) {
                $loader = $config['loader'];
            } else {
                throw new InvalidArgumentException(
                    sprintf(
                        'Loader must be either array or instance of Rails\Loader\Loader, %s passed',
                        gettype($config['loader'])
                    )
                );
            }
        }
        
        /**
         * Service Manager.
         */
        $sm = new Zend\ServiceManager\ServiceManager();
        self::$serviceManager = $sm;
        $sm->setService('loader', $loader);
        
        /**
         * Global configuration.
         */
        $sm->setService('rails.config', new Rails\ActiveSupport\ArrayObject([
            'use_cache' => false
        ]));
        
        /**
         * Inflector.
         */
        $sm->setFactory('inflector', function() {
            return new Rails\ActiveSupport\Inflector\Inflector(
                new Rails\ActiveSupport\Inflector\Inflections\EnglishInflections,
                'en'
            );
        });
        
        /**
         * Translator.
         */
        $sm->setFactory('i18n', function() {
            $tr = new Rails\I18n\LoadingTranslator();
            $tr->setLocale('en');
            $tr->setLoader(new Rails\I18n\Loader());
            
            
            # Add Rails' locales paths to i18n loader
            $tr->loader()->addPaths([
                __DIR__ . '/Rails/ActiveSupport/Carbon/locales',
                __DIR__ . '/Rails/I18n/locales',
            ]);
            
            return $tr;
        });
        
        self::$booted = true;
    }
    
    public static function loadApplication($namespace, array $appConfig)
    {
        $className = $namespace . '\Application';
        
        $config = self::serviceManager()->get('rails.config');
        $config->merge($appConfig);
        // $config = 
        
        $rootPath = new Path($config['root']);
        $config->merge([
            'paths' => [
                'root' => $rootPath
            ]
        ]);
        
        if (!isset($config['paths']['public_path']) || $config['paths']['public_path']->none()) {
            $config['paths']['public_path'] = $rootPath->extend('public');
        } else {
            $config['paths']['public_path'] = new Path($config['paths']['public_path']);
        }
        
        $config['paths']['app']    = new Path('app', $rootPath);
        $config['paths']['config'] = new Path('config', $rootPath);
        require $config['paths']['config']->expand('application.php');
        
        $app = new $className($config);
        
        if (!self::$application) {
            self::$application = $app;
            # TODO: load/set app config
            self::$root = $config['paths']['root'];
            
            self::$serviceManager->setService('app.conf', $config);
            // self::$serviceManager->setService('app.conf', $config);
            
            error_reporting(E_ALL);
            
            if (self::cli()) {
                $app->dispatchConsole();
                return $app;
            } else {
                return $app;
            }
        } else {
            return $app;
        }
    }
}
