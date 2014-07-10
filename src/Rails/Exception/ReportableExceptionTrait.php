<?php
namespace Rails\Exception;

trait ReportableExceptionTrait
{
    /**
     * Exception title.
     * Title to show instead of the class name.
     * Esthetic purposes only.
     *
     * @var string
     */
    protected $title;
    
    /**
     * Skip exception info.
     *
     * @var bool
     */
    protected $skipInfo = false;
    
    /**
     * Exception's HTTP status code.
     *
     * @var int|string
     */
    protected $status = 500;
    
    public function title()
    {
        return $this->title;
    }
    
    public function skipInfo()
    {
        return $this->skipInfo;
    }
    
    public function status()
    {
        return $this->status;
    }
}
