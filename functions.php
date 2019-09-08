<?php

/**
 * connect to db
 * @param   String  $servername     name of the book
 * @param   String  $dbname         dbname
 * @param   String  $username       username
 * @param   String  $password       password
 * @return  PDO   $conn
 */
function connect_to_db(string $servername, string $dbname, string $username, string $password)
{
    //connect
    try {
        $conn = new PDO("mysql:host=" . $servername . ";dbname=" . $dbname . ";charset=utf8", $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sql = $conn->prepare("SET character SET UTF8");
        $sql->execute();

        return $conn;
    } catch (PDOException $e) {
        generate_db();
    }
}

/**
 * check if db is up to date or if exist
 */
function generate_db()
{
    ini_set('max_execution_time', 0);
    $configs = include('config.php');
    $servername = $configs["servername"];
    $dbname = $configs["dbname"];
    $username = $configs["username"];
    $password = $configs["password"];

    $dsn = "mysql:host=$servername;dbname=$dbname;";
    //connect
    try {
        $conn = new PDO($dsn, $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $execute = false;
    } catch (PDOException $e) {
        $execute = true;
        $dsn = "mysql:host=$servername;";
        //connect
        try {
            $conn = new PDO($dsn, $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $execute = false;
            echo "Something goes worn give us time to fix it";
        }

        $sql = $conn->prepare("SET character SET UTF8");
        $sql->execute();
    }


    $sql = $conn->prepare("SET character SET UTF8");
    $sql->execute();
    if ($execute) {
        save_to_log("Generating DB");
        $fileList = glob('db/*.sql');
        $sql = load_file($fileList[0]);
        $sql = explode(";", $sql);
        foreach ($sql as $item) {
            try {
                $sql = $conn->prepare($item . ";");
                $sql->execute();
            } catch (PDOException $e) { }
        }
    }
}

/**
 * will load file in root folder of program
 * @param   String  $filename   filen name with end (.txt, etc)
 * @param   String  $mode       mode (w, a, etc)
 * @return  String  text in file
 */
function load_file($filename, $mode = "r")
{

    $handle = fopen($filename, $mode);
    $text = "";
    while (($line = fgets($handle)) !== false) {
        $text = $text . $line;
    }
    return $text;
}

/**
 * save text to .log and delete old
 * @param   String  $log_text   text that will be putet to log
 */
function save_to_log(String $log_text)
{
    date_default_timezone_set('Europe/Prague');
    $configs = include('config.php');
    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }
    $date = date("Y-m-d");
    $fa = fopen("logs/" . $date . ".log", "a");
    fwrite($fa, (date("H:i") . ": " . $log_text) . "\n");
    fclose($fa);

    if ($configs["delete_log"] != null) {
        $fileList = glob('logs/*.log');
        foreach ($fileList as $filename) {
            $date = substr($filename, 5, 10);
            if (strtotime($date) < strtotime('-' . ($configs["delete_log"] + 1) . ' days')) {
                unlink($filename);
            }
        }
    }
}

/**
 * search in db
 * @param   PDO  $coon     db
 * @param   String  $search         search
 * @return  Array
 */
function search(PDO $conn, String $search, $page = 1, $per_page = 30)
{
    $page -= 1;
    if ($page == 0) {
        $skip = 0;
    } else {
        $skip = $page * $per_page;
    }
    $sql = "SELECT * FROM `site` WHERE `url` LIKE '%" . $search . "%' OR `title` LIKE '%" . $search . "%' OR `fav` LIKE '%" . $search . "%' OR `h` LIKE '%" . $search . "%' OR `s/b` LIKE '%" . $search . "%' OR `p` LIKE '%" . $search . "%' OR `div` LIKE '%" . $search . "%' ORDER BY `rank` DESC LIMIT " . $skip . ", " . $per_page;
    //echo $sql;
    $sql = $conn->prepare($sql);
    $sql->execute();
    $array = array();
    while ($row = $sql->fetch()) {
        $array[] = $row;
    }
    return $array;
}

/**
 * search in db
 * @param   PDO  $coon     db
 * @param   String  $search         search
 * @return  Array
 */
function count_search(PDO $conn, String $search)
{
    $sql = "SELECT COUNT(`url`) as 'count' FROM `site` WHERE `url` LIKE '%" . $search . "%' OR `title` LIKE '%" . $search . "%' OR `fav` LIKE '%" . $search . "%' OR `h` LIKE '%" . $search . "%' OR `s/b` LIKE '%" . $search . "%' OR `p` LIKE '%" . $search . "%' OR `div` LIKE '%" . $search . "%' ORDER BY `rank` DESC";
    //echo $sql;
    $sql = $conn->prepare($sql);
    $sql->execute();
    $array = array();
    $row = $sql->fetch();
    return $row["count"];
}

/**
 * search in db
 * @param   PDO  $coon     db
 * @param   String  $url         url
 */
function add_rank(PDO $conn, String $url)
{
    $sql = "UPDATE `site` SET `rank`= `rank`+1 WHERE `url` = '" . $url . "' and `rank`+1 < 1000000";
    $sql = $conn->prepare($sql);
    $sql->execute();
}

/**
 * search in db
 * @param   PDO  $coon     db
 * @return   Array         urls
 */
function alredy_crawled(PDO $conn)
{
    $sql = "SELECT `url` FROM `site`";
    $sql = $conn->prepare($sql);
    $sql->execute();
    $alredy_crawled = array();

    while ($row = $sql->fetch()) {
        $alredy_crawled[] = $row["url"];
    }

    return $alredy_crawled;
}

/**
 * search in db
 * @param   PDO     $coon     db
 * @param   Array   $already_crawled_update
 */
function update(PDO $conn, array $already_crawled_update)
{
    $sql = "INSERT INTO `site`(`url`, `title`, `fav`, `h`, `s/b`, `p`, `div`) VALUES ";
    $execute = false;

    //print_r($already_crawled_update) . "<br>";
    $i = 0;
    foreach ($already_crawled_update as $key => $item) {
        //print_r($key) . "<br>";
        if (isset($item["title"])) {
            $title = $item["title"];
            $title = str_replace("\"", "'", $title);
            $title = clean($title, 0, 50);
        }else{
            $title = "NULL";
        }

        if (isset($item["fav"])) {
            $fav = $item["fav"];
            $fav = str_replace("\"", "'", $fav);
            $fav = clean($fav, 0, 100, false);
        }else{
            $fav = "NULL";
        }

        if (isset($item["h"])) {
            $h = $item["h"];
            $h = str_replace("\"", "'", $h);
            $h = clean($h, 0, 100);
        }else{
            $h = "NULL";
        }

        if (isset($item["sb"])) {
            $sb = $item["sb"];
            $sb = str_replace("\"", "'", $sb);
            $sb = clean($sb, 0, 100);
        }else{
            $sb = "NULL";
        }

        if (isset($item["p"])) {
            $p = $item["p"];
            $p = str_replace("\"", "'", $p);
            $p = clean($p, 0, 400);
        }else{
            $p = "NULL";
        }

        if (isset($item["div"])) {
            $div = $item["div"];
            $div = str_replace("\"", "'", $div);
            $div = clean($div, 0, 400);
        }else{
            $div = "NULL";
        }

        if("http" == substr($key, 0, 4)){
            $key = substr($key, 0, 200);
            if ($i != 0) {
                $sql .= ", (\"" . $key . "\",\"" . $title . "\",\"" . $fav . "\",\"" . $h . "\",\"" . $sb . "\",\"" . $p . "\",\"" . $div . "\")";
            } else {
                $sql .= "(\"" . $key . "\",\"" . $title . "\",\"" . $fav . "\",\"" . $h . "\",\"" . $sb . "\",\"" . $p . "\",\"" . $div . "\")";
                $i++;
                $execute = true;
            }
        }
    }
    
    if ($execute) {
        echo $sql;
        $sql = $conn->prepare($sql);
        $sql->execute();
    }
}

function clean($string, $start, $len, $clean = true) {
    if($clean){
        $string = preg_replace('/[^A-Za-z0-9\- ]/', '', $string); // Removes special chars.
    }

    $string = mb_substr($string, $start, $len); // set max len
 
    if($clean){
        return preg_replace('/-+/', '-', $string); // Replaces multiple hyphens with single one.
    }else{
        return $string;
    }
 }

/** redirect by tags
 * @param $q search queri
 */
function tags($q)
{

    ini_set('max_execution_time', 0);

    if (substr($q, 0, 4) == "!yt ") {
        $url = "https://youtube.com/results?search_query=";
        $url .= substr($q, 4);

        echo $url;
        header("Location: $url");
    }

    if (substr($q, 0, 6) == "!wiki ") {
        $url = "https://wikipedia.org/wiki/";
        $url .= substr($q, 6);

        echo $url;
        header("Location: $url");
    }

    if (substr($q, 0, 8) == "!google ") {
        $url = "https://www.google.com/search?q=";
        $url .= substr($q, 8);

        echo $url;
        header("Location: $url");
    }

    if (substr($q, 0, 6) == "!duck ") {
        $url = "https://duckduckgo.com/?q=";
        $url .= substr($q, 6);

        echo $url;
        header("Location: $url");
    }

    if (substr($q, 0, 7) == "!stack ") {
        $url = "https://stackoverflow.com/search?q=";
        $url .= substr($q, 7);

        echo $url;
        header("Location: $url");
    }
}

function get_html($url)
{
    $config['useragent'] = 'BoubikBot/0.2';
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, $config['useragent']);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($httpcode == 200) {
        return $data;
    } else {
        echo "code: ".$httpcode."<br><br>";
        return false;
    }
}

function get_links(DOMDocument $DOM)
{
    $a = array();
    $links = $DOM->getElementsByTagName('a');

    //Iterate over the extracted links and display their URLs
    foreach ($links as $link) {
        try {
            $a[] = $link->getAttribute('href');
        } catch (PDOException $e) { }
    }
    return $a;
}

function get_tags(DOMDocument $DOM, String $url)
{
    $h = "";
    $p = "";
    $sb = "";
    $div = "";
    $title = "";
    $fav = "";
    $array = array();
    $hs = $DOM->getElementsByTagName('h1');
    foreach ($hs as $each) {
        try {
            $h .= $each->nodeValue;
        } catch (PDOException $e) { }
    }
    $h = preg_replace('/\s+/', ' ', $h);

    $hs = $DOM->getElementsByTagName('h2');
    foreach ($hs as $each) {
        try {
            $h .= $each->nodeValue;
        } catch (PDOException $e) { }
    }
    $h = preg_replace('/\s+/', ' ', $h);

    $hs = $DOM->getElementsByTagName('h3');
    foreach ($hs as $each) {
        try {
            $h .= $each->nodeValue;
        } catch (PDOException $e) { }
    }
    $h = preg_replace('/\s+/', ' ', $h);
    $array["h"] = $h;

    $ps = $DOM->getElementsByTagName('p');
    foreach ($ps as $each) {
        try {
            $p .= $each->nodeValue;
        } catch (PDOException $e) { }
    }
    $p = preg_replace('/\s+/', ' ', $p);
    $array["p"] = $p;

    $divs = $DOM->getElementsByTagName('div');
    foreach ($divs as $each) {
        try {
            $div .= $each->nodeValue;
        } catch (PDOException $e) { }
    }
    $div = preg_replace('/\s+/', ' ', $div);
    $array["div"] = $div;

    $fav = $DOM->getElementsByTagName('link');
    foreach ($fav as $each) {
        try {
            if($each->getAttribute('rel') == "shortcut icon"){
                $fav = $each->getAttribute('href');
            }
        } catch (PDOException $e) { }
    }
    $fav = preg_replace('/\s+/', ' ', $fav);
    $array["fav"] = fix_url($fav, $url);

    /*$s = $DOM->getElementsByTagName('s');
    foreach ($s as $each) {
        try {
            $s .= $each->nodeValue;
        } catch (PDOException $e) { }
    }
    $s = preg_replace('/\s+/', ' ', $s);
    $array["sb"] = $s;

    $b = $DOM->getElementsByTagName('b');
    foreach ($b as $each) {
        try {
            $b .= $each->nodeValue;
        } catch (PDOException $e) { }
    }
    $b = preg_replace('/\s+/', ' ', $b);
    $array["sb"] = $div;*/

    $titlek = $DOM->getElementsByTagName("title");
    try {
        $title = $titlek->item(0)->nodeValue;
    } catch (PDOException $e) { }
    $array["title"] = $title;

    $tags = get_meta_tags($url);

    if (isset($tags['keywords'])) {
        $array[] = $tags['keywords'];
    }
    if (isset($tags['description'])) {
        $array[] = $tags['description'];
    }

    return $array;
}

function fix_url(string $l, $url)
{
    //echo "orig: ".$l."<br>";
    if (substr($l, 0, 1) == "/" and substr($l, 0, 2) != "//") {
        return parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . $l;
    } else if (substr($l, 0, 2) == "//") {
        return parse_url($url)["scheme"] . ":" . $l;
    } else if (substr($l, 0, 2) == "./") {
        if (dirname(parse_url($url)["path"]) != "/") {
            return parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . dirname(parse_url($url)["path"]) . substr($l, 1);
        } else {
            return parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . substr($l, 1);
        }
    } else if (substr($l, 0, 1) == "#") {
        return parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . parse_url($url)["path"] . $l;
    } else if (substr($l, 0, 3) == "../") {
        return parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . "/" . $l;
    } else if (substr($l, 0, 5) != "https" and substr($l, 0, 4) != "http") {
        return parse_url($url)["scheme"] . "://" . parse_url($url)["host"] . "/" . $l;
    }
    //echo " new: ".$l."<br><br>";

    return "";
}
