<?php
namespace Rails\ActionController\Test;

use Closure;
use ReflectionClass;
// use Rails\ActionDispatch\Http\Response;
use Rails\ActionDispatch\Http\Session;
use Rails\ActionDispatch\Test\Http\Parameters as TestParameters;
use Rails\ActionDispatch\Test\Http\Request as TestRequest;
use Rails\ActionDispatch\Test\Http\Response as TestResponse;
use Rails\ActionDispatch\Test\Constraint;
use Rails\Test\TestCase as BaseTestCase;
use Rails\Routing\ActionToken;
use Rails\ActionDispatch\ActionDispatch;

abstract class TestCase extends BaseTestCase
{
    /**
     * Controller class is taken out of the name of the
     * test class, which is assumed to end in Test, like
     * UsersControllerTest. A different name can be defined
     * with this property.
     *
     * @var string
     */
    protected $controllerClass;
    
    protected $controller;
    
    protected $parameters;
    
    protected $request;
    
    protected $session;
    
    protected $routeSet;
    
    /**
     * Last used cookies
     * @var \Rails\ActionDispatch\Http\Cookies\CookieJar
     */
    protected $cookieJar;
    
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->setUpRequest();
    }
    
    public function get($actionName, array $customParams = [], array $session = [])
    {
        $this->runAction('get', $actionName, $customParams, $session);
    }
    
    public function post($actionName, array $customParams = [], array $session = [])
    {
        $this->runAction('post', $actionName, $customParams, $session);
    }
    
    public function runAction($method, $actionName, array $customParams = [], array $session = [])
    {
        $controllerClass = $this->resolveControllerClass();
        $path = $this->ensureRouteExists($controllerClass, $actionName, $customParams);
        
        $response = new TestResponse();
        $this->setCustomRequestParams($this->parameters, $method, $customParams);
        
        $appClone = clone $this->application();
        $appRefl  = new ReflectionClass(get_class($appClone));
        
        $response->setCookieJar($this->request->cookieJar());
        
        foreach(['request', 'parameters', 'session', 'response'] as $propName) {
            $prop = $appRefl->getProperty($propName);
            $prop->setAccessible(true);
            $value = $propName == 'response' ? $response : $this->$propName;
            $prop->setValue($appClone, $value);
        }
        
        $this->cookies = $response->cookieJar();
        // $this->request->resetCookieJar();
        // vde($response->cookieJar()->sn);
        // $path = 
        
        $dispatcher = new ActionDispatch($appClone);
        $endPoint = $dispatcher->dispatch($method, $path);
        
        $this->controller = $endPoint->controller();
        
        // $this->controller = new $controllerClass(
            // $this->request,
            // $response,
            // $this->parameters,
            // $this->session,
            // $this->routeSet
        // );
        
        // $this->controller->runAction($actionName);
    }
    
    public function cookies()
    {
        return $this->cookies;
    }
    
    public function assigns()
    {
        return $this->controller->assigns();
    }
    
    public function assertResponse($response, $message = '')
    {
        $constraint = new Constraint\Response();
        $constraint->setController($this->controller);
        self::assertThat($response, $constraint, $message);
    }
    
    /**
     * Verifies that the route exists and returns the path as string.
     */
    protected function ensureRouteExists($controllerClass, $actionName, array $parameters)
    {
        $nameParts = array_map(function($part) { return lcfirst($part); }, explode('\\', preg_replace('/Controller$/', '', $controllerClass)));
        
        $normalizedControllerName = implode(ActionToken::NAMESPACE_SEPARATOR, $nameParts);
        $tokenParams = [
            'controller' => $normalizedControllerName,
            'action'     => $actionName
        ];
        $token = new ActionToken($tokenParams);
        
        array_unshift($parameters, $token->toString());
        
        $url = $this->routeSet->urlFor($parameters);
        
        if (!$url) {
            array_shift($parameters);
            $parameters = array_merge($tokenParams, $parameters);
            $pairs  = [];
            foreach ($parameters as $key => $value) {
                $pairs[] = $key . '=>' . (is_scalar($value) ? '"' . $value . '"' : '(' . gettype($value) . ')');
            }
            throw new \Exception(sprintf(
                'No route matches [%s]',
                implode(', ', $pairs)
            ));
        }
        
        $pos = strpos($url, '?');
        if ($pos) {
            $url = substr($url, 0, $pos);
        }
        
        return $url;
    }
    
    protected function setUpRequest()
    {
        $this->parameters   = new TestParameters();
        $this->request  = new TestRequest($this->parameters);
        $this->session  = new Session();
        $this->routeSet = self::$application->routes();
    }
    
    protected function resolveControllerClass()
    {
        if ($this->controllerClass) {
            return $this->controllerClass;
        }
        
        # It is assumed that test class ends in Test
        return substr(get_called_class(), 0, -4);
    }
    
    protected function setCustomRequestParams(TestParameters $parameters, $method, array $customParams)
    {
        switch ($method) {
            case 'get':
            case 'post':
            case 'delete':
            case 'put':
            case 'patch':
                $setterName = 'set' . ucfirst($method) . 'Params';
                $parameters->$setterName($customParams);
                break;
            
            default:
                $parameters->setOtherVerbParams($customParams);
                break;
        }
    }
}
