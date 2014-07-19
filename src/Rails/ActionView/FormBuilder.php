<?php
namespace Rails\ActionView;

use Closure;
use Rails\ActiveRecord\Base as ARBase;
use Rails\ActiveModel\Collection;

# TODO: this class should use FormTag methods to create tags,
# instead of using Form methods.
class FormBuilder extends Helper
{
    protected $helper;
    
    protected $model;
    
    protected $inputNamespace;
    
    protected $index = 1;
    
    public function __construct(Helper\HelperSet $helperSet, $model, $inputNamespace = null)
    {
        parent::__construct($helperSet);
        $this->model  = $model;
        
        if (!$inputNamespace) {
            if ($model instanceof Collection) {
                $arr   = $model->toArray();
                if ($arr) {
                    $object = get_class(current($arr));
                } else {
                    $object = null;
                }
            } else {
                $object = $model;
            }
            
            if ($object) {
                $inputNamespace = $this->helperSet->baseHelper()->objectName($object);
            }
        }
        $this->inputNamespace = $inputNamespace;
    }
    
    public function runBlock(Closure $block)
    {
        if ($this->model instanceof Collection) {
            $collection = $this->model;
            foreach ($collection as $model) {
                $this->model = $model;
                $block($this);
                $this->index++;
            }
        } else {
            $block($this);
        }
    }
    
    public function index()
    {
        return $this->index;
    }
    
    public function textField($property, array $attrs = [])
    {
        return $this->invoke('textField', [$this->model, $property, $attrs]);
    }
    
    public function hiddenField($property, array $attrs = [])
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->hiddenField($this->inputNamespace, $property, $attrs);
    }
    
    public function passwordField($property, array $attrs = [])
    {
        // $this->helper->setDefaultModel($this->model);
        return $this->helper->passwordField($this->inputNamespace, $property, $attrs);
    }
    
    public function fileField($property, array $attrs = [])
    {
        return $this->invoke('fileField', [$this->model, $property, $attrs]);
    }
    
    public function checkBox($property, array $attrs = [], $checked_value = '1', $unchecked_value = '0')
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->checkBox($this->inputNamespace, $property, $attrs, $checked_value, $unchecked_value);
    }
    
    public function textArea($property, array $attrs = [])
    {
        return $this->invoke(
            'textArea',
            [$this->model, $property, $this->formAttributes('textarea', $attrs)]
        );
    }
    
    public function select($property, $options, array $attrs = [])
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->select($this->inputNamespace, $property, $options, $attrs);
    }
    
    public function radioButton($property, $tag_value, array $attrs = [])
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->radioButton($this->inputNamespace, $property, $tag_value, $attrs);
    }
    
    public function label($property, $text = '', array $attrs = [])
    {
        if (!$text) {
            $text = $this->getService('inflector')->humanize($property);
        }
        
        return $this->base()->contentTag(
            'label',
            $text,
            $this->formAttributes(
                'label',
                array_merge($attrs, ['for' => $this->inputNamespace . '_' . $property])
            )
        );
    }
    
    public function submit($value = null, array $attrs = [])
    {
        if (!$value) {
            $value = $this->valueForSubmit();
        }
        
        return $this->invoke(
            'tag',
            [
                'input',
                $this->formAttributes(
                    'submit',
                    array_merge($attrs, ['value' => $value, 'type' => 'submit'])
                )
            ]
        );
    }
    
    protected function valueForSubmit()
    {
        $inflector = $this->getService('inflector');
        
        $prettyClassName = $inflector->humanize(
            $inflector->underscore(get_class($this->model))
        );
        
        # TODO: use I18n to get the localized value.
        if ($this->model->isNewRecord()) {
            $value = 'Create ' . $prettyClassName;
        } else {
            $value = 'Update ' . $prettyClassName;
        }
        
        return $value;
    }
    
    public function field($type, $property, array $attrs = [])
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->formField($type, $this->inputNamespace, $property, $attrs);
    }
    
    public function object()
    {
        return $this->model;
    }
    
    protected function invoke($method, array $arguments)
    {
        return $this->helperSet->invoke($method, $arguments);
    }
    
    protected function formAttributes($inputType, array $attrs)
    {
        $this->base()->ensureClass($attrs);
        return $attrs;
    }
}
