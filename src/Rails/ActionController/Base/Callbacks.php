<?php
namespace Rails\ActionController\Base;

use Rails\ActiveSupport\Callbacks\Callbacks as CallbacksBase;

class Callbacks extends CallbacksBase implements \Serializable
{
    public function serialize()
    {
        return serialize([
            'skips'     => $this->skips,
            'chains'    => $this->chains,
            'callbacks' => $this->callbacks
        ]);
    }
    
    public function unserialize($data)
    {
        list (
            $this->skips,
            $this->chains,
            $this->callbacks
        ) = unserialize($data);
    }

    public function beforeAction(array $callbackParams)
    {
        $this->setCallback('action', $callbackParams);
    }
    
    public function aroundAction(array $callbackParams)
    {
        $this->setCallback('action', $callbackParams);
    }
    
    public function afterAction(array $callbackParams)
    {
        $this->setCallback('action', $callbackParams);
    }
}
