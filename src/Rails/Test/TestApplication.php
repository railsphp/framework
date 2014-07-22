<?php
namespace Rails\Test;

use DirectoryIterator;
use PHPUnit_TextUI_TestRunner as TestRunner;
use Rails\Application\Base as Application;

class TestApplication
{
    protected $application;
    
    public function __construct(Application $application)
    {
        $this->setUpEnvironment($application);
        $this->application = $application;
    }
    
    public function run($className)
    {
        $rootPath = str_replace('\\', '/', $this->application->config()['paths']['root']);
        
        // $suiteClassName = implode('/', array_slice(explode('/', $filePath), 2));
        // $className;
        
        $reflection = new \ReflectionClass($className);
        
        $testRunner = new TestRunner();
        $testRunner->run($reflection);
    }
    
    protected function setUpEnvironment(Application $application)
    {
        $application->configLoader()->loadEnvironmentConfig('test');
        $testDirPath = $application->config()['paths']['root']->expand('test');
        $testDir     = new DirectoryIterator($testDirPath);
        
        $loader = $application->getService('loader');
        foreach ($testDir as $file) {
            if (!$file->isDot() && $file->isDir()) {
                $loader->addPath($file->getRealPath());
            }
        }
    }
}
