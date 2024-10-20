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

    public function perfil() {
        $id = $_GET["id"] ?? null;
        $data["usuario"] = $this->model->getUsuarioPorId($id);
        $this->presenter->show("perfil", $data);
    }

    public function login() {
        echo "Login";
    }

    public function registro() {
        echo "Login";
    }
}