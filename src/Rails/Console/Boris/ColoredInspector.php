<?php
namespace Rails\Console\Boris;

use Rails\ActiveModel\Base as Model;
use Rails\ActiveSupport\Inflector\Word;

# TODO: update description
/**
 * Adds a special dump format for classes extending Model. To achieve this,
 * the _dumpStructure private method is invoked using a reflection.
 */
class ColoredInspector extends \Boris\ColoredInspector
{
    protected $dumpStructure;
    
    public function _dump($value)
    {
        if ($value instanceof Model) {
            return $this->dumpActiveModel($value);
        } elseif ($value instanceof Word) {
            $value = (string)$value;
        }
        
        return parent::_dump($value);
    }
    
    protected function dumpActiveModel(Model $model)
    {
        if (!$this->dumpStructure) {
            $this->setReflections();
        }
        
        $attributes = [];
        foreach ($model->attributes() as $attrName => $attrValue) {
            if (is_object($attrValue)) {
                $attrValue = (string)$attrValue;
            }
            $attributes[$attrName] = $attrValue;
        }
        
        return $this->dumpStructure->invokeArgs(
            $this,
            [
                sprintf('model(%s)', get_class($model)),
                [
                    'attributes' => $attributes,
                    'properties' => $this->objectVars($model)
                ]
            ]
        );
    }
    
    protected function setReflections()
    {
        $refl = new \ReflectionClass('Boris\ColoredInspector');
        
        $dumpStructure = $refl->getMethod('_dumpStructure');
        $dumpStructure->setAccessible(true);
        
        $this->dumpStructure = $dumpStructure;
    }
}
