<?php
namespace Rails\I18n;

/**
 * Simple translator.
 *
 * Add translations in arrays, like this:
 * $translator->addTranslations([
 *   'en' => [
 *     'errors' => [
 *       'invalid' => 'The value is invalid',
 *       'unique'  => 'The value must be unique'
 *     ]
 *   ],
 *   'es' => [
 *     'errors' => [
 *       'invalid' => 'El valor es inválido,
 *       'unique'  => 'El valor debe ser único'
 *     ]
 *   ]
 * ]);
 *
 * Then you can translate like this:
 * $translator->translate('errors.invalid.unique', [], 'en'); //-> The value must be unique
 */
class Translator
{
    const DEFAULT_SEPARATOR   = '.';
    
    const INTERPOLATION_REGEX = '/\%\{([^\{]+)\}/';
    
    static protected $RESERVED_KEYS = [
        'default', 'fallbacks', 'exception'
    ];
    
    protected $translations   = [];
    
    /**
     * Default locale for translations.
     */
    protected $defaultLocale;
    
    /**
     * Locales to fallback to if a translation is missing.
     */
    protected $fallbacks = [];
    
    public function __construct($defaultLocale = null, array $fallbacks = [])
    {
        if ($defaultLocale) {
            $this->setDefaultLocale($defaultLocale);
        }
        if ($fallbacks) {
            $this->setFallbacks($fallbacks);
        }
    }
    
    /**
     * Sets default locale for translations.
     *
     * @param string $defaultLocale
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;
    }
    
    public function defaultLocale()
    {
        return $this->defaultLocale;
    }
    
    /**
     * @param array $fallbacks
     */
    public function addFallbacks(array $fallbacks)
    {
        $this->fallbacks = array_merge($this->fallbacks, $fallbacks);
    }
    
    /**
     * @param string $fallback
     */
    public function addFallback(array $fallback)
    {
        $this->fallbacks[] = $fallback;
    }
    
    public function setFallbacks(array $fallbacks)
    {
        $this->fallbacks = $fallbacks;
    }
    
    public function fallbacks()
    {
        return $this->fallbacks;
    }
    
    public function translations()
    {
        return $this->translations;
    }
    
    public function addTranslations($translations)
    {
        $this->translations = array_merge_recursive(
            $this->translations,
            $translations
        );
    }
    
    /**
     * Assuming 'foo.bar' is "Hello foo!":
     * $t->translate('foo.bar', [], 'en'); //-> "Hello foo!"
     *
     * If a default locale is set, the third argument can be ommited:
     * $t->setDefaultLocale('en');
     * $t->translate('foo.bar'); //-> "Hello foo!"
     *
     * Assuming 'foo.bar' is "The time is %{time}", pass the value for %{time} in the
     * second argument:
     * $t->translate('foo.bar', [ 'time' => date('H:i:s') ]); //-> "The time is 10:24:18"
     *
     *
     * Valid $options:
     * - fallbacks => false: ignore fallbacks if translation is missing.
     * - exception => true : throw an exception is translation is missing.
     * - default   => [ ... ]: optional translation fallbacks if $key fails to be
     *   found. See translateDefault().
     * As noted above, values to be interpolated in the translation may be also passed
     * in the $options array. However, some keys held in $RESERVED_KEYS can't be passed as
     * values as they will be taken as options.
     */
    public function translate($key, array $options = [], $locale = null)
    {
        if (null === $locale) {
            if (null === $this->defaultLocale) {
                throw new Exception\RuntimeException(
                    "No default locale set"
                );
            }
            $locale = $this->defaultLocale;
        }
        
        $key   = $this->normalizeKey($key);
        $entry = $this->getTranslation($key, $locale);
        
        if (!$options) {
            $values = [];
        } else {
            if (false === $entry && isset($options['default'])) {
                $entry = $this->translateDefault($options['default'], $locale);
            }
            
            $values  = array_diff_key($options, array_fill_keys(self::$RESERVED_KEYS, null));
            $options = array_intersect_key($options, array_fill_keys(self::$RESERVED_KEYS, null));
        }
        
        if (false === $entry) {
            /**
             * If translation is missing, check fallback locales.
             */
            if ($this->fallbacks && (!isset($options['fallbacks']) || $options['fallbacks'])) {
                $fbOptions = array_merge($options, ['fallbacks' => false, 'exception' => false]);
                $fallbacks = $this->fallbacks;
                
                while (!$entry && $fallbacks) {
                    $fbLocale = array_shift($fallbacks);
                    $entry = $this->translate($key, $fbOptions, $fbLocale);
                }
            }
        }
        
        if (false !== $entry) {
            if ($values) {
                $entry = $this->interpolate($entry, $values);
            }
        } else {
            $message = sprintf(
                "translation missing: %s%s%s",
                $locale,
                self::DEFAULT_SEPARATOR,
                implode(self::DEFAULT_SEPARATOR, $key)
            );
            
            if (isset($options['exception']) && $options['exception']) {
                if (isset($options['default'])) {
                    $message .= " with defaults:\n";
                    if (is_array($options['default'])) {
                        $message .= implode("\n", $options['default']);
                    } else {
                        $message .= $options['default'];
                    }
                }
                
                throw new Exception\TranslationMissingException(ucfirst($message));
            } else {
                $entry = $message;
            }
        }
        
        return $entry;
    }
    
    /**
     * Alias of translate().
     */
    public function t($key, array $options = [], $locale = null)
    {
        return $this->translate($key, $options, $locale);
    }
    
    protected function normalizeKey($key, array $options = [])
    {
        if (is_array($key)) {
            return $key;
        } elseif (!is_string($key)) {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "\$key must be either array or string, %s passed",
                    gettype($key)
                )
            );
        }
        $separator = isset($options['separator']) ?
                        $options['separator'] :
                        self::DEFAULT_SEPARATOR;
        return explode($separator, $key);
    }
    
    /**
     * Checks "fallback translations" passed to a translation.
     * If the value of $default is an array, the values will be taken as translation keys
     * to search for. If the 'literal' array key is set, it will be taken as a literal
     * translation text (not as a key) that will be returned if all default keys (if any)
     * aren't found.
     * If the value of $default is a string, it will be taken as a 'literal' translation.
     * If everything fails and no literal text is passed, false is retruned.
     *
     * @return string|bool
     * @throw Exception\InvalidArgumentException
     */
    protected function translateDefault($default, $locale)
    {
        if (is_array($default)) {
            foreach ($default as $def) {
                $keys = $this->normalizeKey($def);
                
                if (is_string($tr = $this->getTranslation($keys, $locale))) {
                    return $tr;
                }
            }
        } elseif (is_string($default)) {
            return $default;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "Translation default must be either array or string, %s passed",
                    gettype($default)
                )
            );
        }
        
        if (isset($default['literal'])) {
            return $default['literal'];
        }
        
        return false;
    }
    
    protected function interpolate($text, array $values)
    {
        if (preg_match_all(self::INTERPOLATION_REGEX, $text, $ms)) {
            foreach ($ms[0] as $k => $m) {
                $valueName = $ms[1][$k];
                if (!isset($values[$valueName])) {
                    throw new Exception\RuntimeException(
                        sprintf(
                            "Missing interpolation argument '%s'",
                            $valueName
                        )
                    );
                }
                $text = str_replace($m, $values[$valueName], $text);
            }
        }
        return $text;
    }
    
    protected function getTranslation(array $keys, $locale)
    {
        if (!isset($this->translations[$locale])) {
            return false;
        }
        
        $tr = $this->translations[$locale];
        
        foreach ($keys as $key) {
            if (array_key_exists($key, $tr)) {
                $tr = $tr[$key];
            } else {
                /**
                 * Translation not found.
                 */
                return false;
            }
        }
        
        if (is_array($tr)) {
            /**
             * This may be a translation that got merged with another one, resulting
             * in an array; get the last value.
             */
            $tr = end($tr);
        } elseif ($tr === null) {
            $tr = '';
        }
        
        return $tr;
    }
}
