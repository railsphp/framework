<?php
namespace Rails\ActiveModel;

trait SerializableTrait
{
    public function toXml()
    {
        
    }
    
    public function asJson()
    {
        return $this->attributes();
    }
    
    public function toJson()
    {
        return json_encode($this->asJson());
    }
}
