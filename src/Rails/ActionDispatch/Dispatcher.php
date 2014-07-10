<?php
namespace Rails\ActionDispatch;

use Rails\Routing\ActionToken;
use Rails\ActionController\MainController;

class Dispatcher
{
    protected $token;
    
    public function __construct(ActionToken $token)
    {
        $this->token = $token;
    }

    public function __invoke($application)
    {
        $this->dispatch($application);
    }
    
    public function dispatch($application)
    {
        $controllerClass = ucfirst($application->request()->controller()) . 'Controller';
        $controller      = new $controllerClass($application);
        
        $controller->actionView()->lookupContext()->addPath(
            $application->config()['paths']['app']->expand('views')
        );
        
        try {
            $controller->runAction($application->request()->action());
        } catch (\Exception $e) {
            if (!$application->config()['consider_all_requests_local']) {
                try {
                    $dispatcher = new ExceptionDispatcher();
                    $dispatcher->dispatch($e, $controller, $application);
                    $application->getService('log')->exception($e);
                } catch (\Exception $e) {
                    $application->getService('log')->warning("Error during failsafe response:");
                    throw $e;
                }
            } else {
                throw $e;
            }
        }
    }
}
