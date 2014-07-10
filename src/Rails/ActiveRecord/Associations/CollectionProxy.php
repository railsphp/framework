<?php
namespace Rails\ActiveRecord\Associations;

use Rails\ActiveRecord\Relation;

class CollectionProxy extends Relation
{
    protected $owner;
    
    protected $foreignKey;
    
    protected $kind;
    
    public function __construct($modelClass, $kind, $owner, $foreignKey)
    {
        $this->kind       = $kind;
        $this->owner      = $owner;
        $this->foreignKey = $foreignKey;
        parent::__construct($modelClass);
    }
    
    /**
     * Add records to the association. The owner record must be
     * persisted before adding records.
     * Pass an array or records or many records.
     */
    public function add($records/*...$records*/)
    {
        if (!$this->owner->isPersisted()) {
            throw new Exception\RuntimeException(
                # TODO: terminar mensaje:
                # ...adding children to its $type association
                "Owner record must be persisted before adding children"
            );
        }
        
        $this->load();
        
        if (!is_array($records)) {
            $records = func_get_args();
        }
        
        if (!$records) {
            return true;
        }
        
        $className = get_class(reset($records));
        
        return $className::transaction(function() use ($records, $className) {
            
            $idsForUpdate = [];
            
            foreach ($records as $record) {
                if ($record->hasChanged()) {
                    if (!$record->updateAttribute($this->foreignKey, $this->owner->id())) {
                        # If a record won't save, cancel the whole process.
                        return false;
                    }
                } else {
                    $idsForUpdate[] = $record->id();
                }
            }
            
            if ($idsForUpdate) {
                $className::connection()
                    ->table($className::tableName())
                    ->where([$className::primaryKey() => $idsForUpdate])
                    ->update([$this->foreignKey => $this->owner->id()]);
            }
            
            $this->records->merge($records);
        });
    }
    
    protected function addRecord($record)
    {
        return $record->updateAttribute($this->foreignKey, $this->owner->id());
    }
}
