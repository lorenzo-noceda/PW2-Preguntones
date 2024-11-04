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

    public function usuario () {
        $idUsuario = $_GET["id"];
        $usuarioBuscado = $this->model->getUsuarioPorId($idUsuario);

        $contenidoQR = "https://example.com/perfil/" . $idUsuario;

        // Captura el flujo de salida en lugar de enviar la imagen directamente
        ob_start();
        QRcode::png($contenidoQR, null, QR_ECLEVEL_L, 10, 2);
        $imageData = ob_get_contents();
        ob_end_clean();

        // Convierte el flujo de salida en una URL de datos
        $qrCodeDataUrl = 'data:image/png;base64,' . base64_encode($imageData);


        $usuarioBuscado["qr"] =
        $data["usuario"] = $usuarioBuscado;
        $this->presenter->show("otroUsuarioPerfil", $data);
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