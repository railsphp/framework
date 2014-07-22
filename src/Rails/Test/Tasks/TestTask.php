<?php
namespace Rails\Test\Tasks;

use Rails\Console\Task\Task;
use Rails\Test\TestApplication;

class TestTask extends Task
{
    protected function configure()
    {
        $this
            ->setName('test')
            ->setDescription('Run tests')
            ->addArgument(
                'class_name',
                'required',
                'Name of class to test (e.g. Api/UserTest).'
            );
        ;
    }
    
    protected function task()
    {
        $testClass = str_replace('/', '\\', $this->arg('class_name'));
        
        $testApp = new TestApplication($this->app);
        $testApp->run($testClass);
    }
}
