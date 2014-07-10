<?php
namespace Rails\ActiveModel\Validator;

use Rails\ActiveModel\Errors\Errors;

trait ValidableModelTrait
{
    protected $modelValidator;
    
    protected $validationContext;
    
    /**
     * @var Errors
     */
    protected $errors;
    
    public function validator()
    {
        if (!$this->modelValidator) {
            $this->setupValidator();
        }
        return $this->modelValidator;
    }
    
    public function isValid($context = null)
    {
        $this->validationContext = $context;
        $this->errors()->clear();
        $this->validator()->validate($this);
        return $this->errors()->none();
    }
    
    public function IsInvalid($context = null)
    {
        return !$this->isValid($context);
    }
    
    public function errors()
    {
        if (!$this->errors) {
            $this->errors = new Errors($this);
        }
        return $this->errors;
    }
    
    public function isCreateContext()
    {
        return $this->validationContext == 'create';
    }
    
    public function isUpdateContext()
    {
        return $this->validationContext == 'update';
    }
    
    # TODO: get validations from methods *Validations()
    protected function getAllValidations()
    {
        $validations = $this->validations();
        return $validations;
    }
    
    protected function setupValidator()
    {
        $this->modelValidator = new ModelValidator();
        $this->modelValidator->setValidations(
            $this->getAllValidations()
        );
    }
    
    # TODO: support multiple attribute assigment like
    # [ ['name', 'email'], 'presence' => true, 'uniqueness' => true ]
    # all validations will be set to both name and email.
    protected function validations()
    {
        return [];
    }
}
