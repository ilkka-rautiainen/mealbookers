<?php

class ApiException extends Exception
{
    private $data;

    public function __construct($message, $data = null)
    {
        parent::__construct($message, 0, null);

        if (!is_array($data))
            $data = array();
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}