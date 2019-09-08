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
$before = microtime(true);
include "functions.php";
ini_set('max_execution_time', 0);
$configs = include('config.php');
date_default_timezone_set('Europe/Prague');
$conn = connect_to_db($configs["servername"], $configs["dbname"], $configs["username"], $configs["password"]);
$per_page = $configs["per_page"];
if (isset($_GET["page"])) {
    $page = $_GET["page"];
}else{
    $page = 1;
}

echo "<div>";
    echo '<a href="/"><h2>Boubik Search Engine</h2></a>';
        echo '<form action="search.php" method="get">';
        echo '<input type="text" name="q" value="'.$_GET["q"].'">';
        echo '<input type="submit" value="Search">';
    echo "</form>";
echo "</div>";

echo '<div id="search">';
tags($_GET["q"]);
$search = search($conn, $_GET["q"], $page);
foreach($search as $item){
    echo '<a class="item" href="redirect.php?q='.$item["url"].'">';

    echo '<img class="site_logo" src="' .$item["fav"]. '" alt="logo">';
    echo '<div class="title">'.$item["title"]."</div>";

    echo "</a>";
}
echo "</div>";


echo '<div id="pages">';
$search = $_GET["q"];
$count_pages = count_search($conn, $search);
$maxpage = (int)($count_pages / $per_page) + 1;
$after = microtime(true);
echo $count_pages . " results found (" . substr($after-$before, 0, 5) . " s)<br>";
$i = 1;
if($maxpage > 1){
    if ($page  > 1) {
        echo "<a href=\"/search.php?q=" . $search . "&page=" . ($page - 1) . "\">< </a>";
    }
    while (1) {
        if($maxpage != 0){
            do{
                if ($i == 1) {
                    echo "pages: ";
                    echo "<a href=\"/search.php?q=" . $search . "&page=" . $i . "\">" . $i . "</a>";
                }else{
                    echo ", ";
                    echo "<a href=\"/search.php?q=" . $search . "&page=" . ($i) . "\">" . ($i) . "</a>";
                }
                $i++;
            }while($i <= $maxpage);
            break;
        }else{
            break;
        }
    }

    if ($page  < $maxpage) {
        echo "<a href=\"/search.php?q=" . $search . "&page=" . ($page + 1) . "\"> ></a>";
    }
}
echo "</div>";

?>
</body>
</html>