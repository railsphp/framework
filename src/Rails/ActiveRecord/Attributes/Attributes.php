<?php
namespace Rails\ActiveRecord\Attributes;

use Rails\ActiveModel\Attributes\Attributes as AttributesBase;

class Attributes extends AttributesBase
{
    protected function castType($attrName, $value)
    {
        return self::getAttributesFor($this->className)
            ->getAttribute($attrName)
            ->column()
            ->typeCast($value);
    }
    
    // protected function typeCastForWrite($attrName, $value)
    // {
        // return self::getAttributesFor($this->className)
            // ->getAttribute($attrName)
            // ->column()
            // ->typeCastForWrite($value);
    // }
}
