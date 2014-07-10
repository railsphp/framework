<?php
namespace Rails\Validator\Validations;

class Exclusion extends Clusivity
{
    protected function validateClusivity($record, $attribute, $value, $haystack, $strict)
    {
        if (in_array($value, $haystack, $strict)) {
            $record->errors()->add($attribute, ['exclusion']);
        }
    }
}
