<?php

class CancionesController
{
    private $cancionesModel;

    private $presenter;

    public function __construct($cancionesModel,$presenter)
    {
        $this->cancionesModel = $cancionesModel;
        $this->presenter = $presenter;
    }

    public function list()
    {
        $data['canciones'] = $this->cancionesModel->getCanciones();
        $this->presenter->show('canciones', $data);
    }
}