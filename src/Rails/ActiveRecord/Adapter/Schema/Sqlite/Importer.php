<?php
namespace Rails\ActiveRecord\Adapter\Schema\Sqlite;

use Rails\ActiveRecord\Adapter\Schema\AbstractImporter;

class Importer extends AbstractImporter
{
    public function importFile($file)
    {
        return $this->import(file($file));
    }
    
    public function import($queries)
    {
        foreach ($queries as $query) {
            $query = trim($query);
            if ($query) {
                $this->connection->execute(trim($query, ';'));
            }
        }
    }
}
