<?php

    $script_path=realpath(dirname(__FILE__));
    $script_path .= '/../include/';
    set_include_path(get_include_path() . PATH_SEPARATOR . $script_path);


    include_once('settings.php');
    include_once('answer.php');


    $CONN   = NULL;
    $STID   = NULL;
    $RESULT = NULL;


    $url_r = $_SERVER['REQUEST_URI'];
    $url = $_GET['url'];


    $m = array();
    $pattern = '|^/api/v\d+/[\w-]+|';
    $res = preg_match($pattern, $url,$m);

    if ($res==0 or $url!=$m[0]) {
        Error("Wrong API URL");
    }


    $api = explode("/",mb_substr($url,1));

    $file = $api[1]."/api.php";
    if (!file_exists($file)){
        Error("Wrong API Version");
    }

    include_once('secure.php');
    include_once($file);

    db_close();
?>