<?php
namespace Rails\Log;

use Zend\Log\Logger as ZendLogger;
use Rails\ActionDispatch\Request;
use Rails\ActionDispatch\ErrorReporting\Reporter;

class Logger extends ZendLogger
{
    const NONE = 8;
    
    /**
     * @var Request
     */
    protected $request;
    
    protected $priorities = array(
        self::EMERG  => 'EMERGENCY',
        self::ALERT  => 'ALERT',
        self::CRIT   => 'CRITICAL',
        self::ERR    => 'ERROR',
        self::WARN   => 'WARNING',
        self::NOTICE => 'NOTICE',
        self::INFO   => 'INFO',
        self::DEBUG  => 'DEBUG',
        self::NONE   => 'NONE',
    );
    
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    
    public function emergency($message, $extra = [])
    {
        return $this->emerg($message, $extra);
    }
    
    public function critical($message, $extra = [])
    {
        return $this->crit($message, $extra);
    }
    
    public function error($message, $extra = [])
    {
        return $this->err($message, $extra);
    }
    
    public function warning($message, $extra = [])
    {
        return $this->warn($message, $extra);
    }
    
    public function none($message)
    {
        return $this->log(self::NONE, $message);
    }
    
    public function vars(/*...$vars*/)
    {
        ob_start();
        foreach (func_get_args() as $var) {
            var_dump($var);
        }
        $message  = ob_get_clean();
        $message .= "\n";
        return $this->none($message);
    }
    
    /**
     * Additional text can be passed through $options['extraMessages'].
     */
    public function exception(\Exception $e, array $options = [])
    {
        $message = $this->createExceptionMessages($e);
        return $this->message($message);
    }
    
    /**
     * Adds date-time and request data, if any.
     */
    public function message($err)
    {
        return $this->none($this->buildErrorMessage($err));
    }
    
    protected function createExceptionMessages($exception)
    {
        $message = [];
        while ($exception) {
            $report    = Reporter::createReport($exception);
            $message[] = implode("\n", $report);
            $exception = $exception->getPrevious();
        }
        
        return implode("\n", $message);
    }
    
    protected function buildErrorMessage($err, $requestInfo = true)
    {
        if ($requestInfo) {
            $request = ' ' . $this->buildRequestInfo();
        } else {
            $request = '';
        }
        
        $message  = date('[d-M-Y H:i:s T]') . $request . "\n";
        $message .= $err;
        $message  = trim($message);
        $message .= "\n";
        return $message;
    }
    
    protected function buildRequestInfo()
    {
        if (PHP_SAPI == 'cli') {
            $info = '[cli]';
        } elseif ($this->request) {
            $info = '[' . $this->request->remoteIp() . '] ' . $this->request->method() . ' ' . $this->request->fullPath();
        } else {
            $info = '';
        }
        return $info;
    }
}
