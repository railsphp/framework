<?php
namespace Rails\ActiveModel\Attributes;

class AccessibleProperties
{
    // const ACTIVE_RECORD_BASE = 'Rails\ActiveRecord\Base';
    
    protected static $instance;
    
    protected $propertiesByClass = [];
    
    protected $propsByClassUnderscored = [];
    
    public static function getProperties($className)
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->get($className);
    }
    
    public static function getPropertiesUnderscored($className)
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->getUnderscored($className);
    }
    
    public static function getProperty($className, $property)
    {
        $attrs = self::getProperties($className);
        if (isset($attrs[$property])) {
            return $attrs[$property];
        }
    }
    
    public function get($className)
    {
        if (!isset($this->propertiesByClass[$className])) {
            $this->propertiesByClass[$className] = $this->getAccessibleProperties($className);
            
            # TODO: cache accordingly.
        }
        
        return $this->propertiesByClass[$className];
    }
    
    public function getUnderscored($className)
    {
        if (!isset($this->propsByClassUnderscored[$className])) {
            $attrsNames = [];
            
            foreach ($this->get($className) as $attrName => $value) {
                $underscoredName = \Rails::getService('inflector')->underscore($attrName)->toString();
                $attrsNames[$underscoredName] = $value;
            }
            
            # TODO: cache accordingly.
            $this->propsByClassUnderscored[$className] = $attrsNames;
        }
        
        return $this->propsByClassUnderscored[$className];
    }
    
    /**
     * Get accessible properties.
     * An accessible property is either a public property, or a
     * property that has a setter method named like `setPropName`.
     * Returns an array whose keys are the property name, and the value
     * is the setter name if the property has a setter, or an array with
     * the actual property name under the `propName` key if its a public
     * property.
     *
     * @param string $className
     * @return array
     */
    protected function getAccessibleProperties($className)
    {
        $refl = $className::getReflection();
        $props = [];
        
        foreach ($refl->getProperties() as $prop) {
            if (
                $prop->getDeclaringClass()->getName() == 'Rails\ActiveModel\Base'           ||
                $prop->getDeclaringClass()->getName() == 'Rails\ActiveRecord\Base'          ||
                $prop->getDeclaringClass()->getName() == 'Rails\ActiveRecord\Mongo\Base'
            ) {
                break;
            }
            
            if ($prop->isPublic()) {
                $props[$prop->getName()] = [
                    'propName' => $prop->getName()
                ];
            } else {
                $params = [];
                
                $getterMethodName = $prop->getName();
                if (
                    $refl->hasMethod($getterMethodName) &&
                    ($method = $refl->getMethod($getterMethodName)) &&
                    $method->isPublic()
                ) {
                    $params[] = true;
                } else {
                    $params[] = false;
                }
                
                $setterMethodName = 'set' . ucfirst($prop->getName());
                if (
                    $refl->hasMethod($setterMethodName) &&
                    ($method = $refl->getMethod($setterMethodName)) &&
                    $method->isPublic()
                ) {
                    $params[] = true;
                } else {
                    $params[] = false;
                }
                
                $props[$prop->getName()] = $params;
            }
        }
        
        return $props;
    }
}
