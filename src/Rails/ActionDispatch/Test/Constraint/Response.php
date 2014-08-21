<?php
namespace Rails\ActionDispatch\Test\Constraint;

use Rails\ActionController\Base as Controller;
use Rails\ActionDispatch\Http\StatusCodes;

class Response extends \PHPUnit_Framework_Constraint
{
    protected $controller;
    
    protected static $RANGED_STATUS_TYPES = [
        'success',
        'redirect',
        'missing',
        'error'
    ];
    
    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }
    
    public function matches($other)
    {
        $responseStatus = $this->controller->response()->status();
        if (null === $responseStatus) {
            $responseStatus = 200;
        } elseif (!is_int($responseStatus)) {
            $responseStatus = StatusCodes::getCode($responseStatus);
        }
        
        if (is_string($other)) {
            if (in_array($other, self::$RANGED_STATUS_TYPES)) {
                return $this->matchRangedStatus($other, $responseStatus);
            } else {
                $otherCode = StatusCodes::getCode($other);
                return $otherCode == $responseStatus;
            }
        } elseif (is_int($other)) {
            return $other == $responseStatus;
        } else {
            throw new \InvalidArgumentException(sprintf(
                "First argument must be either string or int, %s passed",
                gettype($other)
            ));
        }
    }
    
    public function toString()
    {
        return "is the response status";
    }
    
    protected function matchRangedStatus($name, $status)
    {
        switch ($name) {
            case 'success':
                $min = 200;
                $max = 299;
                break;
            
            case 'redirect':
                $min = 300;
                $max = 399;
                break;
            
            case 'missing':
                return $status == 404;
            
            case 'error':
                $min = 500;
                $max = 599;
                break;
            
            default:
                throw new \InvalidArgumentException(sprintf(
                    "Unknown ranged status name %s",
                    $name
                ));
        }
        
        return $status >= $min && $status <= $max;
    }
}
