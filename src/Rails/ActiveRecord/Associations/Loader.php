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
            case 'belongsTo':
            case 'hasMany':
            case 'hasAndBelongsToMany':
                return $this->{'load' . ucfirst($options['type'])}($record, $name, $options);
            
            default:
                throw new Exception\BadMethodCallException(
                    sprintf("Trying to load unknown association type %s", $options['type'])
                );
        }
    }
    
    protected function loadHasOne($record, $name, array $options)
    {
        if (empty($options['class'])) {
            $options['class'] = $record::getService('inflector')->camelize($name)->toString();
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
        empty($options['class']) && $options['class'] = ucfirst($name);
        
        $foreignKey = !empty($options['foreignKey']) ? $options['foreignKey'] : Rails::services()->get('inflector')->underscore($name) . '_id';
        
        if ($fKey = $record->getAttribute($foreignKey)) {
            return $options['class']::where(['id' => $fKey])->first() ?: false;
        }
        
        return false;
    }
    
    /**
     * @param array $options    Additional parameters to customize the query for the association
     */
    protected function loadHasMany($record, $name, $options)
    {
        if (empty($options['class'])) {
            $options['class'] = $record::getService('inflector')->singularize($name)->camelize()->toString();
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
            $options['class']::tableName() . '.' . $options['class']::primaryKey() . ' = ' . $options['joinTable'] . '.' . $options['associationForeignKey']
        );
        
        return $query;
    }
    
    protected function buildQuery(array $options, $proxyKind = null, $record = null)
    {
        if ($proxyKind) {
            $query = new CollectionProxy($options['class'], $proxyKind, $record, $options['foreignKey']);
        } else {
            $query = $options['class']::getRelation();
        }
        
        # options[0], if present, it's an anonymous function to customize the relation.
        # The function is binded to the relation object.
        if (isset($options[0])) {
            $lambda = array_shift($options);
            $lambda = $lambda->bindTo($query);
            $lambda();
        }
        
        return $query;
    }
}
