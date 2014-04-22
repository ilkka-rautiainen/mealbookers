<?php

class HttpException extends LoggedException
{
    private $level;

    public function __construct($http_code, $message, $level = null, $skip_general_code_error = false)
    {
        $this->setInfo(array(
            'http_code' => $http_code,
            'level' => $level,
            'skip_general_code_error' => $skip_general_code_error,
        ));
        parent::__construct($message, $http_code, null);

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