<?php
namespace Rails\ActiveRecord\Tasks;

use Rails\ActiveRecord\Base as SqlBase;
use Rails\Console\Task\Task;
use Rails\ActiveRecord\Migration\Migrator;

class Migrate extends Task
{
    protected function configure()
    {
        $this
            ->setName('db:migrate')
            ->setDescription('Run pending migrations.');
        ;
    }
    
    protected function task()
    {
        $adapter  = SqlBase::adapter();
        $filesDir = $this->app->config()['paths']['root']->expand('db');
        $migrator = new Migrator($adapter, $filesDir);
        $migrator->run();
    }
}
