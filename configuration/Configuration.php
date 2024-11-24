<?php
// Helpers
include_once(__DIR__ . "/../helper/IncludeFilePresenter.php");
include_once(__DIR__ . "/../helper/Router.php");
include_once(__DIR__ . "/../helper/MustachePresenter.php");
include_once(__DIR__ . "/../helper/Database.php");
include_once(__DIR__ . "/../helper/QRCodeGenerator.php");
include_once(__DIR__ . "/../helper/MailPresenter.php");
include_once(__DIR__ . "/../helper/Graficador.php");

// Controllers
include_once(__DIR__ . "/../controller/HomeController.php");
include_once(__DIR__ . "/../controller/RegistroController.php");
include_once(__DIR__ . "/../controller/LoginController.php");
include_once(__DIR__ . "/../controller/PerfilController.php");
include_once(__DIR__ . "/../controller/JuegoController.php");
include_once(__DIR__ . "/../controller/AdminController.php");

// Models
include_once(__DIR__ . "/../model/UsuarioModel.php");
include_once(__DIR__ . "/../model/JuegoModel.php");
include_once(__DIR__ . "/../model/GraficosModel.php");

// Utiles de los heleprs
include_once(__DIR__ . '/../vendor/mustache/src/Mustache/Autoloader.php');
include_once(__DIR__ . '/../vendor/barcode-master/barcode.php');
include_once(__DIR__ . '/../vendor/PHPMailer/src/Exception.php');
include_once(__DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php');
include_once(__DIR__ . '/../vendor/PHPMailer/src/SMTP.php');
include_once(__DIR__ . '/../vendor/jpgraph-4.4.2/src/jpgraph.php');
include_once(__DIR__ . '/../vendor/jpgraph-4.4.2/src/jpgraph_line.php');
include_once(__DIR__ . '/../vendor/jpgraph-4.4.2/src/jpgraph_bar.php');
include_once(__DIR__ . '/../vendor/jpgraph-4.4.2/src/jpgraph_pie.php');


class Configuration
{
    public function __construct()
    {
    }

    // Controllers

    public function getHomeController()
    {
        return new HomeController(
            $this->getJuegoModel(),
            $this->getUsuarioModel(),
            $this->getPresenter(),
        );
    }

    public function getRegistroController()
    {
        return new RegistroController($this->getUsuarioModel(), $this->getPresenter());
    }

    public function getLoginController()
    {
        return new LoginController($this->getUsuarioModel(), $this->getPresenter());
    }

    public function getPerfilController()
    {
        return new PerfilController(
            $this->getUsuarioModel(),
            $this->getPresenter());
    }

    public function getJuegoController()
    {
        return new JuegoController(
            $this->getJuegoModel(),
            $this->getPresenter());
    }

    public function getAdminController(): AdminController
    {
        return new AdminController(
            $this->getJuegoModel(),
            $this->getUsuarioModel(),
            $this->getPresenter(),
            $this->getGraficosModel()
        );

    }


    // Modelos

    private function getGraficosModel() {
        return new GraficosModel(
            $this->getGraficador(),
        );
    }
    private function getUsuarioModel()
    {
        return new UsuarioModel(
            $this->getDatabase(),
            $this->getMailPresenter(),
            $this->getQrCodeGenerator()
        );
    }

    private function getJuegoModel()
    {
        return new JuegoModel($this->getDatabase());
    }

    // Helpers
    private function getQrCodeGenerator(): QRCodeGenerator
    {
        return new QRCodeGenerator();
    }

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

    private function getGraficador()
    {
        return new Graficador();
    }

    private function getMailPresenter()
    {
        $config = parse_ini_file("config.ini");
        return new MailPresenter(
            $config["smtp_host"],
            $config["smtp_email"],
            $config["smtp_password"],
            $config["smtp_name"],
            $config["smtp_port"]
        );
    }

}