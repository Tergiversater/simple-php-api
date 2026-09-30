<?php

    if (isset($api_debug['Errors'])){
        if ($api_debug['Errors']) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
        }
    }

    include_once('functions.php');

    $method = $api[1]."/methods/".$api[2].".php";
    if (!file_exists($method)){
        Error("Wrong API Method: $api[2]");
    }

    $timer=0;

    $sql_count = 0;
    $ans_total = false;

    $page_cur = 0;
    $page_total = 0;

    $is_POST = false;
    $api_name_trunk = false;

    $api_get = array();
    $method_get = array();

#    $api_post = array;
    $method_post = array();

    $can_part = false;

    $sql_filter = array();

    include_once(realpath(dirname(__FILE__).'/../../plugins/core.php'));

    foreach ($api_get_param ?? array() as $key => $item) {
        $api_get[$key] = false;
        $method_get[$key] = false;
    }

    foreach ($api_post_param as $key => $item) {
#        $api_post[$key] = $item[1];
        $method_post[$key] = $item[1];
    }



    include_once($method);

    foreach ($api_use_param as $key => $item) {
        if (!(isset($method_use[$key]))){
            $method_use[$key] = $item[0];
        }

        if (isset($item[1])){
            $tmp_a=explode(',',$item[1]);
            if (!in_array($method_use[$key],$tmp_a)){
                $method_use[$key] = $item[0];
            }
        }
    }

    RunApiPluginHook('read_request');

    foreach ($method_use as $key => $item){
        if (isset($item[2])){
            $tmp_a=explode(',',$item[2]);
            foreach ($tmp_a as $tmp_k => $tmp_i){
                $method_get[$tmp_i] = true;
            }
        }
    }

    if (isset($api_debug['Timer'])){
        if ($api_debug['Timer']) {
            $timer=microtime(true);;
        }
    }


    $ans=array();

    if (!$is_POST){
        RunApiPluginHook('prepare');

        $_filter='';
        foreach ($sql_filter as $fltr_item){
            $_filter.=' and '.$fltr_item;
        }
        if ($_filter!=''){
            $_filter=' WHERE '.mb_substr($_filter,4);

        }


        RunApiPluginHook('before_query');

        $sql = 'SELECT * FROM '.$api_db.$_filter;
        RunApiPluginHook('wrap_query');


        $STID = oci_parse($CONN, $sql);

        oci_set_prefetch($STID, 100);  // Set before calling oci_execute()
        oci_execute($STID);


        $i=0;
        $ans[$api_name] = array();



        while ($row = oci_fetch_array($STID, OCI_ASSOC+OCI_RETURN_NULLS)) {;
            $method_use['Model']($row,$i); # Fill_Answer
        $i++;
        }


        RunApiPluginHook('answer');

        if ($timer>0){
            $a['Used Time'] = microtime(true) - $timer;
            $ans = $a + $ans;
        }

    }else{
        $rawData = file_get_contents("php://input");

        if (true) {
            $logFile = __DIR__ . "/logs/request.json.log";
            $logEntry = [
                "timestamp" => date("Y-m-d H:i:s"),
                "data" => json_decode($rawData, true) ?? $rawData
            ];
            file_put_contents($logFile, json_encode($logEntry, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        }

        $check = validateJsonStructure($rawData, $method_post['json_example'], $method_post['required_fields'], $method_post['optional_fields'], $method_post['offtop_param'] , $prefix = '');

        if ($check){
            $tmp = PostData($api_db, $rawData);

            ###    $ans[$api_name] = $tmp;
            $api_keys = explode(',', $api_name);

            $api_values = explode(',', $tmp);

            $key_count = count($api_keys);
            $value_count = count($api_values);

            if ($key_count > $value_count) {
                if ($api_name_trunk) { // Глобальний параметр, який визначає поведінку
                    $api_keys = array_slice($api_keys, 0, $value_count); // Обрізаємо ключі до кількості значень
                } else {
                    $api_values = array_pad($api_values, $key_count, ''); // Розширяємо значення
                }
                #$api_values = array_pad($api_values, $key_count, '');
            } elseif ($key_count < $value_count) {
                $api_values = array_slice($api_values, 0, $key_count);
            }

            $ans = array_combine($api_keys, $api_values);
            $ans = array_map('FormatField', $ans);

        }else{
            Error("Error: Invalid incoming JSON");
        }
    }


    Answer($ans);
    @db_close();
?>
