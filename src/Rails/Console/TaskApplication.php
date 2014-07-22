<?php
namespace Rails\Console;

use Rails\Generator\Tasks\GenerateTask;

class TaskApplication
{
    protected $app;
    
    protected $consoleApp;

    public function __construct($app)
    {
        $this->app = $app;
        
        $this->consoleApp = new Application();
        $this->consoleApp->setApp($app);
        $this->consoleApp->setCatchExceptions(false);
        
        $this->consoleApp->addTask(new GenerateTask());
        $this->consoleApp->addTask(new Boris\InitializeTask());
        $this->consoleApp->addTask(new \Rails\Assets\Tasks\CompileTask());
        $this->consoleApp->addTask(new \Rails\ActiveRecord\Tasks\Migrate());
        $this->consoleApp->addTask(new \Rails\ActiveRecord\Tasks\Create());
        $this->consoleApp->addTask(new \Rails\ActiveRecord\Tasks\Seed());
        $this->consoleApp->addTask(new \Rails\ActiveRecord\Tasks\Schema\Dump());
        $this->consoleApp->addTask(new \Rails\Test\Tasks\TestTask());
    }
    
    public function run()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        
        if (!$argv) {
            echo "[CLI: No arguments passed. Use --list or -h]\n\n";
        } else {
            $this->consoleApp->run();
        }
    }
}
