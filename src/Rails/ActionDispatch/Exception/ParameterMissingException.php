<?php
namespace Rails\ActionDispatch\Exception;

class ParameterMissingException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $status = 400;
    
    protected $skipInfo = true;
}