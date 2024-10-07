<?php

class LaBandaController
{
    private $presenter;

    public function __construct($presenter)
    {
        $this->presenter = $presenter;
    }

    public function list()
    {
        $this->presenter->show("labanda");
    }
}