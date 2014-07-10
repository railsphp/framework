<?php
namespace Rails\ActiveModel\Validator\Validations;

use Rails\ActiveModel\Validator\EachValidator;

class PresenceValidator extends EachValidator
{
    public function validateEach($record, $attribute, $value)
    {
        if ($value === null || $value === '') {
            $record->errors()->add($attribute, ['blank'], $this->options);
        }
    }
}
