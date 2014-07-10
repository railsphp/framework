<?php
namespace Rails\ActiveRecord\Metadata\Source\Cache;

use Rails\ActiveRecord\Metadata\Metadata;

class Storer
{
    protected $metadata;
    
    public function __construct($adapter)
    {
        $this->metadata = new Metadata($adapter);
    }
    
    public function metadata()
    {
        return $this->metadata;
    }
    
    public function load($schema, $itemType, $itemName, $tableName = null)
    {
        switch ($itemType) {
            case 'tb':
                $object = $this->metadata->getTable($itemName, $schema);
                $data = $this->getTableData($object);
                $data['type'] = 'table';
                break;
            
            case 'vw':
                $object = $this->metadata->getView($itemName, $tableName, $schema);
                $data = $this->getTableData($object);
                
                $data['viewDefinition'] = $object->getViewDefinition();
                $data['checkOption']    = $object->getCheckOption();
                $data['isUpdatable']    = $object->getIsUpdatable();
                $data['type'] = 'view';
                break;
            
            case 'ck':
                $object = $this->metadata->getConstraintKey($itemName, $schema);
                $data = $this->getConstraintKeyData($key);
                break;
            
            case 'tr':
                $object = $this->metadata->getTrigger($itemName, $schema);
                $data = $this->getConstraintKeyData($key);
                break;
        }
        
        return [$object, $data];
    }
    
    protected function getTableData($table)
    {
        $tableData = [
            'name' => $table->getName()
        ];
        
        $columns   = [];
        foreach ($table->getColumns() as $c) {
            $columns[] = [
                'type' => $c->type(),
                'name' => $c->getName(),
                'tableName' => $c->getTableName(),
                'schemaName' => $c->getSchemaName(),
                'ordinalPosition' => $c->getOrdinalPosition(),
                'columnDefault' => $c->getColumnDefault(),
                'isNullable' => $c->getIsNullable(),
                'dataType' => $c->getDataType(),
                'characterMaximumLength' => $c->getCharacterMaximumLength(),
                'characterOctetLength' => $c->getCharacterOctetLength(),
                'numericPrecision' => $c->getNumericPrecision(),
                'numericUnsigned' => $c->getNumericUnsigned(),
                'errata' => $c->getErratas()
            ];
        }
        $tableData['columns'] = $columns;
        
        $constraints = [];
        foreach ($table->getConstraints() as $c) {
            $constraints[] = [
                'name' => $c->getName(),
                'tableName' => $c->getTableName(),
                'schemaName' => $c->getSchemaName(),
                'type' => $c->getType(),
                'columns' => $c->getColumns(),
                'referencedTableSchema' => $c->getReferencedTableSchema(),
                'referencedTableName' => $c->getReferencedTableName(),
                'referencedColumns' => $c->getReferencedColumns() ?: [],
                'matchOption' => $c->getMatchOption(),
                'updateRule' => $c->getUpdateRule(),
                'deleteRule' => $c->getDeleteRule(),
                'checkClause' => $c->getCheckClause()
            ];
        }
        $tableData['constraints'] = $constraints;
        
        return $tableData;
    }
    
    protected function getConstraintKeyData($key)
    {
        return [
            'columnName' => $key->getColumnName(),
            'ordinalPosition' => $key->getOrdinalPosition(),
            'positionInUniqueConstraint' => $key->getPositionInUniqueConstraint(),
            'referencedTableSchema' => $key->getReferencedTableSchema(),
            'referencedTableName' => $key->getReferencedTableName(),
            'referencedColumnName' => $key->getReferencedColumnName(),
            'foreignKeyUpdateRule' => $key->getForeignKeyUpdateRule(),
            'foreignKeyDeleteRule' => $key->getForeignKeyDeleteRule()
        ];
    }
    
    protected function getTriggerData($trigger)
    {
        return [
            'name' => $trigger->getName(),
            'eventmanipulation' => $trigger->getEventmanipulation(),
            'eventobjectcatalog' => $trigger->getEventobjectcatalog(),
            'eventobjectschema' => $trigger->getEventobjectschema(),
            'eventobjecttable' => $trigger->getEventobjecttable(),
            'actionorder' => $trigger->getActionorder(),
            'actioncondition' => $trigger->getActioncondition(),
            'actionstatement' => $trigger->getActionstatement(),
            'actionorientation' => $trigger->getActionorientation(),
            'actiontiming' => $trigger->getActiontiming(),
            'actionreferenceoldtable' => $trigger->getActionreferenceoldtable(),
            'actionreferencenewtable' => $trigger->getActionreferencenewtable(),
            'actionreferenceoldrow' => $trigger->getActionreferenceoldrow(),
            'actionreferencenewrow' => $trigger->getActionreferencenewrow(),
            'created' => $trigger->getCreated()
        ];
    }
}
