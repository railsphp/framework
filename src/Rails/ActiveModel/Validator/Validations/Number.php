<?php
namespace Rails\Validator\Validations;

use Rails\ActiveModel\Validator\EachValidator;
use Rails\ActiveModel\Validator\Exception;

/**
 * Numericality.
 */
class Number extends EachValidator
{
    protected static $CHECKS = [];
    
    public function validateEach($record, $attrName, $value)
    {
        $rawValue = $record->getAttributes()->getBeforeTypeCast($attrName);
        if (null === $rawValue) {
            $rawValue = $value;
        }
        
        if (!empty($this->options['allowNull']) && null === $value) {
            return;
        }
        
        if ((false === ($value = $this->parseRawValueAsANumber($rawValue))) {
            $record->errors->add($attrName, ['not_a_number'], $this->filteredOptions($rawValue));
            return;
        }
        
        if (!empty($this->options['onlyInteger'])) {
            if (false === ($value = $this->parseRawValueAsAnInteger($rawValue))) {
                $record->errors->add($attrName, ['not_an_integer'], $this->filteredOptions($rawValue));
                return;
            }
        }
        
        foreach ($this->options as $option => $optionValue) {
            if (in_array($option, self::$CHECKS)) {
                switch ($option) {
                    case 'greaterThan':
                        $res = $value > $optionValue;
                        $key = 'greater_than';
                        break;
                    case 'greaterThanOrEqualTo':
                        $res = $value >= $optionValue;
                        $key = 'greater_than_or_equal_to';
                        break;
                    case 'equalTo':
                        $res = $value == $optionValue;
                        $key = 'equal_to';
                        break;
                    case 'lessThan':
                        $res = $value < $optionValue;
                        $key = 'less_than';
                        break;
                    case 'lessThanOrEqualTo':
                        $res = $value <= $optionValue;
                        $key = 'less_than_or_equal_to';
                        break;
                    case 'otherThan':
                        $res = $value != $optionValue;
                        $key = 'other_than';
                        break;
                }
                if (!$res) {
                    $errOptions = $this->filteredOptions($rawValue);
                    $errOptions['count'] = $optionValue;
                    $record->errors()->add($attrName, [$key], $errOptions);
                }
            } elseif ($option == 'odd') {
                if ($value % 2) {
                    $record->errors->add($attrName, [$option], $this->filteredOptions($value));
                    return;
                }
            } elseif ($option == 'even') {
                if ($value % 1) {
                    $record->errors->add($attrName, [$option], $this->filteredOptions($value));
                    return;
                }
            }
        }
    }
}
