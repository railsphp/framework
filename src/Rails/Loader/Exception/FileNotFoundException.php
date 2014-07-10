<?php
namespace Rails\Loader\Exception;

require_once __DIR__ . '/ExceptionInterface.php';

class FileNotFoundException extends \RuntimeException implements ExceptionInterface
{
}
