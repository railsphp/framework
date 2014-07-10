<?php
namespace Rails\ActionDispatch;

class ExceptionDispatcher
{
    public function dispatch(\Exception $e, $currentController, $application)
    {
        if ($e instanceof \Rails\Exception\Exception) {
            $status = $e->status();
        } else {
            $status = 500;
        }
        $application->response()->setStatus($status);
        
        $handlerClass = $application->config()['action_controller']['error_handler'];
        $handler      = new $handlerClass($application);
        
        $paths = $currentController->actionView()->lookupContext()->paths;
        if (!$paths) {
            $paths = $application->config()['paths']['app']->expand('views');
        }
        
        $handler->actionView()->lookupContext()->addPaths($paths);
        $handler->handleException($e);
    }
}
