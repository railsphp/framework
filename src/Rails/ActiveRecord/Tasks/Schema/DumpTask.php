<?php
namespace Rails\ActiveRecord\Tasks\Schema;

use Rails\ActiveRecord\Base as SqlBase;
use Rails\Console\Task\Task;
use Rails\ActiveRecord\Schema\Migration\Exporter;

class DumpTask extends Task
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
        $file       = $this->app->config()['paths']['root']->expand('db', 'schema.sql');
        $connection = SqlBase::adapter()->getDriver()->getConnection();
        $exporter   = new Exporter();
        $dump       = $exporter->export($connection);
        file_put_contents($file, $dump);
    }
}
