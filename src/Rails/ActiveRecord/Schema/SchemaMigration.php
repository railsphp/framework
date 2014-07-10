<?php
namespace Rails\ActiveRecord\Schema;

class SchemaMigration extends \Rails\ActiveRecord\Base
{
    protected static $adapter;
    
    protected static $tableName;
    
    public static function setAdapter($adapter)
    {
        self::$adapter = $adapter;
    }
    
    public static function adapter()
    {
        return self::$adapter;
    }
    
    public static function tableName()
    {
        return self::$tableName;
    }
    
    public static function setTableName($tableName)
    {
        self::$tableName = $tableName;
    }
}
