<?php
namespace Rails\ActiveModel\Validator;

class ModelValidator extends Validator
{
    protected $validators = [
        'absence'       => 'Rails\ActiveModel\Validator\Validations\AbsenceValidator',
        'acceptance'    => 'Rails\ActiveModel\Validator\Validations\AcceptanceValidator',
        'confirmation'  => 'Rails\ActiveModel\Validator\Validations\ConfirmationValidator',
        'exclusion'     => 'Rails\ActiveModel\Validator\Validations\ExclusionValidator',
        'format'        => 'Rails\ActiveModel\Validator\Validations\FormatValidator',
        'inclusion'     => 'Rails\ActiveModel\Validator\Validations\InclusionValidator',
        'length'        => 'Rails\ActiveModel\Validator\Validations\LengthValidator',
        'numericality'  => 'Rails\ActiveModel\Validator\Validations\NumericalityValidator',
        'presence'      => 'Rails\ActiveModel\Validator\Validations\PresenceValidator',
    ];
    
    protected $validations = [];
    
    public function setValidator($type, $validatorClass)
    {
        $this->validators[$type] = $validatorClass;
    }
    
    public function setValidations(array $validations)
    {
        $this->validations = $validations;
        return $this;
    }
    
    public function addValidation($attribute, $kind, $options)
    {
        if (!isset($this->validations[$attribute])) {
            $this->validations[$attribute] = [];
        }
        $this->validations[$attribute][] = [$kind, $options];
        return $this;
    }
    
    /**
     * Returns true if all validations passed, false otherwise.
     *
     * @return bool
     */
    public function validate($record)
    {
        $record->errors()->clear();
        
        foreach ($this->validations as $attribute => $validators) {
            if ($attribute === 'validateWith') {
                foreach ($validators as $validatorClass => $options) {
                    if (is_int($validatorClass)) {
                        $validatorClass = $options;
                        $options = [];
                    }
                    $validator = new $validatorClass($options);
                    $validator->validate($record);
                }
            } elseif (is_int($attribute)) {
                if (is_string($validators)) {
                    $record->$validators();
                } elseif (is_array($validators)) {
                    $attributes = array_shift($validators);
                    $validators = array_shift($validators);
                    foreach ($attributes as $attribute) {
                        $this->validateAttribute($record, $attribute, $validators);
                    }
                }
            } else  {
                $this->validateAttribute($record, $attribute, $validators);
            }
        }
        
        return $record->errors()->none();
    }
    
    protected function validateAttribute($record, $attribute, $validators)
    {
        foreach ($validators as $kind => $options) {
            if (!isset($this->validators[$kind])) {
                throw new Exception\UnknownValidatorException(
                    sprintf("Unknown validator kind '%s'", $kind)
                );
            }
            if (!is_array($options)) {
                $options = [$options];
            }
            
            $options['attributes'] = [$attribute];
            $validatorName = $this->validators[$kind];
            $validator = new $validatorName($options);
            $validator->validate($record, $attribute, $record->getProperty($attribute));
        }
    }
}
