<?php
namespace Rails\ActiveModel\Attributes;

class Dirty
{
    /**
     * @var Attributes
     */
    protected $attributes;
    
    /**
     * Whenever an attribute's value is changed, the original value
     * is held here as `attributeName => originalValue`.
     *
     * @var array
     */
    protected $changedAttributes = [];
    
    public function __construct(Attributes $attributes)
    {
        $this->attributes = $attributes;
    }
    
    /**
     * Checks whether attributes have changed or not.
     *
     * @return bool
     */
    public function hasChanged()
    {
        return (bool)$this->changedAttributes;
    }
    
    /**
     * Checks if a specific attribute has changed.
     *
     * @return bool
     */
    public function attributeChanged($attrName)
    {
        return array_key_exists($attrName, $this->changedAttributes);
    }
    
    /**
     * Returns an array of the changed attributes, their names as keys
     * and their original values as values.
     *
     * @return array
     * @see $changedAttributes
     */
    public function changedAttributes()
    {
        return $this->changedAttributes;
    }
    
    /**
     * Get attributes changes.
     * Returns an associated array containing both attributes' original and
     * current value, like `[ attrName => [originalValue, currentValue], ...]`
     *
     * @return array
     */
    public function changes()
    {
        $changes = [];
        foreach ($this->changedAttributes as $attrName => $originalValue) {
            $changes[$attrName] = [$originalValue, $this->attributes->get($attrName)];
        }
        return $changes;
    }
    
    /**
     * Returns the previous value of an attribute. If the attribute
     * wasn't changed, its current value is returned.
     *
     * @return mixed
     */
    public function attributeWas($attrName)
    {
        return $this->attributeChanged($attrName) ?
                $this->changedAttributes[$attrName] :
                $this->attributes->get($attrName);
    }
    
    /**
     * Discards all changes.
     *
     * @return $this
     */
    public function resetChanges()
    {
        $this->attributes->setRaw($this->changedAttributes);
        $this->changedAttributes = [];
        return $this;
    }
    
    /**
     * Notify changes were applied. Changes are removed, therefore the
     * attributes are no longer dirty.
     *
     * @return $this
     */
    public function changesApplied()
    {
        $this->changedAttributes = [];
        return $this;
    }
    
    /**
     * Registers a new value for a given attribute.
     * If the attribute hasn't changed yet, the new value is stored
     * as the original value of the attribute. If the attribute already has
     * an original value stored, and the new value equals to it, the attribute
     * change is unregistered. Otherwise, the new value is ignored; the
     * original value is kept.
     *
     * @return void
     */
    public function registerAttributeChange($attrName, $newValue)
    {
        if (!$this->attributeChanged($attrName)) {
            $oldValue = $this->attributes->get($attrName);
            if ((string)$newValue != (string)$oldValue) {
                $this->changedAttributes[$attrName] = $oldValue;
            }
        } elseif ((string)$newValue == (string)$this->changedAttributes[$attrName]) {
            unset($this->changedAttributes[$attrName]);
        }
    }
}
