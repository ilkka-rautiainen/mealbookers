<?php

class HttpException extends Exception
{
    private $level;

    public function __construct($httpCode, $message, $level = null, $skip_general_code_error = false)
    {
        parent::__construct($message, $httpCode, null);

        $this->level = $level;
        $this->skip_general_code_error = $skip_general_code_error;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function getSkipGeneralCodeError()
    {
        return $this->skip_general_code_error;
    }
}