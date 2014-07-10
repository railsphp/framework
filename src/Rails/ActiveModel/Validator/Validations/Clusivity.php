<?php
namespace Rails\Validator\Validations;

use Rails\ActiveModel\Validator\EachValidator;
use Rails\ActiveModel\Validator\Exception;

/**
 * Available options:
 * in: could be one of the following:
 *   - An array containing the values to validate with.
 *   - A Closure which receives the record as parameter.
 *   - A string which would be taken as a method name for
 *     the $record object that will be called.
 *   In the case of passing a Closure or a string, they
 *   must return an array.
 * type: boolean that if set to true, will cause the
 *   validation not to only check the value but also the
 *   type.
 */
abstract class Clusivity extends EachValidator
{
    abstract protected function validateClusivity($record, $attribute, $value, $haystack, $strict);
    
    public function validateEach($record, $attribute, $value)
    {
        $delimiter = $this->options['in'];
        $strict = isset($this->options['strict']) && $this->options['strict'];
        
        if ($delimiter instanceof \Closure) {
            $haystack = $delimiter($record);
        } elseif (is_string($delimiter)) {
            $haystack = $record->$delimiter();
        } else {
            $haystack = $delimiter;
        }
        
        $this->validateClusivity($record, $attribute, $value, $haystack, $strict);
    }
    
    public function validateOptions()
    {
        if (!isset($this->options['in'])) {
            throw new Exception\InvalidArgumentException(
                "Missing 'in' option"
            );
        } elseif (
            !is_array($this->options['in']) ||
            !$this->options['in'] instanceof \Closure ||
            !is_string($this->options['in'])
        ) {
            throw new Exception\InvalidArgumentException(
                "'in' option must be either a Closure, an array or a string"
            );
        }
    }
}
