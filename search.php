<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html" ; charset="UTF-8">
    <link href="styles/search.css" rel="stylesheet" type="text/css">
    <link rel="icon" href="images/logo.ico">
    <title>Search Engine</title>
</head>

<body>
<?php
include "functions.php";
ini_set('max_execution_time', 0);
$configs = include('config.php');
date_default_timezone_set('Europe/Prague');
$conn = connect_to_db($configs["servername"], $configs["dbname"], $configs["username"], $configs["password"]);

echo "<div>";
    echo '<a href="/"><h2>Boubik Search Engine</h2></a>';
        echo '<form action="search.php" method="get">';
        echo '<input type="text" name="q" value="'.$_GET["q"].'">';
        echo '<input type="submit" value="Search">';
    echo "</form>";
echo "</div>";

echo '<div id="search">';
tags($_GET["q"]);
$search = search($conn, $_GET["q"]);
foreach($search as $item){
    echo '<a class="item" href="redirect.php?q='.$item["url"].'">';

    echo '<img class="site_logo" src="' .$item["fav"]. '" alt="logo">';
    echo '<div class="title">'.$item["title"]."</div>";

    echo "</a>";
}
echo "</div>";
?>
</body>
</html>