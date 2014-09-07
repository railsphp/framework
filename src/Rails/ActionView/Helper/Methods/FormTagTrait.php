<?php
namespace Rails\ActionView\Helper\Methods;

use Closure;
use Rails\ActiveModel\Collection;
use Rails\ActiveModel\Attributes\AccessibleProperties;
use Rails\ActiveModel\Attributes\Attributes;
use Rails\ActionView\FormBuilder;
use Rails\ActionView\Exception;

/**
 * These are the methods that create form-related tags. FormTrait and FormBuilder
 * end up here, so if it's required to customize forms tags, it can be done by
 * creating methods with the same name as these in a Helper.
 */
trait FormTagTrait
{
    /**
     * $content may be:
     * * Closure (no options)
     * * Array (assumed $options is a Closure)
     *
     * Special options:
     * * `data`: An array that accept two sub-options, both of which are handled with
     *   Rails Javascript:
     *   * `disableWith`: Upon form submit, the button will be disabled and this value
     *     be shown as the value for the button (for example, "Please wait...").
     *   * `confirm`: Text to prompt the user with. If the user cancels, no action is
     *     taken, otherwise the form is submitted.
     *
     * The button tag is created without a "name" attribute.
     */
    public function buttonTag($content, $options = [], Closure $block = null)
    {
        if ($content instanceof Closure) {
            $block = $content;
            $content = null;
        } elseif (is_array($content)) {
            $block   = $options;
            $options = $content;
        }
        
        if ($block) {
            $content = $this->runContentBlock($block);
        }
        
        if (is_array($options)) {
            $this->normalizeButtonOptions($options);
        } else {
            $options = [];
        }
        
        if (!isset($options['type'])) {
            $options['type'] = 'submit';
        }
        
        return $this->formFieldTag('button', null, $content, $options, true);
    }
    
    public function checkBoxTag($name, $value = '1', $checked = false, array $options = [])
    {
        if ($checked) {
            $options['checked'] = 'checked';
        }
        return $this->formFieldTag('checkbox', $name, $value, $options);
    }
    
    // TODO
    // public function colorFieldTag($name, $value = null, array $options = [])
    // {
        // return $this->formFieldTag('color', $name, $value, $options);
    // }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see properDateFieldValue()
     */
    public function dateFieldTag($objectName, $property, array $options = [])
    {
        $this->properDateFieldValue('Y-m-d', $objectName, $property, $options);
        return $this->formFieldTag('date', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see properDateFieldValue()
     */
    public function datetimeFieldTag($objectName, $property, array $options = [])
    {
        $this->properDateFieldValue('Y-m-dTH:i:s.uo', $objectName, $property, $options);
        return $this->formFieldTag('datetime', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see properDateFieldValue()
     */
    public function datetimeLocalFieldTag($objectName, $property, array $options = [])
    {
        $this->properDateFieldValue('Y-m-dTH:i:s', $objectName, $property, $options);
        return $this->formFieldTag('datetime-local', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see properDateFieldValue()
     */
    public function monthFieldTag($objectName, $property, array $options = [])
    {
        $this->properDateFieldValue('Y-m', $objectName, $property, $options);
        return $this->formFieldTag('date', $objectName, $property, $attrs);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see properDateFieldValue()
     */
    public function timeFieldTag($objectName, $property, array $options = [])
    {
        $this->properDateFieldValue('H:i:s.u', $objectName, $property, $options);
        return $this->formFieldTag('time', $objectName, $property, $options);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     * @see properDateFieldValue()
     */
    public function weekFieldTag($objectName, $property, array $options = [])
    {
        $this->properDateFieldValue('Y-\WW', $objectName, $property, $options);
        return $this->formFieldTag('date', $objectName, $property, $options);
    }
    
    public function emailFieldTag($name, $value = null, array $options = [])
    {
        return $this->formFieldTag('email', $name, $value, $options);
    }
    
    public function fieldSetTag($legend, $options, Closure $block = null)
    {
        if ($legend instanceof Closure) {
            $options = [];
            $block = $legend;
            $legend = null;
        } elseif ($options instanceof Closure) {
            $block = $options;
            $options = [];
        }
        
        if ($legend) {
            $contents = $this->contentTag('legend', $legend);
        } else {
            $contents = '';
        }
        
        $contents .= $this->runContentBlock($block);
        
        return $this->formFieldTag('fieldset', null, $contents, $options, true);
    }
    
    public function fileFieldTag($name, array $options = [])
    {
        return $this->formFieldTag('file', $name, '', $options);
    }
    
    public function formTag($urlOptions = null, $options = [], Closure $block = null)
    {
        list ($urlOptions, $options, $block) = $this->helperSet->invoke(
            'normalizeFormTagOptions',
            [$urlOptions, $options, $block]
        );

        ob_start();
        $method = strtolower($options['method']);
        
        if ($method != 'get') {
            $options['method'] = 'post';
        }
        
        if ($method != 'get' && $method != 'post') {
            echo $this->hiddenFieldTag('_method', $method, ['id' => '']);
        }
        
        if (isset($options['remote'])) {
            $options['data-remote'] = 'true';
            unset($options['remote']);
        }
        
        // TODO: enable authenticityToken
        // if (!isset($options['authenticityToken'])) {
            // $options['authenticityToken'] = true;
        // }
        // if ($options['authenticityToken']) {
            // if ($this->session) {
                // $this->session->set('authenticityToken', );
            // }
        // }
        
        $block();
        return $this->contentTag('form', ob_get_clean(), $options);
    }

    public function hiddenFieldTag($name, $value = null, array $options = [])
    {
        return $this->formFieldTag('hidden', $name, $value, $options);
    }
    
    public function iamgeSubmitTag($source, array $options = [])
    {
        $imageUrl = $this->assetPath($source, ['assetDir' => 'images']);
        if (!isset($options['alt'])) {
            $options['alt'] = $this->getService('inflector')->humanize(
                pathinfo($source, PATHINFO_FILENAME)
            );
        }
        if (isset($options['confirm'])) {
            $options['data-confirm'] = $options['confirm'];
            unset($options['confirm']);
        }
        return $this->formFieldTag('image', null, null, $options);
    }
    
    public function labelTag($name, $content = null, array $options = [])
    {
        $options['for'] = $name;
        
        if (is_array($content)) {
            $options = $content;
            $content = null;
        }
        if (!$content) {
            $content = $inf->humanize($name);
        }
        
        return $this->formFieldTag('label', null, $content, $options, true);
    }
    
    /**
     * @param string $objectName
     * @param string $property
     * @param array  $options
     * @return string
     */
    public function numberFieldTag($name, $value, array $options = [])
    {
        $this->normalizeNumberOption($options);
        return $this->formFieldTag('number', $name, $value, $options);
    }
    
    public function passwordFieldTag($name = 'password', $value = null, array $options = [])
    {
        return $this->formFieldTag('password', $name, $value, $options);
    }
    
    public function phoneFieldTag($name, $value = null, array $options = [])
    {
        return $this->formFieldTag('tel', $name, $value, $options);
    }
    
    public function radioTag($name, $value, $checked = false, array $options = [])
    {
        if ($checked) {
            $options[] = 'checked';
        }
        if (empty($options['id'])) {
            $options['id'] = $name . '_' . $value;
        }
        return $this->formFieldTag('radio', $name, $value, $options);
    }

    /**
     * @param string $name
     * @param string $value
     * @param array  $options
     * @return string
     * @see numberFieldTag()
     */
    public function rangeFieldTag($name, $value, array $options = [])
    {
        $this->normalizeNumberOption($options);
        return $this->formFieldTag('range', $name, $value, $options);
    }
    
    public function searchFieldTag($name, $value = null, array $options = [])
    {
        return $this->formFieldTag('tel', $name, $value, $options);
    }
    
    /**
     * $optionTags may be closure, collection or an array of name => values.
     */
    public function selectTag($name, $optionTags, array $options = [])
    {
        # This is found also in Form::select()
        if (!is_string($optionTags)) {
            if (is_array($optionTags) && isset($optionTags[0]) && isset($optionTags[1])) {
                list ($optionTags, $value) = $optionTags;
            } else {
                $value = null;
            }
            $optionTags = $this->optionsForSelect($optionTags, $value);
        }
        
        $this->normalizeSelectOptions($options, $optionTags);
        
        return $this->formFieldTag('select', $name, $optionTags, $options, true);
    }

    public function submitTag($value = 'Save changes', array $options = [])
    {
        $this->normalizeButtonOptions($options);
        return $this->formFieldTag('submit', null, $value, $options);
    }
    
    public function textAreaTag($name, $value, array $options = [])
    {
        $this->normalizeSizeOption($options);
        $this->escapeIfOption($value, $options);
        return $this->formFieldTag('textarea', $name, $value, $options, true);
    }
    
    public function textFieldTag($name, $value = null, array $options = [])
    {
        if (is_array($value)) {
            $options = $value;
            $value   = null;
        }
        return $this->formFieldTag('text', $name, $value, $options);
    }
    
    public function urlFieldTag($name, $value = null, array $options = [])
    {
        if (is_array($value)) {
            $options = $value;
            $value   = null;
        }
        return $this->formFieldTag('url', $name, $value, $options);
    }
    
    public function utf8EnforcerTag()
    {
        return $this->formFieldTag('hidden', 'utf8', '&#x2713;');
    }
    
    /**
     * The methods below are public because they may be called from a custom
     * helper.
     */
    
    /**
     * This method can be used to create an arbitrary form field of type $fieldType.
     */
    public function formFieldTag($fieldType, $name = null, $value, array $attributes = [], $contentTag = false)
    {
        list ($name, $value, $attributes) = $this->helperSet->invoke(
                'normalizeFormFieldTagParams',
                [$name, $value, $attributes, $fieldType]
            );
        
        if ($contentTag) {
            return $this->contentTag($fieldType, $value, $attributes);
        } else {
            $attributes['type'] = $fieldType;
            if ($value !== '') {
                $attributes['value'] = $value;
            }
            return $this->tag('input', $attributes);
        }
    }
    
    /**
     * This method can be "extended" to edit attributes for form-related tags.
     */
    public function normalizeFormFieldTagParams($name, $value, array $attributes, $fieldType = null)
    {
        if (!isset($attributes['id'])) {
            $attributes['id'] = trim(
                str_replace(['[', ']', '()', '__'], ['_', '_', '', '_'], $name),
                '_'
            );
        }
        if ($name) {
            $attributes['name'] = $name;
        }
        return [$name, (string)$value, $attributes];
    }
    
    /**
     * @param array  $options
     * @param string $optionTags
     */
    protected function normalizeSelectOptions(array &$options, &$optionTags)
    {
        if (isset($options['includeBlank'])) {
            if ($options['includeBlank'] === true) {
                $text = '';
            } else {
                $text = $options['includeBlank'];
            }
            
            $optionTags = $this->contentTag(
                'option',
                $text,
                ['value' => '', 'allowBlankAttrs' => true]
            ) . "\n" . $optionTags;
            unset($options['includeBlank'], $options['prompt']);
        }
        if (isset($options['prompt'])) {
            if (is_bool(strpos($optionTags, 'selected="1"'))) {
                $optionTags = $this->contentTag(
                    'option',
                    $options['prompt'],
                    ['value' => '', 'allowBlankAttrs' => true]
                ) . "\n" . $optionTags;
            }
            unset($options['prompt']);
        }
        
        $options['value'] = $optionTags;
    }
    
    /**
     * This method may be "extended" to customize form options such as HTML attributes.
     */
    public function normalizeFormTagOptions($urlOptions, $options, $block)
    {
        if (!$urlOptions || !$options || !$block) {
            if ($urlOptions instanceof Closure) {
                $block     = $urlOptions;
                $urlOptions = null;
                $options     = [];
            } elseif (is_array($urlOptions) && is_string(key($urlOptions))) {
                $block = $options;
                $options = $urlOptions;
                $urlOptions = null;
            } elseif ($options instanceof Closure) {
                $block = $options;
                $options = [];
            }
        }
        
        if (!$block instanceof Closure) {
            throw new Exception\BadMethodCallException(
                "One of the arguments for formTag must be a Closure"
            );
        }
        
        if (empty($options['method'])) {
            $options['method'] = 'post';
        }
        
        if (!empty($options['multipart'])) {
            $options['enctype'] = 'multipart/form-data';
            unset($options['multipart']);
        }
        
        if ($urlOptions) {
            if (!$this->isUrl($urlOptions)) {
                $options['action'] = $this->urlFor($urlOptions);
            } else {
                $options['action'] = $urlOptions;
            }
        }
        
        return [$urlOptions, $options, $block];
    }

    protected function getPropertyGetter($modelClass, $propName)
    {
        if (Attributes::isClassAttribute($modelClass, $propName)) {
            return function ($model) use ($propName) {
                return $model->getAttribute($propName);
            };
        } else {
            $property = AccessibleProperties::getProperty($modelClass, $propName);
            if ($property === true) {
                return function ($model) use ($propName) {
                    $model->$propName;
                };
            } elseif ($property[0]) {
                return function ($model) use ($propName) {
                    $model->$propName();
                };
            }
        }
        
        throw new Exception\RuntimeException(
            sprintf(
                "Unknown attribute or property %s::%s",
                $modelClass,
                $propName
            )
        );
    }
    
    protected function normalizeButtonOptions(array &$options)
    {
        if (isset($options['data'])) {
            if (isset($options['data']['disableWith'])) {
                $options['data-disable-with'] = $options['data']['disableWith'];
            }
            if (isset($options['data']['confirm'])) {
                $options['data-confirm'] = $options['data']['confirm'];
            }
            unset($options['data']);
        }
    }
    
    /**
     * Determines a proper value for a datetime-related field. If $value is instance of DateTime,
     * it will be formatted according to $format, otherwise, the value is passed directly.
     * If the "value" key is present in $options, no action is taken.
     *
     * @param mixed $value
     * @param array  $options
     * @return void
     */
    protected function properDateFieldValue($format, $value, array &$options)
    {
        if (empty($options['value'])) {
            if ($value instanceof \DateTime) {
                $value = $value->format($format);
            }
            $options['value'] = $value;
        }
    }
    
    protected function escapeIfOption(&$string, array &$options)
    {
        if (isset($options['escape'])) {
            $escape = $options['escape'];
        } else {
            $escape = true;
            unset($options['escape']);
        }
        if ($escape) {
            $string = $this->h($string);
        }
    }
    
    /**
     * Runs a Closure that can either output content itself or
     * return the content as a string.
     *
     * @param Closure $block
     * @return string
     */
    protected function runContentBlock(Closure $block)
    {
        ob_start();
        $result   = $block();
        $contents = ob_get_clean();
        return $result ?: $contents;
    }
    
    /**
     * Checks for the "in" option, which is expected to be an array like
     * `[1, 10]`, and is splitted into "min" and "max".
     *
     * @return void
     */
    protected function normalizeNumberOption(array &$options)
    {
        if (isset($options['in'])) {
            if (
                is_array($options['in']) &&
                isset($options['in'][0]) &&
                isset($options['in'][1])
            ) {
                list($options['min'], $options['max']) = $options['in'];
            }
            unset($options['in']);
        }
    }
    
    /**
     * Checks for the "size" option, which is expected to be something like
     * `50x20`, and is splitted into "cols" and "rows".
     *
     * @return void
     */
    protected function normalizeSizeOption(array &$options)
    {
        if (isset($options['size'])) {
            $size = explode('x', $options['size']);
            if (
                isset($size[0]) &&
                isset($size[1]) 
            ) {
                list($options['cols'], $options['rows']) = $size;
            }
            unset($options['size']);
        }
    }
}
