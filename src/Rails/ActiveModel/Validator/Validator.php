<?php
namespace Rails\ActiveModel\Validator;

abstract class Validator
{
    protected static $validatorsPerClass = [];
    
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
     * @return void
     */
    abstract public function validate($record);
    
    public static function setForClass($class, self $validator)
    {
        self::$validatorsPerClass[$class] = $validator;
    }
    
    public static function forClass($class)
    {
        if (isset(self::$validatorsPerClass[$class])) {
            return self::$validatorsPerClass[$class];
        }
    }
    
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
}
