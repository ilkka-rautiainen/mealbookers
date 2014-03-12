<?php

class Puu2Import extends AmicaImport
{
    protected $restaurant_id = 4;
    protected $url = "http://www.amica.fi/puu2";

    public function __construct()
    {
        // Only finnish
        $langs = array('fi' => $this->langs['fi']);
        $this->langs = $langs;
    }
}