<?php
namespace Rails\ActiveRecord\Metadata\Source;

use Rails\ActiveRecord\Metadata\Object;

/**
 * getColumn method modifier
 * This trait will modify the getColumn() method so it will
 * use Rails' ColumnObject class instead.
 */
trait ColumnModifierTrait
{
    public function getColumn($columnName, $table, $schema = null)
    {
        $base   = parent::getColumn($columnName, $table, $schema = null);
        $column = Object\ColumnObject::createFromBase($base);
        return $column;
    }
}
