<?php
namespace Rails\ActionDispatch\ErrorReporting;

use Rails\Exception\ExceptionInterface as RailsException;
use Rails\Exception\PHPError\ExceptionInterface as PHPError;

class ReporterHelper extends \Rails\ActionView\Helper
{
    public function skipInfo($e)
    {
        return $e instanceof RailsException && $e->skipInfo();
    }
    
    public function pageTitle($e)
    {
        if ($e->getPrevious()) {
            $i = 1;
            $prev = $e;
            while ($prev = $prev->getPrevious()) {
                $i++;
            }
            return $i . ' Exceptions thrown';
        } else {
            if ($e instanceof PHPError) {
                return "PHP Error";
            } else {
                return "Exception thrown";
            }
        }
    }
}
