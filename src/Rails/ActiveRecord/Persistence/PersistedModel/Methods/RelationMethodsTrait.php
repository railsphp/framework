<?php
namespace Rails\ActiveRecord\Persistence\PersistedModel\Methods;

use Rails\ActiveRecord\Relation;

trait RelationMethodsTrait
{
    /**
     * @return \Rails\ActiveRecord\Relation\AbstractRelation
     */
    protected static function getRelation()
    {
        throw new \RuntimeException(
            __METHOD__ . " static method must be overriden"
        );
    }
    
    public static function all()
    {
        return self::getRelation();
    }
    
    public static function select()
    {
        return call_user_func_array([self::getRelation(), 'select'], func_get_args());
    }
    
    public static function where()
    {
        return call_user_func_array([self::getRelation(), 'where'], func_get_args());
    }
    
    public static function rawWhere()
    {
        return call_user_func_array([self::getRelation(), 'rawWhere'], func_get_args());
    }
    
    public static function find($id)
    {
        return self::all()->find($id);
    }
    
    public static function order($order)
    {
        return self::all()->order($order);
    }
    
    public static function first($limit = 1)
    {
        return self::all()->first($limit);
    }
    
    public static function count()
    {
        return self::all()->count();
    }
    
    /**
     * Include deleted records in the results.
     */
    public static function deleted()
    {
        return self::all()->deleted();
    }
    
    /**
     * Return only deleted records.
     */
    public static function deletedOnly()
    {
        return self::all()->deleted('only');
    }
    
    public static function limit($limit)
    {
        return self::all()->limit($limit);
    }
}
