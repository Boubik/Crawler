<?php
include "functions.php";
ini_set('max_execution_time', 0);
$configs = include('config.php');
date_default_timezone_set('Europe/Prague');
$conn = connect_to_db($configs["servername"], $configs["dbname"], $configs["username"], $configs["password"]);

$already_crawled = alredy_crawled($conn);
$already_crawled_update = array();
$to_be_crawled = array();
$to_be_crawled[] ="https://cs.wikipedia.org/wiki/Hlavn%C3%AD_strana";

$i = 0;
while(isset($to_be_crawled[$i])){
    //echo $to_be_crawled[$i]."<br><br>";
    //print_r($to_be_crawled);
    //echo "<br><br>";
    $html = get_html($to_be_crawled[$i]);
    if($html != false){
        $DOM = new DOMDocument('1.0', 'UTF-8');
        $DOM->loadHTML($html);
        $tags = get_tags($DOM, $to_be_crawled[$i]);

        if((!in_array($to_be_crawled[$i], $already_crawled))){
            $already_crawled_update[] = $to_be_crawled[$i];
            $already_crawled_update[$to_be_crawled[$i]] = $tags;
            $already_crawled[] = $to_be_crawled[$i];
        }

        //print_r($already_crawled_update);
        $links = get_links($DOM);
        //print_r($links);
        foreach($links as $item){
            $fix = fix_url($item, $to_be_crawled[$i]);
            if($fix != "" and (!in_array($fix, $to_be_crawled))){
                //echo $fix."<br><br>";
                $to_be_crawled[] = $fix;
            }
        }
    }

    if(count($already_crawled_update) == 10){
        update($conn, $already_crawled_update);
        $already_crawled_update = array();
    }
    if($configs["stop"] != 0 and $i == $configs["stop"]){
        break;
    }
    $i++;
}
update($conn, $already_crawled_update);
$already_crawled_update = array();