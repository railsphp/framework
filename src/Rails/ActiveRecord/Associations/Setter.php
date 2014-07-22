<?php
namespace Rails\ActiveRecord\Associations;

use Rails\ActiveRecord\Base;
use Rails\ActiveRecord\Associations\CollectionProxy;

class Setter
{
    public function set(Base $record, $name, $value)
    {
        if ($value !== null && !$value instanceof Base) {
            if (is_object($value)) {
                $message = sprintf(
                    "Must pass instance of Rails\ActiveRecord\Base as value, instance of %s passed",
                    get_class($value)
                );
            } else {
                $message = sprintf(
                    "Must pass either null or instance of Rails\ActiveRecord\Base as value, %s passed",
                    gettype($value)
                );
            }
            throw new Exception\InvalidArgumentException($message);
        }
        
        $options = $record->getAssociations()->get($name);
        
        switch ($options['type']) {
            case 'belongsTo':
                if ($value) {
                    $this->matchClass($value, $options['className']);
                    $value = $value->id();
                }
                
                $record->setAttribute($options['foreignKey'], $value);
                break;
            
            case 'hasOne':
                $foreignKey = $options['foreignKey'];
                
                if ($value) {
                    $this->matchClass($value, $options['className']);
                    $value->setAttribute($foreignKey, $record->id());
                }
                
                if ($record->isNewRecord()) {
                    return;
                }
                
                $oldValue = $record->getAssociation($name);
                
                if (
                    $value && $oldValue &&
                    $value->getAttribute($foreignKey) == $oldValue->getAttribute($foreignKey)
                ) {
                    return;
                }
                
                if ($oldValue) {
                    $oldValue->setAttribute($foreignKey, null);
                }
                
                
                if (!static::transaction(function() use ($name, $value, $oldValue) {
                    if ($value) {
                        if (!$value->save()) {
                            return false;
                        }
                    }
                    
                    if ($oldValue) {
                        if (!$oldValue->save()) {
                            return false;
                        }
                    }
                })) {
                    throw new RecordNotSavedException(
                        sprinf(
                            "Failed to save new associated %s",
                            strtolower(
                                $record::getService('inflector')->underscore($name)->humanize()
                            )
                        )
                    );
                }
                
                break;
        }
        
        return true;
    }
    
    protected function matchClass($object, $targetClass)
    {
        if (get_class($object) != $targetClass) {
            throw new TypeMissmatchException(
                sprintf(
                    "Expected instance of %s, got %s",
                    get_class($object)
                )
            );
        }
    }
}
