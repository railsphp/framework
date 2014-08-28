<?php
namespace Rails\Console;

use Rails\Generator\Tasks\GenerateTask;
use Rails\Application\Tasks\RenameTask;
use Rails\Assets\Tasks\CompileTask;
use Rails\ActiveRecord\Tasks\MigrateTask;
use Rails\ActiveRecord\Tasks\CreateDatabaseTask;
use Rails\ActiveRecord\Tasks\SeedTask;
use Rails\ActiveRecord\Tasks\Schema\DumpTask;
use Rails\Test\Tasks\TestTask;

class TaskApplication
{
    /**
     * @var \Rails\Application\Base
     */
    protected $app;
    
    /**
     * @var Application
     */
    protected $consoleApp;

    public function __construct($app)
    {
        $this->app = $app;
        
        $this->consoleApp = new Application();
        $this->consoleApp->setApp($app);
        $this->consoleApp->setCatchExceptions(false);
        
        $this->consoleApp->addTasks([
            new GenerateTask(),
            new Boris\InitializeTask(),
            new CompileTask(),
            new MigrateTask(),
            new CreateDatabaseTask(),
            new SeedTask(),
            new DumpTask(),
            new TestTask(),
            new RenameTask()
        ]);
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
