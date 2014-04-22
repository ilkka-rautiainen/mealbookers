<?php

class LoggedException extends Exception
{
    private $info;

    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        Application::inst()->logError(
            get_class($this),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            $this->getTrace(),
            $this->info
        );
    }

    protected function setInfo($info)
    {
        $this->info = $info;
    }
}