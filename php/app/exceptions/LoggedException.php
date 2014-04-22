<?php

class LoggedException extends Exception
{
    private $info;

    public function __construct($message = "", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        DB::inst()->query("INSERT INTO exceptions (
                datetime,
                type,
                message,
                file,
                line,
                request,
                info
            ) VALUES (
                '" . date("Y-m-d H:i:s") . "',
                '" . get_class($this) . "',
                '" . $this->getMessage() . "',
                '" . DB::inst()->quote($this->getFile()) . "',
                '" . $this->getLine() . "',
                '" . json_encode($_SERVER) . "',
                " . ((is_null($this->info)) ? "NULL" : "'" . json_encode($this->info) . "'") . "
            )", false);
    }

    protected function setInfo($info)
    {
        $this->info = $info;
    }
}