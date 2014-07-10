<?php
namespace Rails\Exception;

class BadMethodCallException extends \BadMethodCallException implements ExceptionInterface
{
    use ReportableExceptionTrait;
}
