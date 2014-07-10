<?php
namespace Rails\ActionDispatch\Http;

class UploadedFile
{
    protected $name;
    
    protected $type;
    
    protected $path;
    
    protected $size;
    
    protected $errorCode;
    
    public function __construct(array $data)
    {
        $this->name  = $data['name'];
        $this->type  = $data['type'];
        $this->path  = $data['tmp_name'];
        $this->size  = $data['size'];
        $this->errorCode = $data['error'];
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function type()
    {
        return $this->type;
    }
    
    public function path()
    {
        return $this->path;
    }
    
    public function size()
    {
        return $this->size;
    }
    
    public function errorCode()
    {
        return $this->errorCode;
    }
    
    public function error()
    {
        return !($this->errorCode == UPLOAD_ERR_OK);
    }
    
    public function move($newName)
    {
        return move_uploaded_file($this->path, $newName);
    }
}
