<?php
namespace Rails\ActionDispatch;

use Rails\ActiveSupport\MimeTypes;
use Rails\Routing\ActionToken;
use Rails\ActionController\MainController;

class Dispatcher
{
    protected $token;
    
    protected $controller;
    
    public function __construct(ActionToken $token)
    {
        $this->token = $token;
    }

    public function __invoke($application)
    {
        $this->dispatch($application);
    }

    public function controller()
    {
        return $this->controller;
    }
    
    public function dispatch($application)
    {
        $application->response()->setCookieJar($application->request()->cookieJar());
        $application->session()->start(
            // $application->config()['session']['name'],
            // $application->config()['session']['id']
        );
        $controllerClass = $application->routes()->requestRoute()->to()->toClass() . 'Controller';
        $controller      = new $controllerClass(
            $application->request(),
            $application->response(),
            $application->parameters(),
            $application->session(),
            $application->routes()
        );
        
        $this->controller = $controller;
        
        $controller->actionView()->lookupContext()->addPath(
            $application->config()['paths']['app']->expand('views')
        );
        
        try {
            $controller->runAction($application->request()->action());
            $this->setDefaultContentType($controller);
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
    
    protected function setDefaultContentType($controller)
    {
        if (!$controller->response()->contentType()) {
            $controller->response()->setContentType(
                MimeTypes::getMimeType($controller->request()->format())
            );
        }
    }
}
