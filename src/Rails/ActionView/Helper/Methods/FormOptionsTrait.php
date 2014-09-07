<?php
namespace Rails\ActionView\Helper\Methods;

use Closure;
use Rails\ActiveModel\Collection;
use Rails\ActiveRecord\Relation\RelationInterface;

trait FormOptionsTrait
{
    // public function collectionCheckBoxes(
        // $objectName,
        // $property,
        // $collection,
        // $valueProperty,
        // $textProperty,
        // $options = [],
        // $htmlOptions = [],
        // $block = null
    // ) {
        
    // }
    
    /**
     * Accepts collection as $options in index 0, in index 1 the option name and in index 2 the value
     * which are the properties of the models that will be used.
     * Example: [ Category::all(), 'name', 'id' ]
     * The second and third indexes can be either:
     *  - An attribute name
     *  - A public property
     *  - A method of the model that will return the value for the option/name
     *
     * If $options is a Closure, it must return the options as array of option=>value pairs.
     *
     * Otherwise, pass an array of option=>value pairs.
     */
    public function optionsForSelect($options, $selectValue = '')
    {
        if ($options instanceof Closure) {
            $options = $options();
        } elseif (
            is_array($options)              &&
            isset($options[0])              &&
            ($options[0] instanceof Collection || $options[0] instanceof RelationInterface)
        ) {
            list($models, $optionName, $valueName) = $options;
            $models = $models->toArray();
            $options = [];
            
            if ($models) {
                $modelClass = get_class($models[0]);
                
                $optionGetter = $this->getPropertyGetter($modelClass, $optionName);
                $valueGetter  = $this->getPropertyGetter($modelClass, $valueName);
                
                foreach ($models as $m) {
                    $options[$optionGetter($m)] = $valueGetter($m);
                }
            }
        }
        
        $selectValue = (string)$selectValue;
        $tags = [];
        
        foreach ($options as $name => $value) {
            $value = (string)$value;
            $tags[] = $this->formFieldTag(
                'option',
                null,
                $name,
                ['selected' => $value == $selectValue ? '1' : '', 'id' => '', 'value' => $value],
                true
            );
        }
        
        return implode("\n", $tags);
    }
    
    public function select($objectName, $property, $optionTags, array $options = [])
    {
        if (array_key_exists('value', $options)) {
            $value = $options['value'];
            unset($options['value']);
        } else {
            $value = $this->getObject($objectName)->getProperty($property);
        }
        
        if (!is_string($optionTags)) {
            $optionTags = $this->optionsForSelect($optionTags, $value);
            $this->normalizeSelectOptions($options, $optionTags);
        }
        
        return $this->helperSet->invoke('formField', ['select', $objectName, $property, $options, true]);
    }
}
