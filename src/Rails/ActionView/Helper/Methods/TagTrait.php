<?php
namespace Rails\ActionView\Helper\Methods;

trait TagTrait
{
    public function tag($name, array $options = array(), $open = false, $escape = false)
    {
        return '<' . $name . ' ' . $this->tagOptions($options, $escape) . ($open ? '>' : ' />');
    }
    
    /**
     * $content could be a Closure that can either return a string or
     * echo the contents itself.
     */
    public function contentTag($name, $content, array $options = array(), $escape = false)
    {
        if ($content instanceof \Closure) {
            ob_start();
            $ret     = $content();
            $content = ob_get_clean();
            if ($ret !== null) {
                $content = $ret;
            }
        }
        return $this->contentTagString($name, $content, $options, $escape);
    }
    
    /**
     * Proper tag size.
     * Converts a "size" attribute to width and height attributes
     * if it contains an "x", which is assumed to be like \d+x\d+.
     */
    public function properTagSize(&$attrs)
    {
        if (is_int(strpos($attrs['size'], 'x'))) {
            list ($attrs['width'], $attrs['height']) = explode('x', $attrs['size']);
            unset($attrs['size']);
        }
    }
    
    /**
     * Given an array of HTML attributes, ensures that the "class" index exists
     * and is an array, then adds to it the class passed, which could be either
     * a string or an array. This array of attributes will be flatten before
     * converting it to string.
     *
     * @param array $attrs
     * @param array|string $class
     */
    public function addClass(array &$attrs, $class)
    {
        $this->ensureClass($attrs);
        $attrs['class'][] = $class;
    }
    
    public function ensureClass(array &$attrs)
    {
        if (!isset($attrs['class'])) {
            $attrs['class'] = [];
        } elseif (!is_array($attrs['class'])) {
            $attrs['class'] = [$attrs['class']];
        }
    }
    
    protected function contentTagString($name, $content, array $options, $escape = false)
    {
        return '<' . $name . ' ' . $this->tagOptions($options) . '>' . ($escape ? $this->h($content) : $content) . '</' . $name . '>';
    }
    
    protected function tagOptions(array $options = array(), $escape = false)
    {
        $opts = array();
        if (isset($options['allowBlankAttrs'])) {
            $allowBlankAttrs = true;
            unset($options['allowBlankAttrs']);
        } else {
            $allowBlankAttrs = false;
        }
        
        foreach ($options as $opt => $val) {
            # "class" attribute allows array.
            if ($opt == 'class' && is_array($val)) {
                $val = implode(' ', \Rails\Toolbox\ArrayTools::flatten($val));
            }
            
            if (is_array($val)) {
                $val = implode(' ', $val);
            }
            
            if ((string)$val === '' && !$allowBlankAttrs) {
                continue;
            }
            
            if (is_int($opt)) {
                $opts[] = $val;
            } else {
                $escape && $val = htmlentities($val);
                $opts[] = $opt . '="' . $val . '"';
            }
        }
        return implode(' ', $opts);
    }
}
