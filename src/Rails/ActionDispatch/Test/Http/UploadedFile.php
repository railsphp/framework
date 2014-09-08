<?php
namespace Rails\ActionDispatch\Test\Http;

use finfo;

/**
 * Uploaded file for testing.
 * Extends the original ActionDispatch's UploadedFile class, adding
 * features that could be useful for testing:
 *  - The move() method won't move_uploaded_file() but copy() instead.
 *  - Adds a static method to automatically generate an UploadedFile
 *    object out of a file.
 *  - Has setters to arbitrary manipulate the object as needed.
 */
class UploadedFile extends \Rails\ActionDispatch\Http\UploadedFile
{
    /**
     * Generate uploaded file.
     * Pass the path to a file to automatically generate an
     * UploadedFile object for that file.
     * Note that FileInfo extension must be available.
     *
     * @param string $filePath
     * @return UploadedFile
     */
    public static function generate($filePath)
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return new self([
            'name'     => pathinfo($filePath, PATHINFO_BASENAME),
            'type'     => $finfo->file($filePath),
            'tmp_name' => $filePath,
            'error'    => UPLOAD_ERR_OK,
            'size'     => filesize($filePath)
        ]);
    }
    
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }
    
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        return $this;
    }
    
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }
    
    public function move($newName)
    {
        return copy($this->path, $newName);
    }
}
