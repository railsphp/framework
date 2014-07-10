<?php
namespace Rails\Xml;

use Closure;
use XMLWriter;

/**
 * This class requires the libxml PHP extension (available by default).
 */
class XmlBuilder
{
    /**
     * @var XMLWriter
     */
    protected $writer;
    
    public function __construct()
    {
        $this->writer = new XMLWriter();
        $this->writer->openMemory();
    }
    
    /**
     * @see write()
     */
    public function __call($method, $params)
    {
        array_unshift($params, $method);
        return call_user_func_array([$this, 'write'], $params);
    }
    
    /**
     * @see markup()
     */
    public function __toString()
    {
        return $this->markup();
    }
    
    /**
     * @return XMLWriter
     */
    public function writer()
    {
        return $this->writer;
    }
    
    /**
     * Get the markup built so far.
     *
     * @return string
     */
    public function markup()
    {
        return $this->writer->flush(false);
    }
    
    /**
     * Insert processing instruction.
     *
     *     $xml->instruct(); // <?xml version="1.0" encoding="UTF-8"?>
     *
     * @param string $version
     * @param string $encoding
     * @return self
     */
    public function instruct($version = '1.0', $encoding = 'UTF-8')
    {
        $this->writer->startDocument($version, $encoding);
        return $this;
    }
    
    /**
     * Sets number of spaces uses for indentation. Set 0 to
     * disable indentation.
     *
     * @param int $spacesNum
     */
    public function setIndent($spacesNum)
    {
        if ($spacesNum) {
            $this->writer->setIndent(true);
            $this->writer->setIndentString(str_repeat(' ', (int)$spacesNum));
        } else {
            $this->writer->setIndent(false);
        }
        return $this;
    }
    
    /**
     * Surround $text between comment tags.
     *
     * ```
     * $xml->comment('Comment!'); // <!-- Comment! -->
     * ```
     *
     * @return self
     */
    public function comment($text)
    {
        $this->writer->writeComment(' ' . $text . ' ');
        return $this;
    }
    
    /**
     * Surround $text between comment tags.
     * Adds a trailing new line after the end tag.
     *
     * ```
     * $xml->cData('cData!'); // <![CDATA[cData!]]>
     * ```
     *
     * @return self
     */
    public function cData($text)
    {
        $this->writer->writeCData($text);
        $this->writer->text("\n");
        return $this;
    }
    
    /**
     * Write plain text.
     *
     * @return self
     */
    public function plainText($text)
    {
        $this->writer->text($text);
        return $this;
    }
    
    /**
     * Sets attributes to the current element. Note that this will fail
     * if content was written to the element:
     *
     * ```
     * $xml->div(function($x) {
     *     $x->setAttrs(['foo' => 'bar']); // works
     *     $x->p('Some content');          // content added
     *     $x->setAttr(['bar' => 'baz']);  // won't work
     * });
     * // <div foo="bar"><p>Some content</p></div>
     * ```
     *
     * @return self
     */
    public function setAttrs(array $attrs)
    {
        foreach ($attrs as $attrName => $value) {
            $this->writer->writeAttribute($attrName, $value);
        }
        return $this;
    }
    
    /**
     * Sets a single attribute.
     *
     * @see setAttrs()
     */
    public function setAttr($attrName, $value)
    {
        return $this->setAttrs([$attrName => $value]);
    }
    
    /**
     * Write an element with either attributes, content, both or none.
     * $content may be either string or Closure. If Closure, the XmlBuilder
     * object will be passed as first argument.
     * $attributes is an array of attrName => value pairs. It may be
     * ommited by passing $content as second argument.
     *
     * ```php
     * $xml->write('img', ['src' => 'image.png']);
     * // <img src="image.png"/>
     *
     * // Any unknown method will be derived to write(), so we can do:
     * $xml->div(['class' => 'foo'], function($x) {
     *     $x->p('Lorem ipsum');
     *     $x->br();
     * });
     * // <div class="foo"><p>Lorem ipsum</p><br/></div>
     * ```
     *
     * @param string $elementName
     * @param array|string|Closure $attributes
     * @param string|Closure $content
     * @return self
     */
    public function write($elementName, $attributes = null, $content = null)
    {
        if ($attributes && !is_array($attributes)) {
            $content    = $attributes;
            $attributes = [];
        }
        
        if ($content && (!is_string($content) && !$content instanceof Closure)) {
            throw new \InvalidArgumentException(sprintf(
                '$content must be either string or instance of Closure, %s passed',
                gettype($content)
            ));
        }
        
        $this->writer->startElement($elementName);
        
        if ($attributes) {
            $this->setAttrs($attributes);
        }
        
        if ($content) {
            if (is_string($content)) {
                $this->writer->text($content);
            } else {
                $content($this);
            }
        }
        
        $this->writer->endElement();
        
        return $this;
    }
}
