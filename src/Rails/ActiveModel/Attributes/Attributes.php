<?php
namespace Rails\ActiveModel\Attributes;

use Rails\ActiveModel\Exception;

class Attributes
{
    /**
     * Holds attributes for each registered class.
     *
     * @var array
     * @see setClassAttributes()
     */
    protected static $classAttributes = [];
    
    /**
     * The name of the class holding this instance.
     *
     * @var string
     */
    protected $className;
    
    /**
     * Holds attributes as attributeName => value.
     *
     * @var array
     */
    protected $attributes = [];
    
    protected $dirty;
    
    protected $beforeTypeCast = [];
    
    /**
     * Register attributes for a class.
     * This must be done before creating an instance of Attributes
     * for that class. Registration can be done only once.
     *
     * @throws Exception\RuntimeException if the class is already registered.
     */
    public static function setClassAttributes($className, AttributeSet $attributes)
    {
        if (self::attributesSetFor($className)) {
            throw new Exception\RuntimeException(
                srptinf("Attributes already set for class %s", $className)
            );
        }
        self::$classAttributes[$className] = $attributes;
    }
    
    /**
     * Check if attributes are registered for a class.
     *
     * @return bool
     */
    public static function attributesSetFor($className)
    {
        return array_key_exists($className, self::$classAttributes);
    }
    
    /**
     * Check if an attribute is valid for a class.
     *
     * @return bool
     * @throws Exception\RuntimeException
     */
    public static function isClassAttribute($className, $attrName)
    {
        if (!self::attributesSetFor($className)) {
            // # TODO: this logic doesn't make sense because the classes implementing
            // # AttributedModelTrait require a piece of code to register their attributes.
            // return false;
            throw new Exception\RuntimeException(
                sprintf("Attributes not set for class %s", $className)
            );
        }
        // return in_array($attrName, self::$classAttributes[$className]);
        return self::$classAttributes[$className]->exists($attrName);
    }
    
    /**
     * Get the registered attributes for a class.
     *
     * @return array
     * @throws Exception\RuntimeException if the class isn't registered.
     */
    public static function getAttributesFor($className)
    {
        if (!self::attributesSetFor($className)) {
            throw new Exception\RuntimeException(
                sprintf("Attributes not set for class %s", $className)
            );
        }
        return self::$classAttributes[$className];
    }
    
    # TODO: if the model data was fetched from a db, $initialValues shoulnd't
    # make the model dirty!
    /**
     * Constructor.
     * Class attributes for $className must be set before creating an instance for that class.
     * Default values for attributes can be passed in $attributes.
     // * If $defaultValues is set to true, the $initialValues passed won't make the model dirty.
     */
    public function __construct($className, array $initialValues = []/*, $defaultValues = true*/)
    {
        $this->className  = $className;
        $this->setDefaultValues();
        
        if ($initialValues) {
            $this->setRaw($initialValues);
        }
    }
    
    public function dirty()
    {
        if (!$this->dirty) {
            $this->dirty = new Dirty($this);
        }
        return $this->dirty;
    }
    
    /**
     * Returns the value of an attribute.
     *
     * @return mixed
     * @throws Exception\RuntimeException if the attribute is invalid.
     */
    public function get($attrName)
    {
        if (!self::isClassAttribute($this->className, $attrName)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Trying to get unknown attribute '%s' for class '%s'",
                    $attrName,
                    $this->className
                )
            );
        }
        return isset($this->attributes[$attrName]) ?
                $this->attributes[$attrName] : null;
    }
    
    /**
     * Sets values for one or many attributes.
     * If $attrName is an array, it's assumed it's attrName/value pairs.
     * If an attribute is invalid, an exception is thrown.
     * Attribute changes will be registered on Dirty unless `false` is
     * passed as $dirty.
     *
     * @throws Exception\RuntimeException
     */
    public function set($attrName, $attrValue = null, $dirty = true)
    {
        if (!is_array($attrName)) {
            $attributes = [$attrName => $attrValue];
        } else {
            $attributes = $attrName;
        }
        
        foreach ($attributes as $attrName => $value) {
            if (!self::isClassAttribute($this->className, $attrName)) {
                throw new Exception\RuntimeException(
                    sprintf(
                        "Trying to set unknown attribute '%s' for class '%s'",
                        $attrName,
                        $this->className
                    )
                );
            }
            
            $this->beforeTypeCast[$attrName] = $value;
            if ($dirty) {
                $this->dirty()->registerAttributeChange($attrName, $value);
            }
            $this->attributes[$attrName] = $this->typeCastForSet($attrName, $value);
        }
        
        return true;
    }
    
    public function getBeforeTypeCast($attrName)
    {
        if (!self::isClassAttribute($this->className, $attrName)) {
            throw new Exception\RuntimeException(
                sprintf(
                    "Trying to get before-type-cast value of unknown attribute '%s' for class '%s'",
                    $attrName,
                    $this->className
                )
            );
        }
        return isset($this->beforeTypeCast[$attrName]) ?
                $this->beforeTypeCast[$attrName] : null;
    }
    
    /**
     * This is the same as doing `$attrs->set($name, $value, false)`.
     *
     * @see set()
     */
    public function setRaw($attrName, $attrValue = null)
    {
        return $this->set($attrName, $attrValue, false);
    }
    
    /**
     * Returns the attributes array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }
    
    /**
     * Checks if an attribute is a valid attribute for a class. This is
     * the instance-version of isClassAttribute().
     *
     * @return bool
     */
    public function isAttribute($attrName)
    {
        return self::isClassAttribute($this->className, $attrName);
    }
    
    /**
     * Get attribute
     **
     * @param string $attrName
     * @return Attribute
     */
    public function getAttribute($attrName)
    {
        $attribute = self::getAttributesFor($this->className)->getAttribute($attrName);
        if (!$attribute) {
            throw new Exception\RuntimeException(sprintf(
                "Trying to get unknown Attribute '%s' for class '%s'",
                $attrName,
                $this->className
            ));
        }
        return $attribute;
    }
    
    protected function setDefaultValues()
    {
        $attrSet = self::getAttributesFor($this->className);
        
        foreach ($attrSet->attributes() as $attribute) {
            $this->attributes[$attribute->name()] = $attribute->defaultValue();
        }
    }
    
    protected function typeCastForSet($attrName, $value)
    {
        switch ($this->getAttribute($attrName)->type()) {
            case 'integer':
                if (is_int($value)) {
                    return $value;
                } elseif (is_scalar($value)) {
                    return (int)$value;
                } else {
                    return null;
                }
            
            case 'float':
                if (is_float($value)) {
                    return $value;
                } elseif (is_scalar($value)) {
                    return (float)$value;
                } else {
                    return null;
                }
            
            case 'datetime':
            case 'timestamp':
            case 'time':
            case 'date':
                if (is_int($value) or (is_string($value) && ctype_digit($value))) {
                    $value = \Carbon\Carbon::createFromTimestamp($value);
                } elseif (is_string($value)) {
                    try {
                        $value = new \Carbon\Carbon($value);
                    } catch (\Exception $e) {
                        $value = null;
                    }
                } elseif (!$value instanceof \DateTime) {
                    $value = null;
                }
                return $value;
            
            case 'boolean':
                return (bool)$value;
            
            default:
                return $value;
        }
    }
    
    protected function castType($attrName, $value)
    {
        #TODO:
        return $value;
    }
    
    // /**
     // * Set initial attribute values.
     // * Same as set() except that this won't register attribute changes.
     // */
    // protected function setInitialValues(array $attributes)
    // {
        // foreach ($attributes as $attrName => $value) {
            // if (!self::isClassAttribute($this->className, $attrName)) {
                // throw new Exception\RuntimeException(
                    // sprintf(
                        // "Trying to set unknown attribute '%s' for class '%s'",
                        // $attrName,
                        // $this->className
                    // )
                // );
            // }
            
            // $this->beforeTypeCast[$attrName] = $value;
            // $this->attributes[$attrName] = $this->castType($attrName, $value);
        // }
    // }
}
