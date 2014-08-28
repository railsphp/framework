<?php
namespace Rails\ActiveRecord\Associations;

use Rails\ActiveRecord\Base;
use Rails\ActiveRecord\Associations\CollectionProxy;

class Loader
{
    public function load(Base $record, $name, array $options)
    {
        switch ($options['type']) {
            case 'hasOne':
            case 'hasMany':
                if (isset($options['through'])) {
                    return $this->loadThrough($record, $name, $options);
                }
                
            case 'belongsTo':
            case 'hasAndBelongsToMany':
                return $this->{'load' . ucfirst($options['type'])}($record, $name, $options);
            
            default:
                throw new Exception\BadMethodCallException(
                    sprintf("Trying to load unknown association type %s", $options['type'])
                );
        }
    }
    
    protected function loadThrough($record, $name, array $options)
    {
        $throughName = $options['through'];
        return $record->getAssociations($throughName)->load($name);
    }
    
    protected function loadHasOne($record, $name, array $options)
    {
        if (empty($options['className'])) {
            $options['className'] = $record::getService('inflector')->camelize($name)->toString();
        }
        
        $query = $this->buildQuery($options);
        $query->where([$options['foreignKey'] => $record->id()]);
        $first = $query->first();
        
        if ($first) {
            return $first;
        }
        
        return false;
    }
    
    protected function loadBelongsTo($record, $name, array $options)
    {
        if (empty($options['className'])) {
            if (empty($options['polymorphic'])) {
                $options['className'] = ucfirst($name);
            } else {
                $assocType = $name . 'Type';
                $options['className'] = $record->$assocType();
            }
        }
        
        $foreignKey = !empty($options['foreignKey']) ? $options['foreignKey'] : Rails::services()->get('inflector')->underscore($name) . '_id';
        
        $query = $this->buildQuery($options);
        $fKey  = $record->getAttribute($foreignKey);
        
        if ($fKey) {
            return $query->where(['id' => $fKey])->first() ?: false;
        }
        
        return false;
    }
    
    /**
     * @param array $options    Additional parameters to customize the query for the association
     */
    protected function loadHasMany($record, $name, $options)
    {
        if (empty($options['className'])) {
            $options['className'] = $record::getService('inflector')->singularize($name)->camelize()->toString();
        }
        
        $query = $this->buildQuery($options, 'hasMany', $record);
        $query->where([$options['foreignKey'] => $record->id()]);
        
        return $query;
    }
    
    protected function loadHasAndBelongsToMany($record, $name, array $options)
    {
        $query = $this->buildQuery($options, 'hasAndBelongsToMany', $record);
        $query->where([$options['joinTable'] . '.' . $options['foreignKey']  => $record->id()]);
        $query->join(
            $options['joinTable'],
            $options['className']::tableName() . '.' . $options['className']::primaryKey() . ' = ' . $options['joinTable'] . '.' . $options['associationForeignKey']
        );
        
        return $query;
    }
    
    protected function buildQuery(array $options, $proxyKind = null, $record = null)
    {
        if ($proxyKind) {
            $query = new CollectionProxy($options['className'], $proxyKind, $record, $options['foreignKey']);
        } else {
            $query = $options['className']::all();
        }
        
        # options[0], if present, it's an anonymous function to customize the relation.
        # The relation object is passed to that function.
        if (isset($options[0])) {
            $lambda = array_shift($options);
            $lambda($query);
        }
        
        return $query;
    }
}
