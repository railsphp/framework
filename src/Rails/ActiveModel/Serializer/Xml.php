<?php
namespace Rails\ActiveModel\Serializer;

use Rails\ActiveSupport\XmlBuilder;
use Rails\ActiveModel\Base;
use Rails\ActiveModel\Collection;
use Rails\Toolbox\ClassTools;

class Xml
{
    protected $attrElementNames = [];
    
    public function serialize(Base $model, array $options = [])
    {
        $this->normalizeOptions($options);
        $xml = $this->initXml($options);
        
        if (!isset($options['root'])) {
            $options['root'] = $this->properElementName($options, ClassTools::getClassName($model));
        }
        $this->writeModel($xml, $model, $options);
        return $xml->markup();
    }
    
    public function serializeCollection(Collection $collection, array $options = [])
    {
        $this->normalizeOptions($options);
        $xml = $this->initXml($options);
        
        if ($collection->any()) {
            $collection->rewind();
            $first     = $collection->current();
            $inflector = $first->getService('inflector');
            
            if (!isset($options['root'])) {
                $arrayName = $inflector->pluralize(
                    $this->properElementName($options, ClassTools::getClassName($first))
                );
            } else {
                $arrayName = $options['root'];
            }
            
            $options['root'] = $inflector->singularize($arrayName);
            
            $xml->write($arrayName, ['type' => 'array'], function($x) use ($collection, $options) {
                foreach ($collection as $member) {
                    $this->writeModel($x, $member, $options);
                }
            });
        } else {
            $xml->write($this->properElementName($options, 'nullClasses'), ['type' => 'array']);
        }
        
        return $xml->markup();
    }
    
    protected function writeModel($xml, $model, array $options)
    {
        $attributes = $this->getAttributes($model, $options);
        $attrSet    = $model->getAttributes();
        $this->filterAttributes($attributes, $options);
        
        $xml->write($options['root'], function($x) use ($attributes, $options, $attrSet) {
            foreach ($attributes as $attrName => $value) {
                $type = $attrSet->getAttribute($attrName)->type();
                
                $x->write(
                    $this->attrElementName($attrName),
                    function($x) use ($value, $options, $type) {
                        if (!$options['skipTypes']) {
                            $this->setTypeAttribute($x, $type);
                        }
                        
                        if ($value !== null) {
                            $this->properAttrValue($type, $value);
                            $x->plainText($value);
                        } else {
                            $x->setAttr('null', 'true');
                        }
                    }
                );
            }
        });
    }
    
    protected function attrElementName($attrName)
    {
        if (!isset($this->attrElementNames[$attrName])) {
            $this->attrElementNames[$attrName] = str_replace('_', '-', $this->decamelize($attrName, '-'));
        }
        return $this->attrElementNames[$attrName];
    }
    
    protected function properElementName(array $options, $className)
    {
        # Setting dasherize to false will cause the column names to be camelized.
        $elementFormat = 'dasherize';
        if (!empty($options['underscore'])) {
            $elementFormat = 'underscore';
        } elseif (isset($options['dasherize']) && !$options['dasherize']) {
            $elementFormat = 'camelize';
        }
        
        // $modelName = ClassTools::getClassName($model);
        
        switch ($elementFormat) {
            case 'camelize':
                $elementName = lcfirst($className);
                break;
            
            default:
                if ($elementFormat == 'dasherize') {
                    $separator = '-';
                } else {
                    $separator = '_';
                }
                $elementName = $this->decamelize($className, $separator);
                break;
        }
        
        return $elementName;
    }
    
    protected function setTypeAttribute($xml, $type)
    {
        if ($type != 'string' && $type != 'text') {
            if ($type == 'datetime') {
                $type = 'dateTime';
            }
            $xml->setAttr('type', $type);
        }
    }
    
    protected function properAttrValue($type, &$value)
    {
        if (
            ($timestamp = $type == 'timestamp') ||
            is_int(strpos($type, 'date'))       ||
            is_int(strpos($type, 'time'))
        ) {
            if ($timestamp) {
                if (!ctype_digit((string)$value)) {
                    $value = strtotime($value);
                }
            }
            $value = date('c', strtotime($value));
        } elseif ($type == 'boolean') {
            if ($value) {
                $value = 'true';
            } else {
                $value = 'false';
            }
        }
    }
    
    protected function decamelize($string, $separator)
    {
        return trim(
            preg_replace_callback(
                '/[A-Z]/',
                function($m) use ($separator) {
                    return $separator . strtolower($m[0]);
                },
                $string
            ),
            $separator
        );
    }
    
    protected function initXml(array $options)
    {
        $xml = new XmlBuilder();
        
        if (!empty($options['skipInstruct'])) {
            $xml->instruct();
        }
        $xml->setIndent(2);
        
        return $xml;
    }
    
    protected function getAttributes($model, array $options)
    {
        if (!isset($options['attributes'])) {
            $attributes = $model->attributes();
        } else {
            $attributes = $options['attributes'];
            unset($options['attributes']);
        }
        return $attributes;
    }
    
    protected function filterAttributes(&$attributes, array $options)
    {
        if (isset($options['except'])) {
            $attributes = array_diff_key(
                $attributes,
                array_fill_keys($options['except'], null)
            );
        } elseif (isset($options['only'])) {
            $attributes = array_intersect_key(
                $attributes,
                array_fill_keys($options['except'], null)
            );
        }
    }
    
    protected function normalizeOptions(array &$options)
    {
        if (!isset($options['skipTypes'])) {
            $options['skipTypes'] = false;
        }
    }
}
