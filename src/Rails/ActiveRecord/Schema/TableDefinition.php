<?php
namespace Rails\ActiveRecord\Schema;

use Zend\Db\Sql\Ddl\Column as ZfColumn;
use Zend\Db\Sql\Ddl\Constraint as ZfConstraint;
use Rails\ActiveRecord\Exception;

class TableDefinition
{
    protected $schema;
    
    /**
     * @var Zend\Db\Sql\Ddl\CreateTable
     */
    protected $table;
    
    public function __construct(Schema $schema, \Zend\Db\Sql\Ddl\CreateTable $table)
    {
        $this->schema = $schema;
        $this->table  = $table;
    }
    
    /**
     * Adds a column
     */
    public function column($name, $type, array $options = [])
    {
        $column = $this->schema->getColumnDefinition($name, $type, $options);
        $this->table->addColumn($column);
        return $this;
    }
    
    public function timestamps(array $options = [])
    {
        $this->column('created_at', 'datetime', $options);
        $this->column('updated_at', 'datetime', $options);
        return $this;
    }
    
    public function recoverable($name = 'deleted_at', $type = 'datetime', array $options = [])
    {
        $this->column($name, $type, $options);
        return $this;
    }
    
    public function string($name, $limit = 255, $type = 'varchar', array $options = [])
    {
        $this->column($name, $type, array_merge($options, ['limit' => $limit]));
        return $this;
    }
    
    public function text($name, array $options = [])
    {
        $this->column($name, 'text', $options);
        return $this;
    }
    
    public function integer($name, array $options = [])
    {
        $this->column($name, 'integer', $options);
        return $this;
    }
    
    public function float($name, array $options = [])
    {
        $this->column($name, 'float', $options);
        return $this;
    }
    
    public function decimal($name, array $options = [])
    {
        $this->column($name, 'decimal', $options);
        return $this;
    }
    
    public function datetime($name, array $options = [])
    {
        $this->column($name, 'datetime', $options);
        return $this;
    }
    
    public function timestamp($name, array $options = [])
    {
        $this->column($name, 'timestamp', $options);
        return $this;
    }
    
    public function time($name, array $options = [])
    {
        $this->column($name, 'time', $options);
        return $this;
    }
    
    public function date($name, array $options = [])
    {
        $this->column($name, 'date', $options);
        return $this;
    }
    
    public function binary($name, array $options = [])
    {
        $this->column($name, 'binary', $options);
        return $this;
    }
    
    public function boolean($name, array $options = [])
    {
        $this->column($name, 'boolean', $options);
        return $this;
    }
    
    public function primaryKey($name, $type = 'primary_key', array $options = [])
    {
        $driverName = $this->schema->adapter()->getDriver()->getConnection()->getDriverName();
        $column = new Column\PrimaryKey($name, $driverName);
        
        switch ($driverName) {
            case 'Mysql':
                $this->table->addConstraint(
                    new ZfConstraint\PrimaryKey($name)
                );
                break;
        }
        
        $this->table->addColumn($column);
        
        return $this;
    }
    
    public function index($columnName, array $options = [])
    {
        $this->table->addConstraint(
            new Constraint\IndexKey($columnName)
        );
        return $this;
    }
}
