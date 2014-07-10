<?php
namespace Rails\ActionDispatch\ErrorReporting;

use Rails\ActionView\ActionView;
use Rails\ActionView\Renderer\Renderer;
use Rails\ActionView\Helper\HelperSet;
use Rails\ActionView\Template\Assigns;
use Rails\ActionDispatch\Http\StatusCodes;
use Rails\Exception\ExceptionInterface as RailsException;
use Rails\Console\Console;

class Reporter
{
    const ERROR_NOTICE =
"%s %s<br/>
If you are the administrator of this website, then please read this web application's<br/>
log file and/or the web server's log file to find out what went wrong.";
    
    protected $application;
    
    public static function createReport(\Exception $exception)
    {
        $helperSet = new HelperSet();
        $presenter = new ExceptionPresenter($helperSet);
        $presenter->setObject($exception);
        $presenter->cli = PHP_SAPI == 'cli';
        
        return [
            'title'   => $presenter->title(),
            'message' => $presenter->message(),
            'trace'   => $presenter->trace(),
        ];
    }
    
    public function __construct($application)
    {
        $this->application = $application;
    }
    
    public function reportException(\Exception $exception)
    {
        if (PHP_SAPI == 'cli') {
            $this->reportExceptionCli($exception);
        } else {
            $this->reportExceptionHttp($exception);
        }
    }
    
    public function reportExceptionHttp(\Exception $exception)
    {
        if ($exception instanceof RailsException) {
            $status = $exception->status();
        } else {
            $status = 500;
        }
        
        $this->application->response()->setStatus($status);
        
        if ($this->application->config()['environment'] != 'production') {
            $contents = $this->createHtmlReport($exception);
        } else {
            $contents = $this->errorNotice($exception, $status);
        }
        
        $this->application->response()->setBody($contents);
    }
    
    protected function createHtmlReport($exception)
    {
        $actionView = new ActionView();
        $actionView->lookupContext()->addPath(__DIR__ . '/templates');
        
        $assigns = new Assigns([
            'application' => $this->application,
            'exception'   => $exception
        ]);
        
        $helperSet = new HelperSet();
        $helperSet->addHelper(new ReporterHelper($helperSet));
        $presenter = new ExceptionPresenter($helperSet);
        $helperSet->invoke('setPresenter', [$presenter]);
        
        $renderParams = [
            'partial'  => false,
            'layout'   => 'layout',
            'prefixes' => [],
            'details'  => [
                'locale'   => [],
                'handlers' => ['php'],
                'formats'  => [],
            ],
            'assigns'   => $assigns,
            'helperSet' => $helperSet
        ];
        $contents   = [];
        $contents[] = $this->renderException($exception, $actionView, $presenter, $renderParams);
        
        if ($previous = $exception->getPrevious()) {
            while ($previous) {
                $contents[] = $this->renderException($previous, $actionView, $presenter, $renderParams);
                $previous = $previous->getPrevious();
            }
        }
        
        return implode("\n", $contents);
    }
    
    protected function errorNotice($e, $status)
    {
        return sprintf(
            self::ERROR_NOTICE,
            $status,
            StatusCodes::getName($status)
        );
    }
    
    protected function renderException($exception, $actionView, $presenter, $renderParams)
    {
        $presenter->setObject($exception);
        return $actionView->renderTemplate('exception', $renderParams);
        
    }
    
    public function reportExceptionCli(\Exception $exception)
    {
        $report = self::createReport($exception);
        
        $c = new Console();
        $c->put();
        $c->put($report['title'],   'red');
        $c->put($report['message'], 'cyan');
        $c->put($report['trace'],   'light gray');
        $c->put();
    }
    
    public static function buildErrorException($errno, $errstr, $errfile, $errline, $errargs)
    {
        $errtype  = '';
        $basePath = realpath(__DIR__ . '/../..');
        
        switch ($errno) {
            case E_WARNING:
                $className = 'Warning';
                break;

            case E_NOTICE:
                $className = 'Notice';
                break;
                
            case E_DEPRECATED:
                $className = 'Deprecated';
                break;
            
            case E_RECOVERABLE_ERROR:
                $className = 'Catchable';
                break;

            case E_USER_NOTICE:
                $className = 'UserNotice';
                break;
                
            case E_USER_WARNING:
                $className = 'UserWarning';
                break;
                
            case E_USER_DEPRECATED:
                $className = 'UserDeprecated';
                break;

            default:
                /* When an "Unknown" error is triggered, the Loader isn't invoked for some reason.
                 * Hence the manual requires.
                 */
                require_once $basePath . '/Exception/ExceptionInterface.php';
                require_once $basePath . '/Exception/ReportableExceptionTrait.php';
                require_once $basePath . '/Exception/ErrorException.php';
                require_once $basePath . '/Exception/PHPError/ExceptionInterface.php';
                require_once $basePath . '/Exception/PHPError/UnknownException.php';
                $className = 'Unknown';
                $errtype = '[ErrNo '.$errno.']';
                break;
        }
        
        $class = 'Rails\Exception\PHPError\\' . $className . 'Exception';
        
        /* Workaround: Some errors generate an incomplete message
         * than ends in "on line 123 and defined", missing the part that tells
         * where was the function/method defined. So we're completing the message
         * here.
         */
        if (strpos($errstr, 'and defined') === strlen($errstr) - 11) {
            $errstr .= ' in ' . $errfile . ' on line ' . $errline;
        }
        
        if (strpos($errfile, $basePath) === 0) {
            $errfile = 'Rails' . substr($errfile, strlen($basePath));
        }
        
        $errorInfo = ['errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline, 'errargs' => $errargs];
        
        if ($errtype) {
            $errtype .= ': ';
        }
        
        $e = new $class(
            sprintf('%s%s ', $errtype, $errstr)
        );
        
        $e->setErrorInfo($errorInfo);
        
        return $e;
    }
}
