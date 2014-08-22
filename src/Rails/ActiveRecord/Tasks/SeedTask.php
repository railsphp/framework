<?php
namespace Rails\ActiveRecord\Tasks;

use Rails\ActiveRecord\Base as SqlBase;
use Rails\Console\Task\Task;
use Rails\ActiveRecord\Migration\Migrator;

class SeedTask extends Task
{
    protected function configure()
    {
        $this
            ->setName('db:seed')
            ->setDescription('Seed the database with initial data.');
        ;
    }
    
    protected function task()
    {
        $seedsFile = $this->app->config()['paths']['root']->expand('db', 'seeds.php');
        if (is_file($seedsFile)) {
            require $seedsFile;
        } else {
            $this->output->writeLine(sprintf(
                "Seeds file not found (%s)",
                $seedsFile
            ));
        }
    }
}
