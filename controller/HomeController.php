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

    public function sugerir(): void
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        $data["categorias"]=$this->model->getCategorias();
        if ($usuarioActual == null) {
            $this->redireccionar("login");
        } else {
            $this->presenter->show("sugerirPregunta",$data);
        }
    }

    public function enviarSugerencia():void{
        $usuarioActual = $_SESSION["usuario"] ?? null;
        if($_SERVER["REQUEST_METHOD"] === 'POST' && $usuarioActual){
            $pregunta=$_POST["pregunta"];
            $categoria=$_POST["categoria"];
            $respuestas=[
                'incorrecta1'=>$_POST['respuesta1'],
                'incorrecta2'=>$_POST['respuesta2'],
                'incorrecta3'=>$_POST['respuesta3'],
                'correcta'=>$_POST['respuestaCorrecta'],
                ];
            $result=$this->model->guardarSugerencia($pregunta, $categoria,$respuestas);

            if($result){
                echo "se mando la sugerencia padre";
                $this->presenter->show("home",[]);
            }else{
                echo "no se mando nada";
                $this->presenter->show("home",[]);
            }

        }else{
            $this->redireccionar("login");
        }


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