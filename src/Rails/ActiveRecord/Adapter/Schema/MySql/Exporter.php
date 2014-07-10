<?php
namespace Rails\ActiveRecord\Adapter\Schema\MySql;

use Rails\ActiveRecord\Adapter\Schema\AbstractExporter;

class Exporter extends AbstractExporter
{
    public function export($schemaName = null)
    {
        if ($schemaName) {
            $this->connection->execute("USE " . $schemaName);
        }
        
        $tableStmts = [];
        $sql = "SHOW TABLES";
        $tables = $this->connection->select($sql);
        $autoIncrementRegex = '/\s?AUTO_INCREMENT=\d+/';
        
        foreach ($tables as $table) {
            $table = array_shift($table);
            
            $sql = "SHOW CREATE TABLE `" . $table . "`";
            $row = $this->connection->selectRow($sql);
            $stmt = $row['Create Table'];
            
            if ($constraints = $this->extractConstraints($stmt)) {
                $this->constraints[$table] = $constraints;
            }
            
            # Remove Auto increment attribute.
            $stmt = preg_replace($autoIncrementRegex, '', $stmt);
            
            # Add trailing semicolon to table statements.
            $tableStmts[] = $stmt . ';';
        }
        
        $constraintStmts = [];
        foreach ($this->constraints as $tableName => $statements) {
            $sql = $statements;
            $constraintStmts[] = "ALTER TABLE `" . $tableName . "`\n  " . implode(",\n  ", $sql) . ';';
        }
        
        $sql = "SHOW TRIGGERS";
        $triggers = $this->connection->select($sql);
        
        if ($triggers) {
            $triggerStmts = [
                "DELIMITER //"
            ];
            
            foreach ($triggers as $trg) {
                $sql = [];
                $sql[] = "CREATE TRIGGER `" . $trg['Trigger']
                        . "` " . $trg['Timing'] . " "
                        . $trg['Event'] . ' ON `' . $trg['Table'] . '`';
                $sql[] = "FOR EACH ROW";
                $sql[] = $trg['Statement'];
                $sql[] = '//';
                $triggerStmts[] = implode("\n", $sql);
            }
            
            $triggerStmts[] = "DELIMITER ;";
        } else {
            $triggerStmts = [];
        }
        
        $dump = '';
        $dump .= implode("\n\n", $tableStmts);
        $dump .= "\n\n";
        $dump .= implode("\n\n", $constraintStmts);
        $dump .= "\n\n";
        $dump .= implode("\n\n", $triggerStmts);
        
        return $dump;
    }
}
