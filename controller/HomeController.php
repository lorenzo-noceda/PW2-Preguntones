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

    public function ranking () {
        $data["ranking"] = $this->model->getRanking();
        $this->presenter->show("ranking", $data);
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