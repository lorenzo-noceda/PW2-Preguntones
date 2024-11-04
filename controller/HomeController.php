<?php

class HomeController
{

    private $model;
    private $presenter;
    private $qrCodeGenerator;

    public function __construct($model, $presenter, $qrCodeGenerator)
    {
        $this->model = $model;
        $this->presenter = $presenter;
        $this->qrCodeGenerator = $qrCodeGenerator;
    }

    public function list(): void
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        if ($usuarioActual == null) {
            $this->redireccionar("login");
        } else {
            if (!$usuarioActual["verificado"]) {
                $data = [
                    "mensaje" => "Verifica tu correo por favor.",
                    "correo" => $usuarioActual["email"],
                    "id_usuario" => $usuarioActual["id"]
                ];
                $this->presenter->show("validacionCorreo", $data);
            } else {
                $data = [
                    "nombre" => $usuarioActual["nombre"],
                    "id_usuario" => $usuarioActual["id"],
                ];
                $this->presenter->show("home", $data);
            }
        }
    }

    public function usuario () {
        $idUsuario = $_GET["id"];
        $usuarioBuscado = $this->model->getUsuarioPorId($idUsuario);

        $urlParaQR = "/PW2-Preguntones/usuario?id=" . $idUsuario;
        $_SESSION["qrParaGenerar"] = $urlParaQR;

        $usuarioBuscado["qr"] = $this->qrCodeGenerator::getQrCodeParaImg($urlParaQR);

        $data["usuario"] = $usuarioBuscado;
        
        $this->presenter->show("otroUsuarioPerfil", $data);
    }

    public function generarQr() {
//        $qrParaGenerar = $_SESSION["qrParaGenerar"];
//        $this->qrCodeGenerator::getQrCodeParaImg($qrParaGenerar);
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