<?php
include "functions.php";
ini_set('max_execution_time', 0);
$configs = include('config.php');
date_default_timezone_set('Europe/Prague');
$conn = connect_to_db($configs["servername"], $configs["dbname"], $configs["username"], $configs["password"]);

if(isset($_GET["q"])){
    add_rank($conn, $_GET["q"]);
    header("Location: ".$_GET["q"]);
}