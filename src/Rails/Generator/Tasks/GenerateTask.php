<?php
namespace Rails\Generator\Tasks;

use Rails\Console\Task\Task;
use Rails\Console\GeneratorsApplication;

/**
 * Initializes GeneratorsApplication
 */ 
class GenerateTask extends Task
{
    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->ignoreValidationErrors();
    }
    
    protected function configure()
    {
        $this
            ->setName('generate')
            ->setAliases(['g'])
            ->setDescription('Run generators')
            ->addArgument(
                'generator',
                'required',
                'Name of the generator'
            )
            ->addArgument(
                'arguments',
                ['optional', 'array'],
                'Arguments'
            );
        $this->ignoreValidationErrors = true;
    }
    
    protected function task()
    {
        $generatorsApp = new GeneratorsApplication($this->app);
        $generatorsApp->run();
    }
}
