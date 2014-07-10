<?php
namespace Rails\ActiveModel\Validator;

abstract class EachValidator extends Validator
{
    protected $attributes = [];
    
    abstract public function validateEach($record, $attribute, $value);
    
    public function __construct(array $options = [])
    {
        if (empty($options['attributes'])) {
            throw new Exception\BadMethodCallException(
                "Must pass a value as 'attributes' option"
            );
        }
        
        $this->attributes = $options['attributes'];
        unset($options['attributes']);
        
        if (!is_array($this->attributes)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "'attributes' option must be an array, %s passed",
                    gettype($this->attributes)
                )
            );
        }
        
        parent::__construct($options);
        $this->validateOptions();
    }
    
    public function validate($record)
    {
        foreach ($this->attributes as $attribute) {
            # TODO: readAttributeForValidation()?
            $value = $record->getProperty($attribute);
            if ((null === $value && !empty($this->options['allowNull'])) ||
                (!$value && !empty($this->options['allowBlank']))
            ) {
                continue;
            }
            $this->validateEach($record, $attribute, $value);
        }
    }
    
    /**
     * Check if $options are alright; else, an exception may be thrown.
     */
    public function validateOptions()
    {
    }
}
