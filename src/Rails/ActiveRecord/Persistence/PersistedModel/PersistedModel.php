<?php
namespace Rails\ActiveRecord\Persistence\PersistedModel;

use Rails\ServiceManager\ServiceLocatorAwareTrait;
use Rails\ActiveRecord\Relation;
use Rails\ActiveRecord\Collection;
use Rails\ActiveModel;

/**
 * This class sets base methods for persistance.
 */
abstract class PersistedModel extends ActiveModel\Base
{
    use Methods\AttributeMethodsTrait,
        Methods\PersistenceMethodsTrait,
        Methods\ConnectionMethodsTrait,
        Methods\RelationMethodsTrait;
    
    /**
     * @see primaryKey()
     * @see Base\Methods\AttributeMethods::id()
     */
    const PRIMARY_KEY = 'id';
    
    const DELETED_AT_ATTRIBUTE = 'deleted_at';
    
    const IS_RECOVERABLE = true;
    
    /**
     * @used-by Base\Methods\PersistenceMethods
     */
    protected static $persistence;
    
    protected static function getRelation()
    {
        return new Relation(get_called_class());
    }
    
    /**
     * Name of the column that is the primary key.
     *
     * @return string
     */
    public static function primaryKey()
    {
        return static::PRIMARY_KEY;
    }
    
    /**
     * Collection object for this model. Can be overwritten to set
     * a custom collection class that extends Collection.
     *
     * @return Collection
     */
    public static function collection(array $members = [])
    {
        return new Collection($members);
    }
    
    public static function i18nScope()
    {
        return 'activerecord';
    }
    
    /**
     * The name of the "deleted_at" attribute.
     *
     * @return string
     */
    public static function deletedAtAttribute()
    {
        return static::DELETED_AT_ATTRIBUTE;
    }
    
    /**
     * Type of the attribute that marks a record as deleted.
     * Supported types are: boolean, integer, date, datetime and timestamp.
     * If the model isn't recoverable, returns false.
     *
     * @return string|false
     * @see deletedAtValue()
     * @see deletedAtEmptyValue()
     */
    public static function deletedAtType()
    {
        if (static::isRecoverable()) {
            return 'datetime';
        }
        return false;
    }
    
    /**
     * Proper value (that marks a record as deleted) for the deletedAt column.
     *
     * @return string|int
     */
    public static function deletedAtValue()
    {
        switch (static::deletedAtType()) {
            case 'date':
                return date('Y-m-d');
            
            case 'datetime':
                return date('Y-m-d H:i:s');
            
            case 'timestamp':
                return time();
            
            /**
             * To explicity note that integer and boolean types
             * get 1 as value.
             */
            case 'integer':
            case 'boolean':
            default:
                return '1';
        }
    }
    
    /**
     * Proper empty (non-deleted) value for the deletedAt column, which is `null`
     * by default.
     // * The deleted-at attribute on records that are not deleted must have this
     // * "empty value" by default (e.g. they must not have `NULL` as default value) because
     // * relations, by default, will (or should) look for records with these values.
     *
     * @return string
     */
    public static function deletedAtEmptyValue()
    {
        return null;
        // switch (static::deletedAtType()) {
            // case 'date':
                // return '0000-00-00';
            
            // case 'datetime':
                // return '0000-00-00 00:00:00';
            
            // case 'timestamp':
                // return '0';
            
            // /**
             // * To explicity note that integer and boolean types
             // * get 0 as value.
             // */
            // case 'integer':
            // case 'boolean':
            // default:
                // return '0';
        // }
    }
    
    public function __construct(array $attributes = [], $isNewRecord = true)
    {
        $this->isNewRecord = (bool)$isNewRecord;
        parent::__construct($attributes);
    }
    
    public function __set($prop, $value)
    {
        return parent::__set(static::properAttributeName($prop), $value);
    }
    
    public function __call($methodName, $params)
    {
        $attrName = static::properAttributeName($methodName);
        
        # TODO: Check scopes, etc.
        
        return parent::__call($attrName, $params);
    }
    
    protected function setupValidator()
    {
        $validator = parent::setupValidator();
        $validator->setValidator('uniqueness', 'Rails\ActiveRecord\Validator\Validations\UniquenessValidator');
        $validator->setValidator('presence',   'Rails\ActiveRecord\Validator\Validations\PresenceValidator');
        return $validator;
    }
}
