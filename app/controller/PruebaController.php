<?php

class PruebaController
{
    private $presenter;

    public function __construct($presenter)
    {
        $this->presenter = $presenter;
    }

    public function flauta()
    {
        echo "hola flauta";
    }
}