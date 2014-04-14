<?php

class LaureaImport extends AmicaImportWrapper implements iImport
{
    public function __construct()
    {
        $this->importers = array(
            new LaureaJSONImport(),
            new LaureaNormalImport()
        );
    }
}