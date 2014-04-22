<?php

class ApiException extends LoggedException
{
    private $data;

    public function __construct($message, $data = null)
    {
        if (!is_null($data))
            $this->setInfo(array(
                'data' => $data,
            ));
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