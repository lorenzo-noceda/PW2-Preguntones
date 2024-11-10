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

    public function preguntas()
    {
        $data["preguntas"] = $this->juegoModel->getPreguntas();
        $this->presenter->show("adminPreguntas", $data);
    }

    public function reportadas()
    {
        $data["reportadas"] = $this->juegoModel->getReportes();
        $this->presenter->show("adminReportes", $data);
    }

    public function reporte()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET['id'];
            $result = $this->juegoModel->getReportesDePreguntaConId($idPregunta);
            $data["reporte"] = $result;
            $data["id_pregunta"] = $idPregunta;
            $data["pregunta_texto"] = htmlspecialchars($result[0]["pregunta"]);
            if (!empty($result)) {
                $this->presenter->show("detalleReporte", $data);
            } else {
                echo "error";
            }
        } else echo "error devuelta";
    }

    public function aprobar()
    {

    }

    public function desactivar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET['id'];
            $result = $this->juegoModel->desactivarPregunta($idPregunta);
            if ($result) {
                $data["mensaje"] = "Pregunta desactivada correctamente.";
                $this->presenter->show("mensajeProcesoCorrecto", $data);
            } else echo "error";
        }

    }

    public function editar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $idPregunta = $_GET['id'];
            $categorias = $this->juegoModel->getCategoriasMenosLaDePregunta($idPregunta);
            $respuestas = $this->juegoModel->getRespuestasDePregunta($idPregunta);
            $pregunta = $this->juegoModel->getPreguntaPorId($idPregunta);

            $idAumentado = 1;
            foreach ($respuestas as &$respuesta) {
                $respuesta["idAumentado"] = $idAumentado++;
            }
            unset($respuesta);

            $data["respuestas"] = $respuestas;
            $data["pregunta"] = $pregunta;
            $data["categorias"] = $categorias;


            $this->presenter->show("adminEditarPregunta", $data);
        }

    }

    public function actualizar()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = $this->getFormularioDataActualizarPregunta();
            $this->verVariable($data);

            $result = $this->juegoModel->actualizarPregunta($data);

            if (!empty($result)) {
                echo "todo ok campeon";
            } else {
                echo "todo mal amigo";
            }
        }
    }

    private function getFormularioDataActualizarPregunta (): array
    {
        return [
            "preguntaTexto" => $_POST["pregunta"],
            "idPregunta" => $_POST["preguntaId"],
            "respuestas" => $_POST["respuestas"],
            "nroRespuestaCorrecta" => (int)$_POST["correcta"] - 1,
            "idCategoria" => $_POST["categoria"],
        ];
    }

    public function resetReportes()
    {
        $this->juegoModel->resetReportes();
        $this->redireccionar("admin");
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

    private function verVariable($data): void
    {
        echo '<pre>' . print_r($data, true) . '</pre>';
    }

}
