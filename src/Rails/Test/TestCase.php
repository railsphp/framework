<?php
namespace Rails\Test;

use Closure;
use Rails\Application\Base as Application;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    protected static $application;
    
    public static function setApplication(Application $application)
    {
        self::$application = $application;
    }
    
    /**
     * Evaluates that the result of $difference has changed after running $process.
     */
    public static function assertDifference(Closure $difference, Closure $process)
    {
        $original = $difference();
        $process();
        return self::assertNotSame($original, $difference());
    }
    
}
