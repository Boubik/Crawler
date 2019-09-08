<?php
return array(
//DB
    "servername" => "127.0.0.1",
    "username" => "root",
    "password" => "",
    "dbname" => "Crawler",

//Log
    'log' => false,                                                              // if true it will create logs in folder logs
    'delete_log' => 31,                                                         // after x (default 31) day will delete logs (last log will be x days old, null will dostn delete anything)

//stop
    'stop' => 0,                                                              // stop after x loop (0 infinit)
);