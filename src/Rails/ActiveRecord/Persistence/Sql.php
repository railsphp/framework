<?php
namespace Rails\ActiveRecord\Persistence;

use Zend\Db\Sql as ZfSql;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Exception as AdapterException;
use Rails\ActiveRecord\Base;

class Sql
{
    public function insert(Base $record)
    {
        $baseClass = get_class($record);
        $sql = $baseClass::connection()->table($baseClass::tableName());
        return $sql->insert($record->attributes());
    }
    
    public function update(Base $record)
    {
        if (!$record->hasChanged()) {
            return true;
        }
        
        if ($record->getAttributes()->isAttribute('updated_at')) {
            $record->setAttribute('updated_at', date('Y-m-d H:i:s'));
        } elseif ($record->getAttributes()->isAttribute('updated_on')) {
            $record->setAttribute('updated_on', date('Y-m-d H:i:s'));
        }
        
        $values = [];
        foreach (array_keys($record->changedAttributes()) as $attrName) {
            $values[$attrName] = $record->getAttribute($attrName);
        }
        
        if ($this->updateRecord($record, $values)) {
            $record->getAttributes()->dirty()->changesApplied();
        }
    }
    
    public function updateColumns(Base $record, array $columnsValuesPairs)
    {
        if ($record->isNewRecord()) {
            throw new Exception\RuntimeException(
                "Can't update columns on a new record"
            );
        }
        
        return $this->updateRecord($record, $columnsValuesPairs);
    }
    
    public function delete(Base $record)
    {

        $sql = $this->buildSqlRelation($record);
        return $sql->delete();
    }
    
    protected function updateRecord(Base $record, array $columnsValuesPairs)
    {
        $sql = $this->buildSqlRelation($record);
        return $sql->update($columnsValuesPairs);
    }
    
    protected function buildSqlRelation(Base $record)
    {
        $baseClass = get_class($record);
        
        $sql = $baseClass::connection()->table($baseClass::tableName());
        if ($baseClass::table()->columnExists($baseClass::primaryKey())) {
            $sql->where([$baseClass::primaryKey() => $record->id()]);
        } else {
            $identifiers = $this->getRecordIdentifiers($record);
            $sql->where($identifiers);
        }
        
        return $sql;
    }
    
    protected function getRecordIdentifiers(Base $record)
    {
        $uniques     = $this->getUniqueColumns($record);
        $identifiers = [];
        
        if ($uniques) {
            /* Attempt to identify record by its unique columns.
             */
            foreach ($uniques as $attrName) {
                $identifiers[$attrName] = $record->getAttribute($attrName);
            }
        } else {
            /* Attempt to identify record by all its attributes.
             * This **could** be dangerous.
             */
            $identifiers = $record->attributes();
        }
        
        return $identifiers;
    }
    
    protected function getUniqueColumns($baseClass)
    {
        $keys = [];
        $tableName = $baseClass::tableName();
        
        foreach ($baseClass::table()->metadata()->getConstraints($tableName) as $constraint) {
            if ($constraint->getType() == 'UNIQUE') {
                $keys = array_merge($keys, $constraint->getColumns());
            }
        }
        
        return array_unique($keys);
    }
}
