<?php
namespace Rails\ActiveRecord\Persistence\PersistedModel\Methods;

trait AttributeMethodsTrait
{
    /**
     * Get record's primary key.
     * Common attribute getter. Returns the value of the attribute
     * belonging to the primaryKey, which normally is "id".
     *
     * @return string
     */
    public function id()
    {
        return $this->getAttribute(static::primaryKey());
    }
    
    # TODO: ver cómo afecta éste método al mass-attribute-assigment
    /**
     * Set record's primary key.
     * Common attribute setter.
     *
     * @return self
     */
    public function setId($id)
    {
        $this->setAttribute(static::primaryKey(), $id);
        return $this;
    }
    
    /**
     * Get record's deleted_at key.
     * Common attribute getter. Returns the value of the attribute
     * belonging to the primaryKey, which normally is "id".
     *
     * @return string
     */
    public function deletedAt()
    {
        return $this->getAttribute(static::DELETED_AT_ATTRIBUTE);
    }
    
    /**
     * Set record's deleted_at key.
     * Common attribute setter.
     *
     * @return self
     */
    public function setDeletedAt($value)
    {
        $this->setAttribute(static::DELETED_AT_ATTRIBUTE, $value);
        return $this;
    }
    
    /**
     * If the model is a new record, the default values passed aren't the current values. Mark the
     * model as dirty.
     * Otherwise, the default values passed are the current values; they shouldn't
     * make the model dirty.
     *
     * @return bool
     */
    protected function initAttrsDirtyModel()
    {
        return $this->isNewRecord();
    }
    
    // protected function setterExists($attrName)
    // {
        // $setter = 'set' . ucfirst($attrName);
        // $reflection = self::getReflection();
        
        // if ($reflection->hasMethod($setter)) {
            // $method = $reflection->getMethod($setter);
            // if ($method->isPublic() && !$method->isStatic()) {
                // return $setter;
            // }
        // }
        // return false;
    // }
}
