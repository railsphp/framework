<?php
namespace Rails\Console\Boris;

use Rails\Console\Task\Task;

class InitializeTask extends Task
{
    protected function configure()
    {
        $this
            ->setName('console')
            ->setAliases(['c'])
            ->setDescription('Run Boris (CLI)');
    }
    
    protected function task()
    {
        $boris = new Boris();
        $boris->start();
    }
}
