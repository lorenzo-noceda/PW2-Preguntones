<?php
// Helpers
include_once(__DIR__ . "/../helper/IncludeFilePresenter.php");
include_once(__DIR__ . "/../helper/Router.php");
include_once(__DIR__ . "/../helper/MustachePresenter.php");
include_once(__DIR__ . "/../helper/Database.php");

// Controllers
include_once(__DIR__ . "/../controller/UsuarioController.php");
include_once(__DIR__ . "/../controller/HomeController.php");
include_once(__DIR__ . "/../controller/RegistroController.php");
include_once(__DIR__ . "/../controller/LoginController.php");

// Models
include_once(__DIR__ . "/../model/UsuarioModel.php");

include_once(__DIR__ . '/../vendor/mustache/src/Mustache/Autoloader.php');

class Configuration
{
    public function __construct()
    {
    }

    // Controllers
    public function getUsuarioController(){
        return new UsuarioController($this->getUsuarioModel(), $this->getPresenter());
    }
    public function getHomeController(){
        return new HomeController($this->getUsuarioModel(), $this->getPresenter());
    }
    public function getRegistroController(){
        return new RegistroController($this->getUsuarioModel(), $this->getPresenter());
    }
    public function getLoginController(){
        return new LoginController($this->getUsuarioModel(), $this->getPresenter());
    }




    // Modelos
    private function getUsuarioModel()
    {
        return new UsuarioModel($this->getDatabase());
    }

    // Helpers
    private function getDatabase()
    {
        $config = parse_ini_file("config.ini");
        $database = new Database(
            $config["host"],
            $config["user"],
            $config["password"],
            $config["database"]
        );
        return $database;
    }

    public function getRouter()
    {
        return new Router($this, "getHomeController", "list");
    }

    private function getPresenter()
    {
        return new MustachePresenter("./view");
    }

}