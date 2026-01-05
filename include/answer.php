<?php

function Answer ($data) 
{
    global $CONN,$STID, $api_json_pretty, $api_debug;

    $header_type = 'Content-Type: application/json; charset=utf-8';


    if ($api_debug['NoJSON']){
        $header_type = 'Content-Type: text/html; charset=utf-8';
    }

    $flags=($api_json_pretty)?JSON_PRETTY_PRINT:0;

    if (!is_null($STID)){
        db_clear($STID);
    }

    if (!is_null($CONN)){
        db_close();
    }

    ;

    if ($api_debug['devapi']) {
        $data = ['devapi' => true] + $data;
    }

    header ($header_type);
    echo json_encode($data, $flags+JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);

    exit;
}

function Error ($text)
{
  $res = array ("error" => true, "errorDescription" => $text);
  Answer($res);
}

?>