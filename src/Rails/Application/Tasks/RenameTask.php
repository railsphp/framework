<?php
namespace Rails\Application\Tasks;

use Rails\Console\Task\Task;
use Rails\Toolbox\AppRenamer;

class RenameTask extends Task
{
    protected function configure()
    {
        $this
            ->setName('app:rename')
            ->setDescription('Rename new application.')
            ->addArgument(
                'name',
                'required',
                'New name of application.'
            );
        ;
    }
    
    protected function task()
    {
        $appName = $this->arg('name');
        $appRoot = $this->app->config()['paths']['root']->toString();
        
        AppRenamer::rename($appName, $appRoot);
        
        $this->output->writeln("Application renamed.");
    }
}
