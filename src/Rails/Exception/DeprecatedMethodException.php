<?php
namespace Rails\Exception;

class DeprecatedMethodException extends \BadMethodCallException implements ExceptionInterface
{
    use ReportableExceptionTrait;
}
