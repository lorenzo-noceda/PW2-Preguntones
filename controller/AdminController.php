<?php

class AdminController
{

    private $juegoModel;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->juegoModel = $model;
        $this->presenter = $presenter;
    }

    public function list(): void
    {
        $this->presenter->show("admin", []);
    }

    public function preguntas() {
        $this->juegoModel->
    }



    /**
     * @param $ruta
     * @return void
     */
    #[NoReturn] private function redireccionar($ruta): void
    {
        header("Location: /PW2-preguntones/$ruta");
        exit();
    }

}
