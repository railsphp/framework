<?php
namespace Rails\ActiveRecord\Tasks\Schema;

use Rails\ActiveRecord\Base as SqlBase;
use Rails\Console\Task\Task;
use Rails\ActiveRecord\Schema\Migration\Exporter;
// use Rails\ActiveRecord\Migration\Migrator;

class Dump extends Task
{
    protected function configure()
    {
        $this
            ->setName('db:schema:dump')
            ->setDescription('Export database schema.');
        ;
    }
    
    protected function task()
    {
        // $dumper = new \Rails\ActiveRecord\Schema\Dumper(
            // \Rails\ActiveRecord\ActiveRecord::connection()
        // );
        // $dumper->export(\Rails::root() . '/db/schema.sql');
        $file       = $this->app->config()['paths']['root']->expand('db', 'schema.sql');
        $connection = SqlBase::adapter()->getDriver()->getConnection();
        $exporter   = new Exporter();
        $dump       = $exporter->export($connection);
        file_put_contents($file, $dump);
        
        // $filesDir = $this->app->config()['paths']['root']->expand('db');
        // $migrator = new Migrator($adapter, $filesDir);
        // $migrator->run();
    }
}
