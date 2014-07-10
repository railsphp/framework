<?php
namespace Rails\ActiveRecord\Associations;

use ReflectionClass;
use Rails\ActiveSupport\ParentMethods;
use Rails\ServiceManager\ServiceLocatorAwareTrait;

class Extractor
{
    use ServiceLocatorAwareTrait;
    
    /**
     * @param string $class The model's class that extends AR\Base
     */
    public function getAssociations($class)
    {
        // $class = get_class($model);
        $data = [];
        
        if (!isset($this->associations[$class])) {
            $assocsByType = $this->getAssociationsData($class);
        }
        
        foreach ($assocsByType as $type => $associations) {
            foreach ($associations as $assocName => $options) {
                if (is_int($assocName)) {
                    $assocName = $options;
                    $options   = [];
                }
                
                $data[$assocName] = $this->normalizeAssociationOptions($type, $assocName, $options, $class);
            }
        }
        
        return $data;
    }
    
    protected function getAssociationsData($class)
    {
        $parentMethods = new ParentMethods();
        
        $regex = '/.+Associations$/';
        
        $closures = $parentMethods->getClosures(
            $class,
            function($method, $currentClass) use ($regex) {
                return $method->getDeclaringClass() == $currentClass->getName() &&
                        (bool)preg_match($regex, $method->getName());
            },
            'Rails\ActiveRecord\Base'
        );
        
        $refl   = new ReflectionClass($class);
        $instance = $refl->newInstanceWithoutConstructor();
        $method = $refl->getMethod('associations');
        $method->setAccessible(true);
        
        $associations = $method->invoke($instance);
        
        foreach ($closures as $closure) {
            $associations = array_merge_recursive($associations, $closure->invoke($instance));
        }
        
        return $associations;
    }
    
    

    protected function normalizeAssociationOptions($type, $name, array $options, $class)
    {
        $options['type']    = $type;
        $inflector          = $this->getService('inflector');
        
        if (!isset($options['class'])) {
            switch ($type) {
                case 'hasMany':
                case 'hasAndBelongsToMany':
                    $options['class'] = ucfirst($inflector->singularize($name));
                    break;
                
                default:
                    $options['class'] = ucfirst($name);
                    break;
            }
        }
        
        switch ($type) {
            case 'belongsTo':
                if (!isset($options['foreignKey'])) {
                    $options['foreignKey'] = $inflector->underscore($name) . '_id';
                }
                break;
            
            case 'hasOne':
                if (!isset($options['foreignKey'])) {
                    $options['foreignKey'] = $inflector->singularize(
                        $class::tableName()
                    ) . '_id';
                }
                break;
            
            case 'hasMany':
                if (!isset($options['foreignKey'])) {
                    $options['foreignKey'] = $inflector->singularize(
                        $class::tableName()
                    ) . '_id';
                }
                break;
            
            case 'hasAndBelongsToMany':
                if (empty($options['joinTable'])) {
                    if (
                        $class::tableNamePrefix() &&
                        $class::tableNamePrefix() == $options['class']::tableNamePrefix()
                    ) {
                        $prefix = $class::tableNamePrefix();
                        $tableNames = [
                            substr($class::tableName(), strlen($prefix)),
                            substr($options['class']::tableName(), strlen($prefix))
                        ];
                    } else {
                        $prefix = null;
                        $tableNames = [ $class::tableName(), $options['class']::tableName() ];
                    }
                    
                    sort($tableNames);
                    
                    $joinTable = implode('_', $tableNames);
                    
                    if ($prefix) {
                        $joinTable = $prefix . $joinTable;
                    }
                    
                    $options['joinTable'] = $joinTable;
                }
                
                $names      = explode('\\', $class);
                $modelClass = array_pop($names);
                
                if (empty($options['foreignKey'])) {
                    $options['foreignKey'] = str_replace('/', '_', $inflector->underscore($modelClass)) . '_id';
                }
                if (empty($options['associationForeignKey'])) {
                    $options['associationForeignKey'] = $inflector->underscore($options['class']) . '_id';
                }
                break;
        }
        
        return $options;
    }
}
