<?php
session_start();
include_once("configuration/Configuration.php");
$configuration = new Configuration();
$router = $configuration->getRouter();

$action = $_GET['action'] ?? '';
$page = $_GET['page'] ?? '';
$router->route($page, $action);