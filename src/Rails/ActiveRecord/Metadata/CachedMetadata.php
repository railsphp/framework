<?php
namespace Rails\ActiveRecord\Metadata;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Metadata\MetadataInterface;
use Zend\Db\Metadata\Object as ZfObject;
use Rails\ServiceManager\ServiceLocatorAwareTrait;

class CachedMetadata implements MetadataInterface
{
    use ServiceLocatorAwareTrait;
    
    const DEFAULT_SCHEMA = '__DEFAULT_SCHEMA__';
    
    protected $adapter;
    
    protected $defaultSchema;
    
    protected $schemas;
    
    protected $tableNames = [];
    
    protected $viewNames = [];
    
    protected $triggerNames = [];
    
    protected $tables   = [];
    
    protected $views    = [];
    
    protected $constraintKeys = [];
    
    protected $triggers = [];
    
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->defaultSchema = ($adapter->getCurrentSchema()) ?: self::DEFAULT_SCHEMA;
    }
    
    public function getSchemas()
    {
        if (null === $this->schemas) {
            $key = $this->cacheKey(
                $this->adapter->getDriver()->getConnection()->getName() . '.schemas'
            );
            $schemas = $this->cache()->read($key);
            if (null === $schemas) {
                $schemas = $this->metadata()->getSchemas();
                $this->cache()->write($key, $schemas);
            }
            $this->schemas = $schemas;
        }
        return $this->schemas;
    }

    public function getTableNames($schema = null, $includeViews = false)
    {
        $this->properSchema($schema);
        $this->loadTableNameData($schema);
        
        if ($includeViews) {
            $this->loadViewNameData($schema);
            return array_merge($this->tableNames[$schema], $this->viewNames[$schema]);
        }
        
        return $this->tableNames[$schema];
    }
    
    public function getTables($schema = null, $includeViews = false)
    {
        $this->properSchema($schema);
        if (!isset($this->tables[$schema])) {
            $this->tables[$schema] = [];
            foreach ($this->getTableNames($schema, $includeViews) as $tableName) {
                $this->tables[$schema][] = $this->getTable($tableName, $schema);
            }
        }
        return $this->tables[$schema];
    }
    
    public function getTable($tableName, $schema = null)
    {
        $this->properSchema($schema);
        $this->loadTableNameData($schema);
        
        if (!in_array($tableName, $this->tableNames[$schema])) {
            throw new \Exception('Table "' . $tableName . '" does not exist in schema ' . $schema);
        }
        
        $this->loadFromCache($schema, 'tb', $tableName);
        
        return $this->tables[$schema][$tableName];
    }

    public function getViewNames($schema = null)
    {
        $this->properSchema($schema);
        $this->loadViewNameData($schema);
        return $this->viewNames;
    }
    
    public function getViews($schema = null)
    {
        $this->properSchema($schema);
        if (!isset($this->views[$schema])) {
            $this->views[$schema] = [];
            foreach ($this->getViewNames($schema) as $viewName) {
                $this->views[$schema][] = $this->getView($viewName, $schema);
            }
        }
        return $this->views[$schema];
    }
    
    public function getView($viewName, $schema = null)
    {
        $this->properSchema($schema);
        $this->loadViewNameData($schema);
        
        if (!in_array($viewName, $this->viewNames[$schema])) {
            throw new \Exception('View "' . $viewName . '" does not exist in schema ' . $schema);
        }
        
        $this->loadFromCache($schema, 'vw', $viewName);
        
        return $this->views[$schema][$viewName];
    }

    public function getColumnNames($tableName, $schema = null)
    {
        $table = $this->getTable($tableName, $schema);
        return array_keys($table->getColumns());
    }
    
    public function getColumns($table, $schema = null)
    {
        return $this->getTable($table, $schema)->getColumns();
    }
    
    public function getColumn($columnName, $table, $schema = null)
    {
        $this->properSchema($schema);
        $columns = $this->getColumns($table, $schema);
        if (!isset($columns[$columnName])) {
            throw new \Exception(
                srptinf(
                    "Column '%s' was not found in table '%s' in schema '%s'",
                    $columnName,
                    $table,
                    $schema
                )
            );
        }
        return $columns[$columnName];
    }

    public function getConstraints($table, $schema = null)
    {
        return $this->getTable($table, $schema)->getConstraints();
    }
    
    public function getConstraint($constraintName, $table, $schema = null)
    {
        $this->properSchema($schema);
        $constraints = $this->getConstraints($table, $schema);
        if (!isset($constraints[$constraintName])) {
            throw new \Exception(
                srptinf(
                    "Constraint '%s' was not found in table '%s' in schema '%s'",
                    $constraintName,
                    $table,
                    $schema
                )
            );
        }
        return $constraints[$constraintName];
    }
    
    public function getConstraintKeys($constraint, $table, $schema = null)
    {
        $this->properSchema($schema);
        if (!isset($this->constraintKeys[$schema][$table][$constraint])) {
            $this->loadFromCache($schema, 'ck', $constraint, $table);
        }
        return $this->constraintKeys[$schema][$table][$constraint];
    }
    
    public function getTriggerNames($schema = null)
    {
        $this->properSchema($schema);
        $this->loadTriggerNameData($schema);
        return $this->triggerNames[$schema];
    }
    
    public function getTriggers($schema = null)
    {
        $this->properSchema($schema);
        if (!isset($this->triggers[$schema])) {
            $this->triggers[$schema] = [];
            foreach ($this->getTriggerNames($schema) as $triggerName) {
                $this->triggers[$schema][] = $this->getTrigger($triggerName, $schema);
            }
        }
        return $this->triggers[$schema];
    }
    
    public function getTrigger($triggerName, $schema = null)
    {
        $this->properSchema($schema);
        $this->loadTriggerNameData($schema);
        
        if (!in_array($triggerName, $this->triggerNames[$schema])) {
            throw new \Exception('Trigger "' . $triggerName . '" does not exist in schema ' . $schema);
        }
        
        $this->loadFromCache($schema, 'tr', $triggerName);
        
        return $this->triggers[$schema][$triggerName];
    }
    
    /**
     * $tableName is used by constraint keys.
     */
    protected function loadFromCache($schema, $itemType, $itemName, $tableName = null)
    {
        if ($tableName) {
            if (isset($this->constraintKeys[$schema][$tableName][$itemName])) {
                return;
            }
        } else {
            $prop = $this->keyToProperty($itemType);
            if (isset($this->{$prop}[$schema][$itemName])) {
                return;
            }
        }
        
        $key  = $this->cacheKey($schema . '.' . $itemType . '.' . $itemName);
        $data = $this->cache()->read($key);
        
        if (!$data) {
            $this->loadAndCache($schema, $itemType, $itemName, $key, $tableName);
        } else {
            switch ($itemType) {
                case 'tb':
                    $object = new ZfObject\TableObject($data['name']);
                    $this->setTableData($object, $data);
                    break;
                
                case 'vw':
                    $object = new ZfObject\ViewObject($data['name']);
                    $object->setViewDefinition($data['viewDefinition']);
                    $object->setCheckOption($data['checkPption']);
                    $object->setIsUpdatable($data['isUpdatable']);
                    $this->setTableData($object, $data);
                    break;
                
                case 'ck':
                    $object = new ZfObject\TriggerObject();
                    $object->setOrdinalPosition($data['ordinalPosition']);
                    $object->setReferencedTableSchema($data['referencedTableSchema']);
                    $object->setForeignKeyUpdateRule($data['updateRule']);
                    $object->setForeignKeyDeleteRule($data['deleteRule']);
                    $object->setReferencedTableName($data['referencedTableName']);
                    $object->setReferencedColumnName($data['referencedColumnName']);
                    break;
                
                case 'tr':
                    $object = new ZfObject\ConstraintKeyObject();
                    $object->setName($data['name']);
                    $object->setEventManipulation($data['eventManipulation']);
                    $object->setEventObjectCatalog($data['eventObjectCatalog']);
                    $object->setEventObjectSchema($data['eventObjectSchema']);
                    $object->setEventObjectTable($data['eventObjectTable']);
                    $object->setActionOrder($data['actionOrder']);
                    $object->setActionCondition($data['actionCondition']);
                    $object->setActionStatement($data['actionStatement']);
                    $object->setActionOrientation($data['actionOrientation']);
                    $object->setActionTiming($data['actionTiming']);
                    $object->setActionReferenceOldTable($data['actionReferenceOldTable']);
                    $object->setActionReferenceNewTable($data['actionReferenceNewTable']);
                    $object->setActionReferenceOldRow($data['actionReferenceOldRow']);
                    $object->setActionReferenceNewRow($data['actionReferenceNewRow']);
                    $object->setCreated($data['created']);
                    break;
            }
            
            $this->setItem($object, $schema, $itemType, $itemName, $tableName);
        }
    }
    
    protected function setTableData(ZfObject\AbstractTableObject $table, array $data)
    {
        $columns = [];
        foreach ($data['columns'] as $col) {
            $c = new Object\ColumnObject($col['name'], $col['tableName'], $col['schemaName']);
            
            $c->setType($col['type']);
            $c->setOrdinalPosition($col['ordinalPosition']);
            $c->setColumnDefault($col['columnDefault']);
            $c->setIsNullable($col['isNullable']);
            $c->setDataType($col['dataType']);
            $c->setCharacterMaximumLength($col['characterMaximumLength']);
            $c->setCharacterOctetLength($col['characterOctetLength']);
            $c->setNumericPrecision($col['numericPrecision']);
            $c->setNumericUnsigned($col['numericUnsigned']);
            $c->setErratas($col['errata']);
            
            $columns[$col['name']] = $c;
        }
        $table->setColumns($columns);
        
        $constraints = [];
        foreach ($data['constraints'] as $cons) {
            $c = new ZfObject\ConstraintObject(
                $cons['name'],
                $cons['tableName'],
                $cons['schemaName']
            );
            
            $c->setType($cons['type']);
            $c->setColumns($cons['columns']);
            $c->setReferencedTableSchema($cons['referencedTableSchema']);
            $c->setReferencedTableName($cons['referencedTableName']);
            $c->setReferencedColumns($cons['referencedColumns']);
            $c->setMatchOption($cons['matchOption']);
            $c->setUpdateRule($cons['updateRule']);
            $c->setDeleteRule($cons['deleteRule']);
            $c->setCheckClause($cons['checkClause']);
            
            $constraints[] = $c;
        }
        $table->setConstraints($constraints);
    }
    
    protected function setItem($item, $schema, $itemType, $itemName, $tableName = null)
    {
        if (!$tableName) {
            $prop = $this->keyToProperty($itemType);
            $this->{$prop}[$schema][$itemName] = $item;
        } else {
            if (!isset($this->constraintKeys[$schema])) {
                $this->constraintKeys[$schema] = [
                    $tableName => []
                ];
            } elseif (!isset($this->constraintKeys[$schema][$tableName])) {
                $this->constraintKeys[$schema][$tableName] = [];
            }
            $this->constraintKeys[$schema][$tableName][$itemName] = $item;
        }
    }
    
    # Used to load non-cached data.
    protected $storer;
    
    protected function loadAndCache($schema, $itemType, $itemName, $key, $tableName)
    {
        list ($object, $data) = $this->storer()->load($schema, $itemType, $itemName, $tableName);

        $this->cache()->write($key, $data);
        $this->setItem($object, $schema, $itemType, $itemName);
    }
    
    protected function storer()
    {
        if (!$this->storer) {
            $this->storer = new Source\Cache\Storer($this->adapter);
        }
        return $this->storer;
    }
    
    protected function cache()
    {
        return $this->getService('rails.cache');
    }
    
    protected function cacheKey($suffix)
    {
        return 'rails.ar.md.' . $suffix;
    }
    
    protected function properSchema(&$schema)
    {
        if (null === $schema) {
            $schema = $this->defaultSchema;
        }
        return $schema;
    }
    
    protected function keyToProperty($key)
    {
        switch ($key) {
            case 'tb':
                return 'tables';
            
            case 'vw':
                return 'views';
            
            case 'ck':
                return 'constraintKeys';
            
            case 'tr':
                return 'triggers';
        }
    }
    
    protected function loadTableNameData($schema)
    {
        $this->loadNamesData('tableNames', $schema);
    }
    
    protected function loadViewNameData($schema)
    {
        $this->loadNamesData('viewNames', $schema);
    }
    
    protected function loadTriggerNameData($schema)
    {
        $this->loadNamesData('triggerNames', $schema);
    }
    
    protected function loadNamesData($type, $schema)
    {
        if (!isset($this->{$type}[$schema])) {
            $key = $this->cacheKey(
                $this->adapter->getDriver()->getConnection()->getName() . '.' . $schema . '.' . $type
            );
            $names = $this->cache()->read($key);
            if (null === $names) {
                $names = $this->storer()->metadata()->{'get' . $type }($schema, false);
                $this->cache()->write($key, $names);
            }
            $this->{$type}[$schema] = $names;
        }
    }
}
