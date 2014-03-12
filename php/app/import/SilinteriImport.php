<?php

class SilinteriImport extends AmicaImport
{
    protected $restaurant_id = 5;
    protected $url = "http://www.amica.fi/silinteri";

    public function __construct()
    {
        // Only finnish
        $langs = array('fi' => $this->langs['fi']);
        $this->langs = $langs;
    }
}