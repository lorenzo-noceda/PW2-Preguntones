<?php

class PerfilController
{

    private UsuarioModel $usuarioModel;
    private MustachePresenter $presenter;

    public function __construct($usuarioModel, $presenter)
    {
        $this->usuarioModel = $usuarioModel;
        $this->presenter = $presenter;
    }

    public function list(): void
    {
        $this->validarUsuario();
        $id = $_GET["id"] ?? null;
        $data["usuario"] = $this->usuarioModel->buscarUsuarioPorId($id, true);
        $this->presenter->show("perfil", $data);
    }

    public function verPerfilUsuario()
    {
        $this->validarUsuario();
        $id = $_GET["id"] ?? null;
        $data["usuario"] = $this->usuarioModel->buscarUsuarioPorId($_SESSION["usuario"]["id"], true);
        $data["usuarioBuscado"] = $this->usuarioModel->buscarUsuarioPorId($id, true);
        $this->presenter->show("otroUsuarioPerfil", $data);

    }

    public function editar()
    {
        $this->validarUsuario();
        $id = $_GET["id"] ?? null;
        $data["usuario"] = $this->usuarioModel->getUsuarioPorId($id);
        $data["sexo"] = $this->usuarioModel->getSexosMenosElDelUsuario($data["usuario"]["sexoNombre"]);
        $data["id_usuario"] = $id;
        $this->presenter->show("editarPerfil", $data);
    }

    public function actualizar() {
        $id = $_SESSION["usuario"]["id"] ?? null;

        $data = [
            'nombre' => $_POST['nombre'],
            'apellido' => $_POST['apellido'],
            'id_sexo' => $_POST['id_sexo']
        ];
        $this->usuarioModel->actualizarUsuario($id, $data);
        $data["id_usuario"] = $id;
        $this->presenter->show("perfilEditado", $data);
    }

private
function validarUsuario(): void
{
    $usuarioActual = $_SESSION["usuario"] ?? null;
    if ($usuarioActual == null) {
        $this->redireccionar("login");
    }
}

public
function musica()
{
    $this->validarUsuario();
    $id = $_GET["id"] ?? null;
    if (isset($_POST["musica"])) {
        $activarMusica = $_POST["musica"] === "true" ? 1 : 0;
        $this->usuarioModel->actualizarMusica($id, $activarMusica);
        $_SESSION["musica"] = $activarMusica;
    }
    $this->redireccionar("perfil?id=" . $id);
}

// Helpers de clase

/**
 * @param $ruta
 * @return void
 */
#[
NoReturn] private function redireccionar($ruta): void
{
    header("Location: /PW2-preguntones/$ruta");
    exit();
}

    private function verVariable($data): void
{
    echo '<pre>' . print_r($data, true) . '</pre>';
}
}