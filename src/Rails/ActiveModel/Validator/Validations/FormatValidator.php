<?php
namespace Rails\ActiveModel\Validator\Validations;

use Rails\ActiveModel\Validator\EachValidator;
use Rails\ActiveModel\Validator\Exception;

class FormatValidator extends EachValidator
{
    public function validateEach($record, $attribute, $value)
    {
        if (isset($this->options['with'])) {
            $validator = $this->options['with'];
        } else {
            $validator = $this->options['without'];
        }
        
        if (is_callable($validator)) {
            $regexp = $validator($record, $attribute);
        } else {
            $regexp = $validator;
        }
        
        $valid = (bool)preg_match($regexp, $value);
        
        if (isset($this->options['without'])) {
            $valid = !$valid;
        }
        
        if (!$valid) {
            $record->errors()->add($attribute, ['invalid'], array_merge($this->options, ['value' => $value]));
        }
    }
    
    public function validateOptions()
    {
        if (
             (isset($this->options['with']) &&  isset($this->options['without']))  ||
            (!isset($this->options['with']) && !isset($this->options['without']))
        ) {
            throw new Exception\InvalidArgumentException(
                "Either 'with' or 'without' must be supplied, but not both"
            );
        }
        $this->validateOption('with');
        $this->validateOption('without');
    }
    
    protected function validateOption($name)
    {
        if (!isset($this->options[$name])) {
            return;
        }
        
        $option = $this->options[$name];
        
        if (!is_callable($option) && !is_string($option)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Option '%s' must be either callable or a string, %s passed",
                    $name,
                    gettype($option)
                )
            );
        }
    }
}
