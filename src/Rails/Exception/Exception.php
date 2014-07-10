<?php
namespace Rails\Exception;

class Exception extends \Exception implements ExceptionInterface
{
    use ReportableExceptionTrait;
}
