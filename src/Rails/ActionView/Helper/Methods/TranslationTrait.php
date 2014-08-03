<?php
namespace Rails\ActionView\Helper\Methods;

trait TranslationTrait
{
    public function t($key, array $options = [], $locale = null)
    {
        return \Rails::getService('i18n')->translate($key, $options, $locale);
    }
}
