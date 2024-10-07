<?php
class PresentancionesController
{
    private $model;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->model = $model;
        $this->presenter = $presenter;
    }

    public function list()
    {
        $data["presentaciones"] = $this->model->getPresentaciones();
        $this->presenter->show('presentaciones',$data);
    }

}