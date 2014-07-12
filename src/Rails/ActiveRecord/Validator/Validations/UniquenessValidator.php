<?php
namespace Rails\ActiveRecord\Validator\Validations;

use Rails\ActiveModel\Validator\EachValidator;

class UniquenessValidator extends EachValidator
{
    public function validateEach($record, $attribute, $value)
    {
        $rel = $record::where([$attribute => $value]);
        
        if ($record->isPersisted()) {
            $rel->where()->not([$record::primaryKey() => $record->id()]);
        }
        
        if ($rel->count()) {
            $record->errors()->add($attribute, ['uniqueness'], $this->options);
        }
    }
}
