<?php
namespace Rails\Console;

// use Symfony\Component\Console\Application as ConsoleApp;
use Rails\Console\Application as ConsoleApp;
use Symfony\Component\Console\Input\ArgvInput;

class GeneratorsApplication
{
    protected $app;
    
    protected $consoleApp;

    public function __construct($app = null)
    {
        $this->app = $app;
        
        $this->consoleApp = new ConsoleApp();
        $this->consoleApp->setCatchExceptions(false);
        
        if ($app) {
            $this->consoleApp->setApp($app);
        }
        
        # add tasks
        $this->consoleApp->addTask(new \Rails\ActiveRecord\Generators\Model\ModelGenerator());
        $this->consoleApp->addTask(new \Rails\ActiveRecord\Generators\Migration\MigrationGenerator());
        $this->consoleApp->addTask(new \Rails\ActionController\Generators\Controller\ControllerGenerator());
    }
    
    public function run()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        $this->consoleApp->run(new ArgvInput($argv));
    }
}
