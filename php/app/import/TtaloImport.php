<?php

class TtaloImport extends SodexoImport implements iImport
{
    protected $restaurant_id = 9;
    protected $sodexo_id = 142;
    

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