<?php
namespace Rails\ActionDispatch\ErrorReporting;

use Rails;
use Rails\Exception\ExceptionInterface as RailsException;
use Rails\Exception\PHPError\ExceptionInterface as PHPError;

class ExceptionPresenter extends \Rails\ActionView\Presenter
{
    public $cli;
    
    public function title()
    {
        $e = $this->object;
        $errorScope = $this->findErrorScope($e);
        
        if ($e instanceof RailsException && $e->title()) {
            if ($e instanceof PHPError) {
                if ($errorScope) {
                    $title = $e->title() . ' in ' . $errorScope;
                } else {
                    $title = $e->title();
                }
            } else {
                $title = $e->title();
            }
        } else {
            if ($errorScope) {
                $title = get_class($e) . ' in ' . $errorScope;
            } else {
                $title = get_class($e) . ' thrown';
            }
        }
        
        return $this->cli ? '** ' . $title . ' **' : $title;
    }
    
    public function message()
    {
        if ($this->cli) {
            return $this->object->getMessage();
        } else {
            return $this->h($this->object->getMessage());
        }
    }
    
    public function trace()
    {
        $trace = str_replace(
            [Rails::path(), Rails::root() . '/'],
            ['Rails', ''],
            $this->object->getTraceAsString()
        );
        
        return $this->cli ? $trace : $this->h($trace);
    }
    
    public function errorContext()
    {
        $e = $this->object;
        
        $contextArgs = null;
        
        if ($e instanceof PHPError) {
            if (!empty($e->errorInfo()['errargs'])) {
                $contextArgs = $e->errorInfo()['errargs'];
            }
        } else {
            if (isset($trace[0]['args'])) {
                $contextArgs = $trace[0]['args'];
            }
        }
        
        if ($contextArgs) {
            ob_start();
            var_dump($contextArgs);
            $context = ob_get_clean();
        } else {
            $context = '';
        }
        
        return $context;
    }
    
    protected function prettyPath($path)
    {
        if (strpos($path, Rails::path()) === 0) {
            return 'Rails' . substr($path, strlen(Rails::path()));
        } elseif (strpos($path, Rails::root()) === 0) {
            return substr($path, strlen(Rails::root()));
        } else {
            return $path;
        }
    }
    
    protected function findErrorScope($e)
    {
        $tr = $e->getTrace();
        
        foreach ($this->traceSkippers() as $skipper) {
            if (true === $skipper($tr)) {
                array_shift($tr);
            }
        }
        
        if (isset($tr[0]['class']) && isset($tr[0]['function']) && isset($tr[0]['type'])) {
            if ($tr[0]['type'] == '->') {
                $type = '#';
            } else {
                $type = $tr[0]['type'];
            }
            $errorScope = $tr[0]['class'] . $type . $tr[0]['function'] . '';
        } elseif (!$e instanceof PHPError && isset($tr[0]['function'])) {
            $errorScope = $tr[0]['function'] . '';
        } else {
            $errorScope = '';
        }
        
        return $errorScope;
    }
    
    /**
     * These functions will be used to skip lines in the trace,
     * just to give the title of the exception a more accurate
     * scope (the function/method where the error occured).
     * If the validation returns true, a line will be skipped.
     * This is particulary useful when the exception was thrown by
     * the error handler, or the like.
     */
    protected function traceSkippers()
    {
        return [
            function ($trace) {
                if (
                    isset($trace[0]['class'])        &&
                    isset($trace[0]['function'])     &&
                    $trace[0]['class']    == 'Rails\ActionDispatch\ErrorReporting\Reporter' &&
                    $trace[0]['function'] == 'reportError'
                ) {
                    return true;
                }
            },
            
            function ($trace) {
                if (
                    isset($trace[0]['class'])        &&
                    isset($trace[0]['function'])     &&
                    $trace[0]['class']    == 'Rails\Application\Base' &&
                    $trace[0]['function'] == 'errorHandler'
                ) {
                    return true;
                }
            },
            
            function ($trace) {
                if (
                    isset($trace[0]['class'])        &&
                    isset($trace[0]['function'])     &&
                    $trace[0]['class']    == 'Rails\ActiveRecord\Base' &&
                    $trace[0]['function'] == '__get'
                ) {
                    return true;
                }
            }
        ];
    }
}
