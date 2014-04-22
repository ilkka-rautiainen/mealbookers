<?php

class ImportException extends LoggedException
{
    public function __construct($message, $restaurant, $lang)
    {
        $this->setInfo(array(
            'restaurant' => $restaurant,
            'lang' => $lang,
        ));
        parent::__construct($message, 0, null);
    }
}