<?php
namespace Rails\ActiveRecord\Tasks;

use Rails\ActiveRecord\Base as SqlBase;
use Rails\Console\Task\Task;
use Rails\ActiveRecord\Schema\Migration\Importer;

class Create extends Task
{
    protected function configure()
    {
        $this
            ->setName('db:create')
            ->setDescription('Create database using the schema.sql file.');
        ;
    }
    
    protected function task()
    {
        $sqlFile = $this->app->config()['paths']['root']->expand('db', 'schema.sql');
        if (!is_file($sqlFile)) {
            $this->output->write("File db/schema.sql not found");
            return;
        }
        
        $connection = SqlBase::adapter()->getDriver()->getConnection();
        $importer   = new Importer();
        $importer->import($connection, $sqlFile);
    }
}
