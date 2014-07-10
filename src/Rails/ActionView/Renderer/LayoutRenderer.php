<?php
class Rails\ActionView\Renderer;

class LayoutRenderer extends Base
{
    public function __construct(/*Template*/ $layout, $contents)
    {
        
    }
    
    public function contents()
    {
        return $this->contentFor('LAYOUT.CONTENTS');
    }
    
    processRender
}
