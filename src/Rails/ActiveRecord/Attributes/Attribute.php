<?php
namespace Rails\ActiveRecord\Attributes;

use Rails\ActiveModel\Attributes\AttributeInterface;
use Rails\ActiveRecord\Metadata\Object\ColumnObject;

class Attribute implements AttributeInterface
{
    protected $column;
    
    public function __construct(ColumnObject $column)
    {
        $this->column = $column;
    }
    
    public function column()
    {
        return $this->column;
    }
    
    public function name()
    {
        return $this->column->getName();
    }
    
    public function type()
    {
        return $this->column->type();
    }
    
    public function defaultValue()
    {
        return $this->column->getColumnDefault();
    }
    
    public function serializable()
    {
        return $this->serializable;
    }
    
    public function setType($type)
    {
        $this->column->setType($type);
    }
    
    public function setDefaultValue($defaultValue)
    {
        $this->column->setColumnDefault($defaultValue);
    }
    
    public function setSerializable($serializable)
    {
        $this->serializable = (bool)$serializable;
    }
}
