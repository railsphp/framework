<?php
namespace Rails\ActiveRecord\Schema;

use Closure;
use Zend\Db;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Sql\Ddl;
use Rails;
use Rails\ActiveRecord\ActiveRecord;

class Schema
{
    protected $adapter;
    
    protected $sql;
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->sql     = new Db\Sql\Sql($this->adapter);
    }
    
    public function sql()
    {
        return $this->sql;
    }
    
    public function adapter()
    {
        return $this->adapter;
    }
    
    public function createTable($tableName, $options = [], Closure $block = null)
    {
        if ($options && $options instanceof Closure) {
            $block   = $options;
            $options = [];
        }
        
        $createDdl = new Ddl\CreateTable($tableName);
        $td        = new TableDefinition($this, $createDdl);
        
        if (!empty($options['force'])) {
            $this->queryAdapter(
                new Ddl\DropTable($tableName)
            );
        }
        
        if (!isset($options['id']) || !empty($options['id'])) {
            $pk = isset($options['primaryKey']) ? $options['primaryKey'] : 'id';
            $td->primaryKey($pk);
        }
        
        if ($block) {
            $block($td);
        }
        
        $this->queryAdapter($createDdl);
    }
    
    public function addColumn($tableName, $columnName, $type, array $options = [])
    {
        $column = $this->getColumnDefinition($columnName, $type, $options);
        
        $ddl = new Ddl\AlterTable($tableName);
        $ddl->addColumn($column);
        
        $this->queryAdapter($ddl);
    }
    
    public function changeColumn($tableName, $columnName, $type, array $options = [])
    {
        $column = $this->getColumnDefinition($columnName, $type, $options);
        
        $ddl = new Ddl\AlterTable($tableName);
        $ddl->changeColumn($columnName, $column);
        
        $this->queryAdapter($ddl);
    }
    
    public function addIndex($tableName, $columnName, array $options = [])
    {
        if (!isset($options['name'])) {
            $options['name'] = '';
        }
        
        if (!empty($options['unique'])) {
            $index = new Ddl\Constraint\UniqueKey($columnName, $options['name']);
        } elseif (!empty($options['primaryKey'])) {
            $index = new Constraint\PrimaryKey($columnName);
        } else {
            $index = new Constraint\IndexKey($columnName);
        }
        
        $ddl = new Ddl\AlterTable($tableName);
        $ddl->addConstraint($index);
        
        $this->queryAdapter($ddl);
    }
    
    public function getColumnDefinition($name, $type, $options)
    {
        switch ($type) {
            case 'string':
            case 'varchar':
                # Default options.
                $options = array_merge([
                    'limit' => 255
                ], $options);
                
                $column = new Ddl\Column\Varchar($name, $options['limit']);
                break;
            
            case 'char':
                # Default options.
                $options = array_merge([
                    'limit' => 255
                ], $options);
                
                $column = new Ddl\Column\Char($name, $options['limit']);
                break;
            
            case 'integer':
                $column = new Ddl\Column\Integer($name);
                break;
            
            case 'datetime':
                $column = new Column\DateTime($name);
                break;
            
            case 'text':
                $column = new Column\Text($name);
                break;
            
            case 'boolean':
                $column = new Column\Boolean($name);
                break;
            
            // case 'primaryKey':
                
                // break;
            
            default:
                throw new Exception\RuntimeException(
                    sprintf("Unknown column type '%s'", $type)
                );
        }
        
        # Allow/disallow null, TRUE by default.
        $column->setNullable(!isset($options['null']) || !empty($options['null']));
        
        # Set default value.
        if (isset($options['default'])) {
            $column->setDefault($options['default']);
        }
        
        return $column;
    }
    
    public function tableExists($tableName)
    {
        $metadata = new Metadata($this->adapter);
        return in_array($tableName, $metadata->getTableNames());
    }
    
    public function execute()
    {
        return call_user_func_array([$this->adapter->getDriver()->getConnection(), 'executeSql'], func_get_args());
    }
    
    protected function queryAdapter($ddl)
    {
        $adapter = $this->adapter;
        $adapter->query(
            $this->sql->getSqlStringForSqlObject($ddl),
            $adapter::QUERY_MODE_EXECUTE
        );
    }
}
