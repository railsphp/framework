<?php
namespace Rails\ActionController;

use Rails;
use Rails\ActionController\Base;

/**
 * Basic use:
 *  Create a class that extends this one.
 *  According to Exception or status, change the value of $template by
 *   overriding handle(). Although this isn't needed for basic handling;
 *   default setup should work fine.
 *  The system will render that template.
 */
abstract class ExceptionHandler extends Base
{
    protected $exception;
    
    protected $template = 'exception';
    
    public function handle()
    {
        switch ($this->response()->status()) {
            case 404:
                $this->template = '404';
                break;
            
            default:
                $this->template = '500';
                break;
        }
    }
    
    public function handleException(\Exception $e)
    {
        $this->exception = $e;
        $this->setLayout(false);
        $this->handle();
        if (!$this->isPerformed()) {
            $this->render(['action' => $this->template]);
        }
    }
}
