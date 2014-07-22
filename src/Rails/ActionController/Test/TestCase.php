<?php
namespace Rails\ActionController\Test;

use Closure;
use Rails\ActionDispatch\Http\Response;
use Rails\ActionDispatch\Http\Session;
use Rails\ActionDispatch\Test\Http\Parameters as TestParameters;
use Rails\ActionDispatch\Test\Http\Request as TestRequest;
use Rails\ActionDispatch\Test\Constraint;
use Rails\Test\TestCase as BaseTestCase;
use Rails\Routing\ActionToken;

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
    
    protected $params;
    
    protected $request;
    
    protected $session;
    
    protected $routeSet;
    
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
        $this->ensureRouteExists($controllerClass, $actionName, $customParams);
        
        $response = new Response();
        
        $this->setCustomRequestParams($this->params, $method, $customParams);
        
        $this->controller = new $controllerClass(
            $this->request,
            $response,
            $this->params,
            $this->session,
            $this->routeSet
        );
        
        $this->controller->runAction($actionName);
    }
    
    public function assigns()
    {
        return $this->controller->assigns();
    }
    
    public static function assertResponse($response, $message = '')
    {
        $constraint = new Constraint\Response();
        self::assertThat($response, $constraint, $message);
    }
    
    protected function ensureRouteExists($controllerClass, $actionName, array $params)
    {
        $nameParts = array_map(function($part) { return lcfirst($part); }, explode('\\', preg_replace('/Controller$/', '', $controllerClass)));
        
        $normalizedControllerName = implode(ActionToken::NAMESPACE_SEPARATOR, $nameParts);
        $tokenParams = [
            'controller' => $normalizedControllerName,
            'action'     => $actionName
        ];
        $token = new ActionToken($tokenParams);
        
        array_unshift($params, $token->toString());
        
        $url = $this->routeSet->urlFor($params);
        
        if (!$url) {
            array_shift($params);
            $params = array_merge($tokenParams, $params);
            $pairs  = [];
            foreach ($params as $key => $value) {
                $pairs[] = $key . '=>' . (is_scalar($value) ? '"' . $value . '"' : '(' . gettype($value) . ')');
            }
            throw new \Exception(sprintf(
                'No route matches [%s]',
                implode(', ', $pairs)
            ));
        }
    }
    
    protected function setUpRequest()
    {
        $this->params   = new TestParameters();
        $this->request  = new TestRequest($this->params);
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
    
    protected function setCustomRequestParams(TestParameters $params, $method, array $customParams)
    {
        switch ($method) {
            case 'get':
            case 'post':
            case 'delete':
            case 'put':
            case 'patch':
                $setterName = 'set' . ucfirst($method) . 'Params';
                $params->$setterName($customParams);
                break;
            
            default:
                $params->setOtherVerbParams($customParams);
                break;
        }
    }
}
