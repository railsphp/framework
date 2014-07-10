<?php
namespace Rails\Application;

use Zend;
use Rails\ServiceManager\ServiceLocatorAwareTrait;
use Rails\Application\Config\Loader as ConfigLoader;
use Rails\Config\Config;
use Rails\ActionDispatch\Request as HttpRequest;
use Rails\ActionDispatch\Http\Session as Session;
use Rails\ActionDispatch\Http\Parameters as HttpParameters;
use Rails\ActionDispatch\Http\Response as HttpResponse;
use Rails\ActionDispatch\ErrorReporting\Reporter as ErrorReporter;
use Rails\Routing\ActionToken;

abstract class Base
{
    use ServiceLocatorAwareTrait;
    
    protected $config;
    
    protected $routes;
    
    protected $request;
    
    protected $session;
    
    protected $response;
    
    protected $parameters;
    
    /**
     * @var ConfigLoader
     */
    protected $configLoader;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->getService('loader')->addPaths([
            $config['paths']['root']->expand('lib'),
            $config['paths']['root']->expand('vendor'),
            $config['paths']['app']->expand('models'),
            $config['paths']['app']->expand('controllers'),
            $config['paths']['app']->expand('helpers'),
            $config['paths']['app']->expand('presenters'),
            $config['paths']['app']->expand('mailers')
        ]);
        
        $this->configLoader = new ConfigLoader($this);
        $this->configLoader->loadDefaultConfig();
        $this->configLoader->loadEnvironmentConfig();
        $this->initConfig($this->config);
        
        $this->configLoader->setUpAppCache();
        
        if ($this->config['use_cache']) {
            $this->getService('loader')->setCache($this->getService('rails.cache'));
        }
        
        $this->configLoader->setUpDatabase();
        $this->services()->setFactory('assets', [$this->configLoader, 'assetsFactory']);
        
        chdir($this->config['paths']['root']);
        
        # Setup Logger
        $this->setUpLogger();
        
        if ($config['error']['report_types']) {
            set_error_handler([$this, 'errorHandler'], $config['error']['report_types']);
        }
        
        set_exception_handler([$this, 'exceptionHandler']);
        require_once __DIR__ . '/../Toolbox/functions.php';
    }
    
    public function config()
    {
        return $this->config;
    }
    
    public function configLoader()
    {
        return $this->configLoader;
    }
    
    public function dispatchConsole()
    {
        $app = new \Rails\Console\TaskApplication($this);
        $app->run();
    }
    
    public function dispatchHttp()
    {
        if (!$this->config['action_controller']['error_handler']) {
            if ($this->config['environment'] == 'production') {
                
            }
        }
        
        $this->getService('log')->setRequest($this->request());
        
        $routeParams = $this->routes()->match(
            $this->request()->path(),
            $this->request()->method()
        );
        
        if (!$routeParams) {
            # Route not found.
            throw new \Rails\Routing\Exception\NotFoundException(
                sprintf(
                    'No route matches [%s] "%s"',
                    $this->request()->method(),
                    $this->request()->path()
                )
            );
        }
        
        list ($route, $routeVars) = $routeParams;

        if ($route->controller() == ':controller') {
            $route->setProperController(
                $this->getService('inflector')
                    ->camelize($routeVars['controller'])->toString()
            );
        }
        if ($route->action() == ':action') {
            $route->setProperAction(
                $this->getService('inflector')
                    ->camelize($routeVars['action'])->toString()
            );
        }
        
        $this->parameters()->setRouteVars($routeVars);
        
        ActionToken::setRoute($route);
        
        $this->routes()->setRequestRoute($route);
        $this->request()->setRequestRoute($route);
        
        $endPoint = $route->endPoint();
        
        call_user_func(
            $endPoint,
            $this
        );
        
        $this->response()->commit();
    }
    
    public function exceptionHandler($e)
    {
        $this->getService('log')->exception($e);
        
        $reporter = new ErrorReporter($this);
        $reporter->reportException($e);
        $this->response()->commit();
    }
    
    public function errorHandler($errno, $errstr, $errfile, $errline, $errargs)
    {
        throw ErrorReporter::buildErrorException($errno, $errstr, $errfile, $errline, $errargs);
    }
    
    public function request()
    {
        if (!$this->request) {
            $this->request = new HttpRequest();
        }
        return $this->request;
    }
    
    public function session()
    {
        if (!$this->session) {
            $this->session = new Session();
        }
        return $this->session;
    }
    
    public function parameters()
    {
        if (!$this->parameters) {
            $this->parameters = new HttpParameters();
        }
        return $this->parameters;
    }
    
    public function response()
    {
        if (!$this->response) {
            $this->response = new HttpResponse();
        }
        return $this->response;
    }
    
    public function routes()
    {
        if (!$this->routes) {
            $this->loadHttpRoutes();
        }
        return $this->routes;
    }
    
    protected function initConfig($config)
    {
    }
    
    protected function loadHttpRoutes()
    {
        $this->routes = $this->configLoader->loadRoutes();
    }
    
    protected function setUpLogger()
    {
        $formatter = new \Rails\Log\Formatter\Simple();
        
        $writer = new Zend\Log\Writer\Stream($this->config['paths']['root']->expand('log', $this->config['environment'] . '.log'));
        $writer->setFormatter($formatter);
        
        $logger = new \Rails\Log\Logger();
        $logger->addWriter($writer);
        
        $this->services()->setService('log', $logger);
    }
}
