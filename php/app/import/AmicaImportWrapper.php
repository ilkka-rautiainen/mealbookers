<?php

class AmicaImportWrapper implements iImport
{
    protected $importers;
    private $reset = false;

    public function init()
    {

    }

    public function reset()
    {
        $this->reset = true;
    }

    /**
     * Runs the import with the given importers
     */
    public function run($save_opening_hours = false)
    {
        Logger::debug(__METHOD__ . " called");

        foreach ($this->importers as $importer) {
            try {
                $importer->init();
                if ($this->reset) {
                    $importer->reset();
                }
                $importer->run($save_opening_hours);
                break;
            }
            catch (ImportException $e) {
                Logger::error(__FILE__ . ":" . __LINE__ . " Error in " . get_class($importer) . ": "
                    . $e->getMessage() . ", from:" . $e->getFile() . ":" . $e->getLine());
            }
            // Import opening hours only once
            $save_opening_hours = false;
        }
    }
}