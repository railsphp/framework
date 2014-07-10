<?php
namespace Rails\I18n;

/**
 * This translator uses Loader to automatically load translation from files.
 */
class LoadingTranslator extends Translator
{
    protected $loader;
    
    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;
    }
    
    public function loader()
    {
        return $this->loader;
    }
    
    public function availableLocales()
    {
        return $this->loader->availableLocales();
    }
    
    protected function getTranslation(array $keys, $locale)
    {
        $this->loadTranslations($locale);
        return parent::getTranslation($keys, $locale);
    }
    
    protected function loadTranslations($locale)
    {
        if ($this->loader) {
            $translations = $this->loader->loadTranslations($locale);
            if (is_array($translations)) {
                $this->addTranslations($translations);
            }
        }
    }
}
