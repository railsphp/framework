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
            # OPTIMIZE:
            switch ($controller->request()->format()) {
                case 'html':
                    $contentType = 'text/html';
                    break;
                case 'json':
                    $contentType = 'application/json';
                    break;
                case 'xml':
                    $contentType = 'application/xml';
                    break;
                case 'js':
                    $contentType = 'application/javascript';
                    break;
                default:
                    $contentType = '';
                    break;
            }
            
            if ($contentType) {
                $controller->response()->setContentType($contentType);
            }
        }
    }
}
