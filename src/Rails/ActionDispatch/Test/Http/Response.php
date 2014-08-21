<?php
namespace Rails\ActionDispatch\Test\Http;

use Rails\ActionDispatch\Http\Response as BaseResponse;

class Response extends BaseResponse
{
    protected $status = 200;
    
    public function commit()
    {
        $this->setCookies();
        $this->committed = true;
    }
    
    public function cookieJar()
    {
        return $this->cookieJar;
    }
}
