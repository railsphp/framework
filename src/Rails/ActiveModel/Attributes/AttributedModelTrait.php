<?php
namespace Rails\ActiveModel\Attributes;

use Rails\ActiveModel\Attributes\Attributes as ModelAttributes;
use Rails\ActiveModel\Attributes\AttributeSet;
use Rails\ActiveModel\Exception;

trait AttributedModelTrait
{
    /**
     * @var Attributes
     */
    protected $attributes;
    
    public static function getAttributeSet()
    {
        static::initAttributeSet();
        return Attributes::getAttributesFor(get_called_class());
    }
    
    # TODO: consider changing the name of this method.
    /**
     * This method may be overridden to actually set the attribues.
     *
     * @return void
     */
    protected static function initAttributeSet()
    {
        $className = get_called_class();
        
        if (!ModelAttributes::attributesSetFor($className)) {
            ModelAttributes::setClassAttributes(
                $className,
                static::attributeSet()
            );
        }
    }
    
    protected static function attributeSet()
    {
        return new AttributeSet();
    }
    
    protected static function properAttributeName($attrName)
    {
        if (!static::$literalAttributeNames) {
            return self::services()->get('inflector')->underscore($attrName)->toString();
        } else {
            return $attrName;
        }
    }
    
    /**
     * Get the current attributes and their values.
     * This does NOT return the Attributes object; use getAttributes() for that.
     *
     * @return array
     */
    public function attributes()
    {
        return $this->attributes->toArray();
    }
    
    /**
     * Get the Attributes instance.
     *
     * @return Attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    /**
     * Get the value for an attribute.
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        return $this->attributes->get($name);
    }
    
    public function getProperty($name)
    {
        if (Attributes::isClassAttribute(get_called_class(), $name)) { 
            return $this->getAttribute($name);
        } elseif ($type = AccessibleProperties::getProperty(get_called_class(), $name)) {
            if (isset($type['propName'])) {
                return $this->{$type['propName']};
            } elseif ($type[0]) {
                return $this->$name();
            }
        }
        
        throw new Exception\InvalidArgumentException(
            sprintf(
                "Trying to get unknown property %s::%s",
                get_called_class(),
                $name
            )
        );
    }
    
    /**
     * Set a value to an attribute.
     *
     * @return self
     */
    public function setAttribute($name, $value)
    {
        $this->attributes->set($name, $value);
        return $this;
    }
    
    /**
     * Mass-assign values to attributes.
     * Pass an array of key => value:
     *  * If the key is an attribute, value will be set to it.
     *  * If the key is a public property, value will be set to it.
     *  * If a setter method like setValueName (notice the camelCase) exists,
     *    the value will be passed to it.
     * In MVC, `$attributes` would normally be the request parameters.
     * Note that the names of the attributes must be underscored.
     * Aliased as assignAttrs().
     *
     * Attributes are filtered in order to protect sensitive ones, like the "id"
     * attribute for persisted models. This is done through the `attrAccessible()`
     * and `attrProtected()` methods.
     * Pass `true` as second argument to ignore protection.
     *
     * @see attrAccessible()
     * @see attrProtected()
     */
    public function assignAttributes(array $attributes, $withoutProtection = false)
    {
        $modelAttrs = $this->getAttributes();
        
        if (!$withoutProtection) {
            $attrAccessible = $this->getAccessibleAttributes();
        }
        
        $this->setAndFilterProperties($attributes);
        
        foreach ($attributes as $attrName => $value) {
            if (!$withoutProtection && $attrAccessible) {
                if (!in_array($attrName, $attrAccessible)) {
                    continue;
                }
            }
            
            $modelAttrs->set($attrName, $value);
        }
        
        return $this;
    }
    
    protected function setAndFilterProperties(array &$attributes)
    {
        $accProps = AccessibleProperties::getPropertiesUnderscored(get_called_class());
        $this->filterProperties($attributes, $accProps);
    }
    
    protected function filterProperties(array &$attributes, $accProps)
    {
        foreach ($attributes as $attrName => $value) {
            if (isset($accProps[$attrName])) {
                if (isset($accProps[$attrName]['propName'])) {
                    $this->{$accProps[$attrName]['propName']} = $value;
                } else {
                    if ($accProps[$attrName][1]) {
                        $setterMethod = $accProps[$attrName][1];
                        $this->$setterMethod($value);
                    }
                }
                unset($attributes[$attrName]);
            }
        }
    }
    
    /**
     * Assign Attrs
     * Alias for assignAttributes().
     *
     * @see assignAttributes()
     */
    public function assignAttrs(array $attributes)
    {
        return $this->assignAttributes($attributes);
    }
    
    public function hasChanged()
    {
        return $this->attributes->dirty()->hasChanged();
    }
    
    public function attributeChanged($attrName)
    {
        return $this->attributes->dirty()->attributeChanged($attrName);
    }
    
    public function changedAttributes()
    {
        return $this->attributes->dirty()->changedAttributes();
    }
    
    public function changes()
    {
        return $this->attributes->dirty()->changes();
    }
    
    public function attributeWas($attrName)
    {
        return $this->attributes->dirty()->attributeWas($attrName);
    }
    
    /**
     * List of attributes that will be considered when mass-assigning
     * attributes with assignAttributes(); the rest will be skipped.
     *
     * @return null|array
     * @see attrProtected()
     */
    protected function attrAccessible()
    {
        return null;
    }
    
    /**
     * List of attributes that will be skipped when mass-assigning
     * attributes with assignAttributes().
     * attrAccessible() takes precedence, and if it returns an array,
     * this method is ignored.
     *
     * @return null|array
     */
    protected function attrProtected()
    {
        return null;
    }
    
    protected function getAccessibleAttributes()
    {
        $attrNames = array_keys($this->getAttributes()->toArray());
        
        $attrs = $this->attrAccessible();
        if (null !== $attrs) {
            return $attrs;
        }
        
        $attrs = $this->attrProtected();
        if (null !== $attrs) {
            return array_diff($attrNames, $attrs);
        }
        return [];
    }
    
    /**
     * @see initAttrsDirtyModel()
     * @see getAttributesClass()
     */
    protected function initializeAttributes(array $attributes)
    {
        $className = get_called_class();
        
        /**
         * Check if attributes are set for this class. Set them if not.
         */
        if (!Attributes::attributesSetFor($className)) {
            $className::initAttributeSet();
        }
        
        $attrsClass = $this->getAttributesClass();
        $this->attributes = new $attrsClass($className, $this->defaultAttributes());
        
        if ($attributes) {
            $this->setAndFilterProperties($attributes);
            $this->getAttributes()->set($attributes, null, $this->initAttrsDirtyModel());
        }
    }
    
    /**
     * Returns the Attributes class. Useful if child classes need
     * a to use a different class.
     *
     * @return string
     */
    protected function getAttributesClass()
    {
        return __NAMESPACE__ . '\Attributes'; 
    }
    
    protected function defaultAttributes()
    {
        return [];
    }
    
    /**
     * Defines condition that will decide if the attributes passed to the constructor
     * should make the model dirty or not. By default, the model doesn't turn dirty.
     *
     * @return bool
     */
    protected function initAttrsDirtyModel()
    {
        return false;
    }
}
