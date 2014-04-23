<?php

class KoneImport extends SodexoImport implements iImport
{
    protected $restaurant_id = 8;
    protected $sodexo_id = 140;
    

    /**
     * Import and Save opening hours
     */
    protected function saveOpeningHours()
    {
        //TODO
    }

    private function decreaseWithHalfHour($time)
    {
        return date("H:i", strtotime("1.2.2010 $time") - 1800);
    }
    




}