<?php
namespace Rails\ActiveRecord\Persistence\PersistedModel\Methods;

use Rails\ActiveRecord\Relation;
use Rails\ActiveRecord\Exception\RecordNotFoundException;

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
        $first = self::all()->where([static::primaryKey() => $id])->first();
        if (!$first) {
            throw new RecordNotFoundException(sprintf(
                "Couldn't find %s with %s=%s",
                get_called_class(),
                static::primaryKey(),
                $id
            ));
        }
        return $first;
    }
    
    public static function order($order)
    {
        return self::all()->order($order);
    }
    
    public static function first($limit = 1)
    {
        return self::all()->first($limit);
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
