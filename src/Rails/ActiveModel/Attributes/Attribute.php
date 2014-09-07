<?php
namespace Rails\ActiveModel\Attributes;

class Attribute implements AttributeInterface
{
    protected $name;
    
    protected $type;
    
    protected $defaultValue;
    
    protected $serializable = false;
    
    public function __construct($name, $type = 'string', $defaultValue = null, $serializable = false)
    {
        $this->name = $name;
        $this->setType($type);
        $this->setDefaultValue($defaultValue);
        $this->setSerializable($serializable);
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function type()
    {
        return $this->type;
    }
    
    public function defaultValue()
    {
        return $this->defaultValue;
    }
    
    public function serializable()
    {
        return $this->serializable;
    }
    
    public function setType($type)
    {
        return $this->type = $type;
    }
    
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }
    
    public function setSerializable($serializable)
    {
        $this->serializable = (bool)$serializable;
    }
}
