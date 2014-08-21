<?php
namespace Rails\Test;

use Closure;
use Rails\Application\Base as Application;
use Rails\FactoryGirl\FactoryGirl;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Application
     */
    protected static $application;
    
    /**
     * @var FactoryGirl
     */
    protected static $factoryGirl;
    
    public static function setApplication(Application $application)
    {
        self::$application = $application;
    }
    
    public static function setFactoryGirl(FactoryGirl $factoryGirl)
    {
        self::$factoryGirl = $factoryGirl;
    }
    
    public static function application()
    {
        return self::$application;
    }
    
    public static function factoryGirl()
    {
        return self::$factoryGirl;
    }
    
    /**
     * Alias of factoryGirl()
     */
    public static function factory()
    {
        return self::$factoryGirl;
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
