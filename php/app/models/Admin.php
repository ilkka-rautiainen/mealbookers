<?php

class Admin extends User
{
    public $id = 0;
    
    public function getName(User $viewer)
    {
        return Lang::inst()->get('administrator');
    }
}