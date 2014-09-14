<?php
namespace Rails\ActionDispatch;

use Rails\Application\Base as Application;
use Rails\Routing\ActionToken;

class ActionDispatch
{
    protected $application;
    
    public function __construct(Application $application)
    {
        $this->application = $application;
    }
    
    public function dispatch($method, $path)
    {
        $app = $this->application;
        
        if (strtolower($method) == 'post' && isset($_POST['_method'])) {
            $method = $_POST['_method'];
        }
        
        $app->getService('log')->setRequest($app->request());
        
        $routeParams = $app->routes()->match(
            $path,
            $method
            // $app->request()->path(),
            // $app->request()->method()
        );
        
        if (!$routeParams) {
            # Route not found.
            throw new \Rails\Routing\Exception\NotFoundException(
                sprintf(
                    'No route matches [%s] "%s"',
                    $method,
                    $path
                    // $app->request()->method(),
                    // $app->request()->path()
                )
            );
        }
        
        list ($route, $routeVars) = $routeParams;

        if ($route->controller() == ':controller') {
            $route->setProperController(
                lcfirst($app->getService('inflector')
                    ->camelize($routeVars['controller'])->toString())
            );
        }
        if ($route->action() == ':action') {
            $route->setProperAction(
                lcfirst($app->getService('inflector')
                    ->camelize($routeVars['action'])->toString())
            );
        }
        
        $app->parameters()->setRouteVars($routeVars);
        
        ActionToken::setRoute($route);
        
        $app->routes()->setRequestRoute($route);
        $app->request()->setRequestRoute($route);
        
        $endPoint = $route->endPoint();
        
        call_user_func(
            $endPoint,
            $app
        );
        
        $app->response()->commit();
        return $endPoint;
        // $app->response()->commit();
    }
}
