<?php
namespace Rails\ActiveModel\Errors;

use Rails\ActiveModel\Exception;

class Errors
{
    use \Rails\ServiceManager\ServiceLocatorAwareTrait;
    
    const BASE_ERRORS_INDEX = 'recordBaseErrors';
    
    protected $errors = array();
    
    /**
     * @var object
     */
    protected $model;
    
    /**
     * Cached full messages.
     */
    protected $fullMessages = [];
    
    public function __construct($model)
    {
        $this->model = $model;
    }
    
    /**
     * A translation key can be passed as $message as array, like [ 'invalid' ].
     * Otherwise, pass an string that'll be taken as a litteral message.
     *
     * If the 'strict' option is present, will cause an exception to be thrown.
     *  If boolean, \Rails\ActiveModel\Exception\StrictValidationException will
     *  be thrown. If a string, it's taken as the name of the exception to throw
     *  instead.
     */
    public function add($attribute, $message = [ 'invalid' ], array $options = [])
    {
        $message = $this->normalizeMessage($attribute, $message, $options);
        
        if (!empty($options['strict'])) {
            if (is_string($options['strict'])) {
                $exception = $options['strict'];
            } else {
                $exception = 'Rails\ActiveModel\Exception\StrictValidationException';
            }
            throw new $exception($this->fullMessage($attribute, $message));
        }
        
        if (!isset($this->errors[$attribute])) {
            $this->errors[$attribute] = [];
        }
        $this->errors[$attribute][] = $message;
    }
    
    public function base($message)
    {
        $this->add(self::BASE_ERRORS_INDEX, $message);
    }
    
    public function on($attribute)
    {
        if (!isset($this->errors[$attribute])) {
            return null;
        } elseif (count($this->errors[$attribute]) == 1) {
            return current($this->errors[$attribute]);
        } else {
            return $this->errors[$attribute];
        }
    }
    
    public function onBase()
    {
        return $this->on(self::BASE_ERRORS_INDEX);
    }
    
    public function clear()
    {
        $this->errors       = [];
        $this->fullMessages = [];
    }
    
    /**
     * $glue is a string that, if present, will be used to
     * return the messages imploded.
     */
    public function fullMessages($glue = null)
    {
        $fullMessages = array();
        
        foreach ($this->errors as $attr => $errors) {
            if ($attr == self::BASE_ERRORS_INDEX) {
                $fullMessages = array_merge($fullMessages, $errors);
            } else {
                foreach ($errors as $message) {
                    $fullMessages[] = $this->fullMessage($attr, $message);
                }
            }
        }
        
        if ($glue !== null) {
            return implode($glue, $fullMessages);
        } else {
            return $fullMessages;
        }
    }
    
    public function fullMessage($attribute, $message)
    {
        if (!isset($this->fullMessages[$attribute][$message])) {
            if (!isset($this->fullMessages[$attribute])) {
                $this->fullMessages[$attribute] = [];
            }
            
            $infl     = self::services()->get('inflector');
            $attrName = $infl->humanize($attribute);
            
            $fullMessage = self::services()->get('i18n')->translate('errors.format', [
                'default'   => '%{attribute} %{message}',
                'attribute' => $attrName,
                'message'   => $message
            ]);
            
            $this->fullMessages[$attribute][$message] = $fullMessage;
        }
        return $this->fullMessages[$attribute][$message];
    }
    
    public function invalid($attribute)
    {
        return isset($this->errors[$attribute]);
    }
    
    public function none()
    {
        return !(bool)$this->errors;
    }
    
    public function any()
    {
        return (bool)$this->errors;
    }
    
    public function all()
    {
        return $this->errors;
    }
    
    public function count()
    {
        $i = 0;
        foreach ($this->errors as $errors) {
            $i += count($errors);
        }
        return $i;
    }
    
    protected function properAttrName($attr)
    {
        $attr = ucfirst(strtolower($attr));
        if (is_int(strpos($attr, '_'))) {
            $attr = str_replace('_', ' ', $attr);
        }
        return $attr;
    }
    
    protected function generateMessage($attribute, $type, array $options = [])
    {
        if ($attribute == self::BASE_ERRORS_INDEX) {
            $properAttr = 'base';
        } else {
            $properAttr = $attribute;
        }
        
        if (method_exists($this->model, 'i18nScope')) {
            $i18nKey = $this->getI18nKey();
            
            $defaults = [
                $this->model->i18nScope() . '.errors.models.' . $i18nKey . '.attributes.' . $properAttr . '.' . $type,
                $this->model->i18nScope() . '.errors.models.' . $i18nKey . '.' . $type
            ];
        } else {
            $defaults = [];
        }
        
        $defaults = array_merge($defaults, [
            'errors.attributes.' . $properAttr . '.' . $type,
            'errors.messages.'   . $type
        ]);
        
        $key   = array_shift($defaults);
        $value = $attribute != self::BASE_ERRORS_INDEX ? $this->model->getProperty($attribute) : null;
        
        $infl = self::services()->get('inflector');
        
        $options = array_merge([
            'default'   => $defaults,
            'model'     => $infl->humanize($infl->underscore(get_class($this->model))),
            'attribute' => $infl->humanize($properAttr),
            'value'     => $value,
            'exception' => true
        ], $options);
        
        $message = self::services()->get('i18n')->translate($key, $options);
        return $message;
    }
    
    protected function normalizeMessage($attribute, $message, $options = [])
    {
        if (!$message) {
            $message = ['invalid'];
        }
        
        switch (true) {
            case is_callable($message):
                return $message();
                
            case is_array($message):
                return $this->generateMessage($attribute, array_shift($message), $options);
        }
        
        return $message;
    }
    
    # TODO: which should be the correct i18n key for classes under namespaces?
    # it was: Foo\Post -> post; which would collide with Post -> post.
    # it is now: Foo\BarPost -> foo.bar_post; BarPost -> bar_post
    protected function getI18nKey()
    {
        return str_replace('/', '.', self::services()->get('inflector')->underscore(get_class($this->model)));
    }
}
