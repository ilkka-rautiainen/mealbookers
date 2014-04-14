<?php

interface iImport
{
    public function init();
    public function reset();
    public function run($save_opening_hours);
}