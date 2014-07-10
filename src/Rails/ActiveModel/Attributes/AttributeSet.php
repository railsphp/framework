<?php
namespace Rails\ActiveModel\Attributes;

class AttributeSet
{
    protected $attributes = [];
    
    public function __construct(array $attributes = [])
    {
        if ($attributes) {
            $this->addAttributes($attributes);
        }
    }
    
    public function attributes()
    {
        return $this->attributes;
    }
    
    public function addAttribute(AttributeInterface $attribute)
    {
        $this->attributes[$attribute->name()] = $attribute;
        return $this;
    }
    
    public function addAttributes(array $attributes)
    {
        foreach ($attributes as $attribute) {
            $this->addAttribute($attribute);
        }
        return $this;
    }
    
    public function getAttribute($attrName)
    {
        if (!$this->exists($attrName)) {
            return null;
        } else {
            return $this->attributes[$attrName];
        }
    }
    
    public function exists($attrName)
    {
        return isset($this->attributes[$attrName]);
    }
}
