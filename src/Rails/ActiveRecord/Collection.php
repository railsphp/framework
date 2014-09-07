<?php
namespace Rails\ActiveRecord;

use Rails\ActiveModel\Collection as BaseCollection;
use Zend\Paginator\Adapter\DbSelect as Paginator;

class Collection extends BaseCollection
{
    /**
     * @var int
     */
    protected $page;
    
    /**
     * @var int
     */
    protected $perPage;
    
    /**
     * @var Paginator
     */
    protected $paginator;
    
    /**
     * The total rows found.
     *
     * @var int
     */
    protected $totalRows;
    
    public function page()
    {
        return $this->page;
    }
    
    public function perPage()
    {
        return $this->perPage;
    }
    
    public function paginator()
    {
        return $this->paginator;
    }
    
    public function totalRows()
    {
        if ($this->totalRows === null) {
            $this->totalRows = (int)$this->paginator->count();
        }
        return $this->totalRows;
    }
    
    public function totalPages()
    {
        return ceil($this->totalRows / $this->perPage);
    }
    
    public function setPage($page)
    {
        $this->page = (int)$page;
    }
    
    public function setPerPage($perPage)
    {
        $this->perPage = (int)$perPage;
    }
    
    public function setTotalRows($totalRows)
    {
        $this->totalRows = (int)$totalRows;
    }
    
    public function setPaginator(Paginator $paginator)
    {
        $this->paginator = $paginator;
    }
}
