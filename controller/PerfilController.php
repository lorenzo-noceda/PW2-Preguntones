<?php

class PerfilController
{

    private $usuarioModel;
    private $paisModel;
    private $presenter;

    public function __construct($usuarioModel, $paisModel, $presenter)
    {
        $this->usuarioModel = $usuarioModel;
        $this->paisModel = $paisModel;
        $this->presenter = $presenter;
    }

    public function list(): void
    {
        $this->validarUsuario();
        $id = $_GET["id"] ?? null;
        $data["usuario"] = $this->usuarioModel->getUsuarioPorId($id);
        $this->presenter->show("perfil", $data);
    }



    public function editar()
    {
        $this->validarUsuario();
        $id = $_GET["id"] ?? null;
        $data["usuario"] = $this->usuarioModel->getUsuarioPorId($id);
        $data["paises"] = $this->paisModel->getPaisesMenosElDelUsuario($data["usuario"]["pais"]);
        $data["sexo"] = $this->usuarioModel->getSexosMenosElDelUsuario($data["usuario"]["sexoNombre"]);
        $this->presenter->show("editarPerfil", $data);
    }

    public function actualizar() {
        echo "<h1>En desarrollo</h1>";
    }

    private function validarUsuario(): void
    {
        $usuarioActual = $_SESSION["usuario"] ?? null;
        if ($usuarioActual == null) {
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