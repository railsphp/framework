<?php
namespace Rails\Exception\PHPError;

use Rails\Exception\ErrorException;

abstract class AbstractException extends ErrorException implements ExceptionInterface
{
    protected $errorInfo;
    
    public function errorInfo()
    {
        return $this->errorInfo;
    }
    
    public function setErrorInfo(array $errorInfo)
    {
        $this->errorInfo = $errorInfo;
    }
}
