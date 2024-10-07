<?php
include_once("helper/MysqlDatabase.php");
include_once("helper/MysqlObjectDatabase.php");
include_once("helper/IncludeFilePresenter.php");
include_once("helper/Router.php");
include_once("helper/MustachePresenter.php");

include_once("model/PresentacionesModel.php");
include_once("model/CancionesModel.php");

include_once("controller/PresentancionesController.php");
include_once("controller/CancionesController.php");
include_once("controller/LaBandaController.php");
include_once("controller/PruebaController.php");

include_once('vendor/mustache/src/Mustache/Autoloader.php');

class Configuration
{
    public function __construct()
    {
    }

    public function getPresentacionesController()
    {
        return new PresentancionesController($this->getPresentacionesModel(), $this->getPresenter());
    }

    public function getCancionesController()
    {
        return new CancionesController($this->getCancionesModel(), $this->getPresenter());
    }

    public function getLaBandaController()
    {
        return new LaBandaController($this->getPresenter());
    }

    public function getPruebaController()
    {
        return new PruebaController($this->getPresenter());
    }

    private function getPresentacionesModel()
    {
        return new PresentacionesModel($this->getDatabase());
    }

    private function getCancionesModel()
    {
        return new CancionesModel($this->getDatabase());
    }

    private function getPresenter()
    {
        return new MustachePresenter("./view");
    }

    private function getDatabase()
    {
        $config = parse_ini_file('configuration/config.ini');
        return new MysqlObjectDatabase(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config["database"]
        );
    }

    public function getRouter()
    {
        return new Router($this, "getLaBandaController", "list");
    }
}