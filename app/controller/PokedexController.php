<?php

class PokedexController
{

    private $model;
    private $presenter;

    public function __construct($model, $presenter)
    {
        $this->model = $model;
        $this->presenter = $presenter;
    }

    public function list()
    {
        if (isset($_SESSION['user'])) {
            $data['user'] = $_SESSION['user'];
        }
        $data['pokemons'] = $this->model->getPokemons();
        $this->presenter->show('pokemon', $data);
    }


    public function addForm()
    {
        $this->presenter->show('pokemonAdd', []);
    }

    public function add()
    {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $image = $_POST['image'];
        $number = $_POST['number'];


        $this->model->add($number, $name, $image, $type);

        $this->redirectHome();
    }

    public function editForm()
    {
        $id = $_GET['id'];
        $data['pokemon']  = $this->model->getPokemon($id);
        $this->presenter->show('pokemonAdd', $data);
    }

    public function search(){
        $search = $_POST['search'];
        $data = $this->model->filter($search);

        $this->presenter->show('pokemon', $data);
    }

    public function delete()
    {
        $id = $_GET['id'];
        $this->model->delete($id);
        $this->redirectHome();
    }

    public function details()
    {
        $id = $_GET['id'];
        $data['pokemon']  = $this->model->getPokemon($id);
        $this->presenter->show('pokemonDetails', $data);
    }


    public function redirectHome()
    {
        header('location: /pokedex');
        exit();
    }
}