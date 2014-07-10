<?php
namespace Rails\ActiveModel;

use Rails\ActiveRecord\Base\Methods;
use Rails\ServiceManager\ServiceLocatorAwareTrait;

abstract class Base
{
    use ServiceLocatorAwareTrait,
        Validator\ValidableModelTrait,
        Attributes\AttributedModelTrait,
        SerializableTrait,
        Callbacks\CallbackUsableTrait;
    
    /**
     * Holds reflections of model classes so they don't have to be
     * created more than once.
     *
     * @var array
     */
    protected static $createdReflections = [];
    
    /**
     * Holds data regarding public properties for model classes, so the
     * verification can be skipped if requested multiple times.
     *
     * @var array
     */
    protected static $publicClassProperties = [];
    
    /**
     * Used when invoking magic methods to get/set model attributes.
     * The default convention is to call the camel-cased version of the attribute, for
     * example `$record->createdAt()` will return the "created_at" attribute (which
     * belongs to the "created_at" column).
     * In the case literal names must be used (call createdAt() to get createdAt
     * attribute) this property can be set to true.
     * However, the best practice would be to manually create getters for each attribute,
     * and even best would be to use actual setter methods for each attribute instead of
     * magically setting them.
     *
     * This might be removed in the future, _forcing_ the default convention.
     *
     * @var bool
     */
    protected static $literalAttributeNames = false;
    
    public static function getReflection($class = null)
    {
        if (!$class) {
            $class = get_called_class();
        }
        if (!isset(self::$createdReflections[$class])) {
            self::$createdReflections[$class] = new \ReflectionClass($class);
        }
        return self::$createdReflections[$class];
    }
    
    public static function i18nScope()
    {
        return 'activemodel';
    }
    
    public function __construct(array $attributes = [])
    {
        $this->initializeAttributes($attributes);
    }
    
    public function __set($attrName, $value)
    {
        if ($this->getAttributes()->isAttribute($attrName)) {
            return $this->getAttributes()->set($attrName, $value);
        }
        
        throw new Exception\RuntimeException(
            sprintf("Trying to set unknown property %s::%s", get_called_class(), $attrName)
        );
    }
    
    public function __call($attrName, $params)
    {
        if ($this->getAttributes()->isAttribute($attrName)) {
            return $this->getAttributes()->get($attrName);
        }
        
        throw new Exception\RuntimeException(
            sprintf("Trying to call unknown method %s::%s()", get_called_class(), $attrName)
        );
    }
}
