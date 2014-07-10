<?php
namespace Rails\ActiveRecord\Base\Methods;

use Rails\ActiveRecord\Metadata;
use Rails\ActiveRecord\Base\ModelSchema;

/**
 * These methods offer information about the table corresponding
 * to this model, with which the ModelSchema object will be created.
 */
trait ModelSchemaMethodsTrait
{
    /**
     * @return ModelSchema
     */
    public static function table()
    {
        $class = get_called_class();
        // $class = static::connection()->getName();
        if (!isset(self::$modelSchemas[$class])) {
            $schema = static::initTable();
            self::$modelSchemas[$class] = $schema;
        }
        return self::$modelSchemas[$class];
    }
    
    /**
     * Returns the value of the TABLE_NAME constant if not empty,
     * otherwise it figures out the name of the table out of the
     * name of the model class.
     *
     * @return string
     */
    static public function tableName()
    {
        if (static::TABLE_NAME) {
            return static::TABLE_NAME;
        } else {
            $cn  = str_replace('\\', '_', get_called_class());
            $inf = self::services()->get('inflector');
            
            $tableName = $inf->underscore($inf->pluralize($cn));
            
            return static::tableNamePrefix() . $tableName . static::tableNameSuffix();
        };
    }
    
    /**
     * This prefix will be attached to the table name.
     *
     * @return string
     */
    static public function tableNamePrefix()
    {
        return static::TABLE_NAME_PREFIX;
    }
    
    /**
     * This suffix will be attached to the table name.
     *
     * @return string
     */
    static public function tableNameSuffix()
    {
        return static::TABLE_NAME_SUFFIX;
    }

    /**
     * @return ModelSchema
     */
    protected static function initTable()
    {
        $connectionName = static::adapter()->getDriver()->getConnection()->getName();
        if (!isset(self::$metadatas[$connectionName])) {
            if (self::getService('rails.config')['use_cache']) {
                $metadata = new Metadata\CachedMetadata(static::adapter());
            } else {
                $metadata = new Metadata\Metadata(static::adapter());
            }
            self::$metadatas[$connectionName] = $metadata;
        }
        
        return new ModelSchema(
            static::tableName(),
            self::$metadatas[$connectionName]
        );
    }
}
