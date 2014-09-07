<?php
namespace Rails\ActionView\Helper\Methods;

use Closure;
use Rails\ActionView\FormBuilder;
use Rails\ActiveRecord\Base as ARBase;
use Rails\ActiveModel\Collection;
use Rails\ActionView\Exception;

trait FormTrait
{
    /**
     * @param string $objectName
     */
    public function checkBox(
        $objectName,
        $property,
        array $options  = [],
        $checkedValue   = '1',
        $uncheckedValue = '0'
    ) {
        
        if ($this->getObject($objectName)->getProperty($property)) {
            $attrs['checked'] = 'checked';
        }
        
        $attrs['value'] = $checkedValue;
        
        $hidden = $this->tag(
            'input',
            [
                'type'  => 'hidden',
                'name'  => $objectName . '[' . $property . ']',
                'value' => $uncheckedValue
            ]
        );
        
        $checkBox = $this->formField('checkbox', $objectName, $property, $attrs);
        
        return $hidden . "\n" . $checkBox;
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see buildDateFieldValue()
     */
    public function dateField($objectName, $property, array $options = [])
    {
        $this->buildDateFieldValue('Y-m-d', $objectName, $property, $options);
        return $this->formField('date', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see buildDateFieldValue()
     */
    public function datetimeField($objectName, $property, array $options = [])
    {
        $this->buildDateFieldValue('Y-m-dTH:i:s.uo', $objectName, $property, $options);
        return $this->formField('datetime', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see buildDateFieldValue()
     */
    public function datetimeLocalField($objectName, $property, array $options = [])
    {
        $this->buildDateFieldValue('Y-m-dTH:i:s', $objectName, $property, $options);
        return $this->formField('datetime-local', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see buildDateFieldValue()
     */
    public function monthField($objectName, $property, array $options = [])
    {
        $this->buildDateFieldValue('Y-m', $objectName, $property, $options);
        return $this->formField('date', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see buildDateFieldValue()
     */
    public function timeField($objectName, $property, array $options = [])
    {
        $this->buildDateFieldValue('H:i:s.u', $objectName, $property, $options);
        return $this->formField('time', $objectName, $property, $options);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see buildDateFieldValue()
     */
    public function weekField($objectName, $property, array $options = [])
    {
        $this->buildDateFieldValue('Y-\WW', $objectName, $property, $options);
        return $this->formField('date', $objectName, $property, $options);
    }
    
    public function emailField($objectName, $property, array $options = [])
    {
        return $this->formField('email', $objectName, $property, $options);
    }
    
    public function fieldsFor($recordName, $recordObject = null, $options = [], Closure $block = null)
    {
        return (new Logic\FieldsFor())->render(
            $this,
            $recordName,
            $recordObject,
            $options,
            $block
        );
    }
    
    public function fileField($objectName, $property, array $options = [])
    {
        $options['value'] = '';
        return $this->formField('file', $objectName, $property, $options);
    }
    
    /**
     * 
     */
    # TODO: use named paths to get URL.
    public function formFor($model, $options, Closure $block = null)
    {
        return (new Logic\FormFor())->render($this, $model, $options, $block);
    }
    
    public function hiddenField($objectName, $property, array $options = [])
    {
        return $this->formField('hidden', $objectName, $property, $options);
    }
    
    public function label($objectName, $property, $content = null, array $options = [])
    {
        $inf = $this->getService('inflector');
        $underscoredProp = $inf->underscore($property);
        
        if (!isset($options['for'])) {
            $options['for']  = $inf->underscore(is_object($objectName) ? get_class($objectName) : $objectName) . '_' . $underscoredProp;
        }
        
        if (is_array($content)) {
            $options = $content;
            $content = null;
        }
        
        if (!$content) {
            $content = $underscoredProp->humanize();
        }
        
        return $this->contentTag(
            'label',
            $content,
            $options
        );
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see FormTagTrait::numberFieldTag()
     */
    public function numberField($objectName, $property, array $options = [])
    {
        return $this->formField('number', $objectName, $property, $options);
    }
    
    /**
     * By default, the password field has no value. Pass a value in the $options array
     * to show an initial value.
     *
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     */
    public function passwordField($objectName, $property, array $options = [])
    {
        if (!array_key_exists('value', $options)) {
            $options['value'] = '';
        }
        return $this->formField('password', $objectName, $property, $options);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     */
    public function phoneField($objectName, $property, array $options = [])
    {
        return $this->formField('tel', $objectName, $property, $options);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param string $tagValue
     * @param array  $options
     * @return string
     */
    public function radioButton($objectName, $property, $tagValue, array $options = [])
    {
        $options['value'] = $tagValue;
        if (!isset($options['checked'])) {
            if ((string)$this->getObject($objectName)->getProperty($property) == $tagValue) {
                $options[] = 'checked';
            }
        }
        return $this->formField('radio', $objectName, $property, $options);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param string $tagValue
     * @param array  $options
     * @return string
     * @see FormTagTrait::rangeFieldTag()
     */
    public function rangeField($objectName, $property, array $options = [])
    {
        return $this->formField('range', $objectName, $property, $options);
    }
    
    # TODO
    // public function searchField($objectName, $property, array $options = [])
    // {
    // }
    
    public function textArea($objectName, $property, array $options = [])
    {
        $this->normalizeSizeOption($options);
        $options['value'] = $this->getObject($objectName)->getProperty($property);
        $this->escapeIfOption($options['value'], $options);
        return $this->helperSet->invoke('formField', ['textarea', $objectName, $property, $options, true]);
    }
    
    public function textField($objectName, $property, array $options = [])
    {
        return $this->helperSet->invoke('formField', ['text', $objectName, $property, $options]);
    }
    
    public function urlField($objectName, $property, array $options = [])
    {
        return $this->formField('url', $objectName, $property, $options);
    }
    
    /**
     * This method can be overriden in order to use a different
     * class, that extends FormBuilder, to build forms.
     *
     * @return FormBuilder
     */
    public function getFormBuilder($record, $inputNamespace = null)
    {
        return new FormBuilder($this->helperSet, $record, $inputNamespace);
    }
    
    public function formField(
        $fieldType,
        $objectName,
        $property,
        array $attrs = [],
        $contentTag  = false
    ) {
        $object = $this->getObject($objectName);
        
        if (array_key_exists('value', $attrs)) {
            $value = $attrs['value'];
            unset($attrs['value']);
        } else {
            $value = $object->getProperty($property);
        }
        
        $inf = $this->getService('inflector');
        $underscoreProperty = preg_match('/[A-Z]/', $property) ?
                              $inf->underscore($property) :
                              $property;
        
        // $namespace = explode('\\', get_class($object));
        
        // # TODO: Simple underscore
        // $namespace = $inf->underscore(end($namespace));
        $namespace = $this->objectName($object);
        
        $name = $namespace . '['.$underscoreProperty.']';
        
        if (!isset($attrs['id'])) {
            $attrs['id'] = $namespace . '_' . $underscoreProperty;
        }
        
        return $this->helperSet->invoke('formFieldTag', [$fieldType, $name, $value, $attrs, $contentTag]);
    }
    
    /**
     * Returns the underscored version of the class name of an object.
     * If the class name has namespaces, they are removed.
     * This method is intended to be used only by the system. It is
     * public because it's also used by other classes.
     *
     * @param object $object
     * @return string
     */
    public function objectName($object)
    {
        $names = explode('\\', get_class($object));
        # TODO: Simple underscore
        return $this->getService('inflector')->underscore(end($names));
    }
    
    public function getObject($objectName)
    {
        if (is_string($objectName)) {
            $object = $this->helperSet->assigns()->get($objectName);
            if (!is_object($object)) {
                throw new Exception\RuntimeException(sprintf(
                    "Assign '%s' is '%s', expected object",
                    $objectName,
                    gettype($object)
                ));
            }
        } elseif (is_object($objectName)) {
            return $objectName;
        } else {
            throw new Exception\RuntimeException(sprintf(
                "Argument passed must be either string or object, received %s",
                gettype($objectName)
            ));
        }
    }
    
    /**
     * @param string $format
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return void
     * @see FormTagTrait::properDateFieldValue()
     */
    protected function buildDateFieldValue($format, $objectName, $property, array &$options)
    {
        if (empty($options['value'])) {
            $this->properDateFieldValue(
                $format,
                $this->getObject($objectName)->getProperty($property),
                $options
            );
        }
    }
}
