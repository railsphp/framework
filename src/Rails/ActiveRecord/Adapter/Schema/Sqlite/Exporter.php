<?php
namespace Rails\ActiveRecord\Adapter\Schema\Sqlite;

use Rails\ActiveRecord\Adapter\Schema\AbstractExporter;

class Exporter extends AbstractExporter
{
    /**
     * Very basic exporter. It takes the "sql" column from
     * sqlite_master, and since there's not a standarized syntax by
     * taking SQL that way, it tries to turn queries into single-line
     * queries by removing all line breaks so it'll be easier to import
     * the queries.
     */
    public function export($schemaName = null)
    {
        $dump = [];
        $sql  =
            "SELECT type, name, sql
            FROM sqlite_master
            WHERE type != 'index' AND name != 'sqlite_sequence'
            ORDER BY type";
        
        $rows = $this->connection->selectAll($sql);
        foreach ($rows as $row) {
            $dump[] = preg_replace('/\v/', ' ', $row['sql']);
        }
        
        return implode(";\n\n", $dump) . ";\n";
    }
}
