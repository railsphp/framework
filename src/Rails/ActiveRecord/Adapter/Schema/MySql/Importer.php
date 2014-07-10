<?php
namespace Rails\ActiveRecord\Adapter\Schema\MySql;

use Rails\ActiveRecord\Adapter\Schema\AbstractImporter;

class Importer extends AbstractImporter
{
    public function importFile($file)
    {
        return $this->import(file($file));
    }
    
    public function import($lines)
    {
        $queries   = [];
        $query     = [];
        $delimiter = '';
        
        foreach ($lines as $line) {
            if ($delimiter) {
                if ($line == $delimiter) {
                    $queries[] = implode("\n", $query);
                    $query = [];
                } else {
                    $query[] = $line;
                }
            } elseif (preg_match('/^\s*DELIMITER ([^\;].*?)$/m', $line, $m)) {
                $queries[] = implode("\n", $query);
                $query = [];
                $delimiter = $m[1];
            } elseif ($line == 'DELIMITER ;') {
                $delimiter = '';
            } else {
                $query[] = $line;
                
                if (substr($line, -1) == ';') {
                    $queries[] = implode("\n", $query);
                    $query = [];
                }
            }
        }
        
        foreach ($queries as $query) {
            # There may be empty lines added as queries, it is safe to skip them.
            if ($query) {
                $this->connection->execute($query);
            }
        }
    }
}
