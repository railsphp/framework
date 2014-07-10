<?php
namespace Rails\Console\Boris;

/**
 * Extends the original class in order to automatically set a custom inspector.
 */
class Boris extends \Boris\Boris
{
    public function __construct($prompt = 'cli> ', $historyFile = null)
    {
        parent::__construct($prompt, $historyFile);
        $this->setInspector(new ColoredInspector());
    }
}
