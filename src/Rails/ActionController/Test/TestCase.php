<?php
namespace Rails\ActionController\Test;

use Closure;
use Rails\Application\Base as Application;
use Rails\ActionController\Http\Response;
use Rails\ActionDispatch\Test\Constraint;

abstract class TestCase extends \PHPUnit_Framework_TestCase
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
    
    /**
     * @var Application
     */
    protected $application;
    
    public function setApplication(Application $application)
    {
        $this->application = $application;
    }
    
    public function get($actionName, array $customParams = [], array $session = [])
    {
        $this->runAction('get', $actionName, $customParams, $session);
    }
    
    public function runAction($method, $actionName, array $customParams = [], array $session = [])
    {
        $params   = new Http\Parameters();
        $request  = new Http\Request($params);
        $response = new Response();
        $session  = new Session();
        $routeSet = $this->application->routeSet();
        
        $this->setCustomRequestParams($params, $method, $customParams);
        
        $controllerClass = $this->resolveControllerClass();
        $this->controller = new $controllerClass(
            $request,
            $response,
            $params,
            $session,
            $routeSet
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
    
    public static function assertDifference(Closure $difference, Closure $process)
    {
        $original = $difference();
        $process();
        return $this->assertNotSame($original, $difference());
    }
    
    protected function resolveControllerClass()
    {
        if ($this->controllerClass) {
            return $this->controllerClass;
        }
        
        # It is assumed that test class ends in Test
        return substr(get_called_class(), 0, -4);
    }
    
    protected function setCustomRequestParams(Parameters $params, $method, array $customParams)
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
