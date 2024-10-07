<?php
include_once("configuration/Configuration.php");
$configuration = new Configuration();
$router = $configuration->getRouter();

$router->route($_GET['page'], $_GET['action']);