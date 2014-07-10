<?php
namespace Rails\ActiveModel\Validator\Validations;

use Rails\ActiveModel\Validator\EachValidator;
use Rails\ActiveModel\Validator\Exception;

class LengthValidator extends EachValidator
{
    public function __construct(array $options = [])
    {
        if (isset($options['in'])) {
            $range = $options['in'];
            
            if (!is_array($range) || !isset($range[0]) || !isset($range[1])) {
                throw new Exception\InvalidArgumentException(
                    "The 'in' option must be an array with 2 values"
                );
            }
            $options['min'] = $range[0];
            $options['max'] = $range[1];
        }
        
        if (empty($options['allowBlank']) && !isset($options['min']) && !isset($options['is'])) {
            $options['min'] = 1;
        }
        
        parent::__construct($options);
    }
    
    public function validateEach($record, $attribute, $value)
    {
        $length = strlen($value);
        
        if (isset($this->options['is'])) {
            if ($length == $this->options['is']) {
                return;
            }
            $message = 'wrong_length';
            $count   = $this->options['is'];
        } elseif (isset($this->options['min'])) {
            if ($length >= $this->options['min']) {
                return;
            }
            $message = 'too_short';
            $count   = $this->options['min'];
        } elseif (isset($this->options['max'])) {
            if ($length <= $this->options['max']) {
                return;
            }
            $message = 'too_long';
            $count   = $this->options['max'];
        }
        
        $options = $this->options;
        // $options['value'] = $value;
        $options['count'] = $count;
        
        $record->errors()->add($attribute, [$message], $options);
    }
    
    public function validateOptions()
    {
        if (!isset($this->options['is'])  &&
            !isset($this->options['min']) &&
            !isset($this->options['max'])
        ) {
            throw new Exception\InvalidArgumentException(
                "Range unspecified. Specify the 'in', 'max', 'min', or 'is' option."
            );
        }
    }
}
