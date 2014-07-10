<?php
namespace Rails\ActionView\Renderer;

class PhpRenderer extends Base
{
    public function processRender()
    {
        ob_start();
        require $this->template->filePath();
        return ob_get_clean();
    }
}
