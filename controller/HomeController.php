<?php

class HomeController
{

    private $model;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->model = $model;
        $this->presenter = $presenter;
    }

    public function list() {
        $this->presenter->show("home", []);
    }
}