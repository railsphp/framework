<?php
namespace Rails\ActiveRecord\Observer;

abstract class Observer
{
    /**
     * Defines which ActiveRecord\Base classes will be observed.
     * Defaults to the referred model in the class name.
     *
     * @return string|array
     */
    static public function observe()
    {
        return preg_replace('/Observer$/', '', get_called_class());
    }
}
