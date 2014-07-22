<?php
namespace Rails\ActiveRecord\Validator\Validations;

use Rails\ActiveModel\Validator\Validations\PresenceValidator as Base;

class PresenceValidator extends Base
{
    public function validateEach($record, $attribute, $value)
    {
        if ($record->getAssociations()->exists($attribute)) {
            if (!(bool)$record->getAssociations()->load($record, $attribute)) {
                $record->errors()->add($attribute, ['blank'], $this->options);
            }
            return;
        }
        parent::validateEach($record, $attribute, $value);
    }
}
