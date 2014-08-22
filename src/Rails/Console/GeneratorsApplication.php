<?php
namespace Rails\Console;

use Symfony\Component\Console\Input\ArgvInput;
use Rails\ActionMailer\Generators\Mailer\MailerGenerator;
use Rails\ActiveRecord\Generators\Model\ModelGenerator;
use Rails\ActiveRecord\Generators\Migration\MigrationGenerator;
use Rails\ActionController\Generators\Controller\ControllerGenerator;
use Rails\ActionView\Generators\Helper\HelperGenerator;
use Rails\Test\Generators\Test\TestGenerator;
use Rails\Test\Generators\Factory\FactoryGenerator;

class GeneratorsApplication
{
    protected $app;
    
    protected $consoleApp;

    public function __construct($app = null)
    {
        $this->app = $app;
        
        $this->consoleApp = new Application();
        $this->consoleApp->setCatchExceptions(false);
        
        if ($app) {
            $this->consoleApp->setApp($app);
        }
        
        # add tasks
        $this->consoleApp->addTasks([
            new MailerGenerator(),
            new ModelGenerator(),
            new MigrationGenerator(),
            new ControllerGenerator(),
            new HelperGenerator(),
            new TestGenerator(),
            new FactoryGenerator()
        ]);
    }
    
    public function run()
    {
        $argv = $_SERVER['argv'];
        array_shift($argv);
        $this->consoleApp->run(new ArgvInput($argv));
    }
}
