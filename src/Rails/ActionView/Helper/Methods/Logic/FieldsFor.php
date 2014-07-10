<?php
namespace Rails\ActionView\Helper\Methods\Logic;

class FieldsFor
{
    public function render(
        $baseHelper,
        $recordName,
        $recordObject = null,
        $options = [],
        Closure = $block = null
    ) {
        if ($recordObject instanceof Closure) {
            $block = $recordObject;
            $recordObject = '';
        } elseif ($options instanceof Closure) {
            $block = $options;
            $options = [];
        }
        
        if ($recordName instanceof ARBase || $recordName instanceof Collection) {
            $recordObject = $recordName;
            $recordName = '';
        } elseif (is_string($recordName) && (is_array($recordObject) || $recordObject instanceof Closure)) {
            # $recordName is a string and $recordObject isn't an object;
            # get $recordObject from assigns.
            $recordObject = $baseHelper->getAssign($recordName);
        }
        
        $formBuilder = $baseHelper->helperSet()->invoke(
            'getFormBuilder',
            [
                $recordObject,
                $recordName ?
                    $baseHelper->underscore($recordName) :
                    $recordName
            ]
        );
        
        return $formBuilder->runBlock($block);
    }
}
