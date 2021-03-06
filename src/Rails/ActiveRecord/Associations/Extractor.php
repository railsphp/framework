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
    
    /**
     * Under the following case, when specifing both the polymorphic interface
     * (the "as" option) and the className to use, the foreign key will resolve
     * using the "as" option:
     *
     * ```
     * 'hasOne' => [
     *     'foo' => [
     *         'className' => 'FooClass',
     *         'as' => 'fooable' // foreign key will be "fooable_id"
     *     ]
     * ]
     * ```
     *
     * This means that, to follow the default conventions, the "as" option must
     * be underscored.
     */
    protected function normalizeAssociationOptions($type, $name, array $options, $class)
    {
        $options['type']    = $type;
        $inflector          = $this->getService('inflector');
        
        if (!isset($options['className'])) {
            switch ($type) {
                case 'hasMany':
                case 'hasAndBelongsToMany':
                    $options['className'] = ucfirst($inflector->singularize($name));
                    break;
                
                case 'belongsTo':
                    if (!empty($options['polymorphic'])) {
                        break;
                    }
                
                default:
                    $options['className'] = ucfirst($name);
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
                    if (!empty($options['as'])) {
                        $options['foreignKey'] = $options['as'] . '_id';
                    } else {
                        $options['foreignKey'] = $inflector->singularize(
                            $class::tableName()
                        ) . '_id';
                    }
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
                        $class::tableNamePrefix() == $options['className']::tableNamePrefix()
                    ) {
                        $prefix = $class::tableNamePrefix();
                        $tableNames = [
                            substr($class::tableName(), strlen($prefix)),
                            substr($options['className']::tableName(), strlen($prefix))
                        ];
                    } else {
                        $prefix = null;
                        $tableNames = [ $class::tableName(), $options['className']::tableName() ];
                    }
                    
                    sort($tableNames);
                    
                    $joinTable = implode('_', $tableNames);
                    
                    if ($prefix) {
                        $joinTable = $prefix . $joinTable;
                    }
                    
                    $options['joinTable'] = $joinTable;
                }
                
                if (empty($options['foreignKey'])) {
                    $options['foreignKey'] =
                        str_replace('/', '_', $inflector->underscore($class)) . '_id';
                }
                if (empty($options['associationForeignKey'])) {
                    $options['associationForeignKey'] =
                        str_replace('/', '_', $inflector->underscore($options['className'])) . '_id';
                }
                break;
        }
        
        return $options;
    }
}
