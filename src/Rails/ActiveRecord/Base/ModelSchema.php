<?php
namespace Rails\ActiveRecord\Base;

use Zend\Db\Metadata;
use Rails\ActiveRecord\Exception;

class ModelSchema
{
    protected static $metadatas = [];
    
    protected $tableName;
    
    /**
     * @var Metadata
     */
    protected $metadata;
    
    protected $cacheStore;
    
    public function __construct($tableName, Metadata\MetadataInterface $metadata, $cacheStore = null)
    {
        $this->tableName = $tableName;
        if ($cacheStore) {
            $this->setCacheStore($cacheStore);
        }
        
        $this->setMetadata($metadata);
    }
    
    public function setCacheStore($cacheStore)
    {
        $this->cacheStore = $cacheStore;
    }
    
    public function setMetadata(Metadata\MetadataInterface $metadata)
    {
        $this->metadata = $metadata;
        if (!$this->tableExists()) {
            throw new Exception\RuntimeException(
                sprintf('Couldn\'t find table \'%s\'', $this->tableName)
            );
        }
    }
    
    public function tableName()
    {
        return $this->tableName;
    }
    
    public function tableExists()
    {
        return in_array($this->tableName, $this->metadata->getTableNames());
    }
    
    public function getColumn($name)
    {
        return $this->metadata->getColumn($name, $this->tableName());
    }
    
    public function columns()
    {
        return $this->metadata->getColumns($this->tableName());
    }
    
    public function columnsHash()
    {
        $hash = [];
        foreach ($this->columns() as $column) {
            $hash[$column->getName()] = $column;
        }
        return $hash;
    }
    
    public function columnNames()
    {
        return $this->metadata->getColumnNames($this->tableName);
    }
    
    public function columnExists($columnName)
    {
        return in_array($columnName, $this->columnNames());
    }
    
    public function columnDefaults()
    {
        $defaults = [];
        foreach ($this->columns() as $column) {
            $default = $column->getColumnDefault();
            if ($default !== null) {
                /* Single quote is trimed out of the default value due to
                 * SQLite. Perhaps it is needed to be selective here.
                 */
                $defaults[$column->getName()] = trim($default, "'");
            }
        }
        return $defaults;
    }
}
