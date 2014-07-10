<?php
namespace Rails\Routing\Exception;

class NotFoundException extends \Rails\Exception\RuntimeException implements ExceptionInterface
{
    protected $title    = "Routing Error";
    
    protected $status   = 404;
    
    protected $skipInfo = true;
}
