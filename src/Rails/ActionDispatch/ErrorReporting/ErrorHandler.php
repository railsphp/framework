<?php
namespace Rails\ActionDispatch\ErrorReporting;

use Rails\ActionController\ExceptionHandler as Base;
use Rails\ActionDispatch\Http\StatusCodes;

class ErrorHandler extends Base
{
    public function handle()
    {
        $status   = $this->response()->status();
        $filePath = $this->getService('rails.config')['paths']['public_path']->extend(
            $status . '.html'
        );
        
        if (is_file($filePath)) {
            $body = file_get_contents($filePath);
        } else {
            $body = sprintf(
                Reporter::ERROR_NOTICE,
                $status,
                StatusCodes::getName($status)
            );
        }
        $this->response()->setBody($body);
    }
}
