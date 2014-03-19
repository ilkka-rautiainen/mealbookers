<?php

class Admin extends User
{
    public function getName(User $viewer)
    {
        return Lang::inst()->get('administrator');
    }
}