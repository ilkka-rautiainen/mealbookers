<?php

class SqlException extends LoggedException
{
    public function __construct($message, $query_string)
    {
        $this->setInfo(array(
            'query_string' => $query_string,
        ));
        parent::__construct($message, 0, null);
    }
}