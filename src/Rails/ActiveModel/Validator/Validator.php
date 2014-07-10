<?php
namespace Rails\ActiveModel\Validator;

abstract class Validator
{
    /**
     * @var array
     */
    protected $options;
    
    /**
     * This method contains the logic with which the data will be validated.
     * $record is an object that must use the \Rails\ActiveModel\Errors\ErrorsTrait.
     * It is passed basically only to add errors to it.
     * $attribute is the name of the attribute that will be validated.
     * $value is what is going to be validated; the value of the attribute.
     *
     * @param object $record
     // * @param string $attribute
     // * @param mixed $value
     * @return void
     */
    abstract public function validate($record/*, $attribute, $value*/);
    
    /**
     * Common allowed options:
     * - strict: bool|string. If a validation fails, this option will be passed to
     *   the errors object of the record. See \Rails\ActiveModel\Errors\Errors::add().
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }
    
    // public function setOptions($options)
    // {
        // $this->options = $options;
        // $this->validateOptions();
    // }
}
