<?php

define ('PATTERN_ARRAY','|^array(.)((?1)\d*(?1))(?1)|');
define ('PATTERN_VALUE_ARRAY','|#(\d*)#|');


function Fill_Answer_Default ($row,$i)
{
    global $ans, $api_name;

    $long_item="";

    foreach ($row as $key => $item){

        if (mb_substr($key,0,4)=='SYS_'){
            continue;
        }
        if (mb_substr($key,0,8)=='SYSPART_'){
            $tmp_key=explode('_',$key);
            $tmp_d=mb_substr($item,0,1);
            $item=mb_substr($item,1);
            if($tmp_key[3]==="0") {
                $long_item="array$tmp_d$tmp_d$tmp_key[2]$tmp_d$tmp_d".$item;
                continue;
            }elseif($tmp_key[3]==='00'){
                $item=$long_item."$tmp_d$tmp_d$tmp_key[2]$tmp_d$tmp_d".$item;
                $key=$tmp_key[1];
            }else{
                $long_item=$long_item."$tmp_d$tmp_d$tmp_key[2]$tmp_d$tmp_d".$item; 
                continue;
            }
        }

        if (mb_substr($key,0,9)=='SYSPART2_'){
            $tmp_key=explode('_',$key);
            $tmp_d=mb_substr($item,0,1);
            $item=mb_substr($item,1);
            if($tmp_key[3]==="0") {
                $long_item=$item;
                continue;
            }elseif($tmp_key[3]==='00'){
                $item=$long_item.$item;
                $key=$tmp_key[1];
            }else{
                $long_item=$long_item.$item; 
                continue;
            }
        }

        $ans[$api_name][$i][$key] = GetField($item,$key);

    }
}

function FormatField($str)
{

    if ($str=='_false_'){
        return false;
    }
    if ($str=='_true_'){
        return true;
    }
    if ($str=='_null_'){
        return null;
    }
    if (is_null($str)){
        return '';
    } 
    if (is_numeric($str) and ($str[0]=='.')){
        return "0".$str;
    }

    return $str;
}



function GetField ($item,$key = "")
{

    $res_array=array();


        $m = array();
        $tmp_a = array();
        $tmp_k = array();
        $tmp_v = array();

        $res=preg_match(PATTERN_ARRAY,$item ?? '',$m);

        if (!$res){
            $m_v=array();
            $res_v=preg_match(PATTERN_VALUE_ARRAY,$item ?? '',$m_v);

            if (!$res_v){
                return FormatField($item);
            }else{
                $res_array=explode("#$m_v[1]#",$item);
                for ($ii=0;$ii<count($res_array);$ii++){
                    $res_array[$ii] = FormatField($res_array[$ii]);
                }
            }
        }else{
            $tmp_a=explode("$m[1]$m[2]$m[1]",$item);
            if ($m[1]==':'){
                if (count($tmp_a)==3){
                    $tmp_k=explode(",$m[2],",$tmp_a[1]);
                    $tmp_v=explode(",$m[2],",$tmp_a[2]);
                    for ($j =0 ; $j < count($tmp_k); $j++){
                        $res_array[$tmp_k[$j]] = GetField($tmp_v[$j]); #ToBoolean($tmp_v[$j]);
                    }
                }else{
                    return array();
                }
            }elseif ($m[1]=='|'){
                if (count($tmp_a)>=3){
                    $tmp_k=explode(",$m[2],",$tmp_a[1]);

                    for ($k=0;$k<count($tmp_k);$k++){
                        $tmp_v[$k]=explode(",$m[2],",$tmp_a[2+$k]);
                    }

                    for ($j=0;$j<count($tmp_v[0]);$j++){
                        for ($k=0;$k<count($tmp_k);$k++){
                            $res_array[$j][$tmp_k[$k]]=GetField($tmp_v[$k][$j]);
                        }
                    }
                }else{
                    return array();
                }
            }elseif ($m[1]=='['){
                if (count($tmp_a)>=3){
                    $tmp_k=explode(",$m[2],",$tmp_a[1]);

                    for ($k=0;$k<count($tmp_k);$k++){
                        $tmp_v[$k]=explode(",$m[2],",$tmp_a[2+$k]);
                    }

                    for ($j=0;$j<count($tmp_v[0]);$j++){
                        for ($k=0;$k<count($tmp_k);$k++){

                            $multikey = explode('#', $tmp_k[$k]);
                            $expl_array=&$res_array;
                            $r_m_a = &$expl_array;
                            $is_first=true;

                            foreach ($multikey as $multikey_item) {
                                $j_j=0;
                                if($is_first){
                                    $j_j=$j;
                                    $is_first=false;
                                    $r_m_a = &$r_m_a[$j_j][$multikey_item];
                                }else{
                                    $r_m_a = &$r_m_a[$multikey_item];
                                }
                            }
                            $r_m_a=GetField($tmp_v[$k][$j]);


#                            $res_array[$j][$tmp_k[$k]]=GetField($tmp_v[$k][$j]);
                        }
                    }
                }else{
                    return array();
                }
            }
        }

    return $res_array;
}

/*
function validateFieldType($value, $type) {
    $type_map = [
        'string' => 'is_string',
        'int' => 'is_int',
        'float' => 'is_float',
        'bool' => 'is_bool',
        'datetime' => function($val) { return strtotime($val) !== false; } // простий тест для дати
    ];

    return isset($type_map[$type]) ? $type_map[$type]($value) : true;
}
*/

function validateFieldType($value, $type) {
    global $strong_float; // Оголошення глобальної змінної

    $type_map = [
        'string' => 'is_string',
        'int' => 'is_int',
        'float' => function($val) use ($strong_float) {
            return $strong_float ? is_float($val) : is_float($val) || is_int($val);
        },
        'bool' => 'is_bool',
        'datetime' => function($val) { return strtotime($val) !== false; } // Простий тест для дати
    ];

    return isset($type_map[$type]) ? $type_map[$type]($value) : true; 
}




function validateJsonStructure($data, $example, $required_fields, $optional_fields, $offtop_param, $prefix = '') {

    if (!is_array($data)) {
        $decoded = json_decode($data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $data = $decoded;
        } else {
            Error ('Internal error: Expected array or valid JSON string.');
        }
    }

    if (!is_array($example)) {
        $decoded = json_decode($example, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $example = $decoded;
        } else {
            Error ('Internal error: Invalid sample-code');
        }
    }



    foreach ($data as $key => $value) {
        $current_key = $prefix ? "{$prefix}.{$key}" : $key;

        // Перевірка наявності ключа в прикладі
        if (!array_key_exists($key, $example)) {
            if ($offtop_param === 'ignore') {
                continue;
            } elseif ($offtop_param === 'clear') {
                unset($data[$key]); // Видалити зайвий ключ
            } elseif ($offtop_param === 'error') {
                Error ("Error: Unexpected field $current_key found in request.");
                return false; // Завершити валідацію з помилкою
            }
        }
    }

    foreach ($example as $key => $type) {
        $current_key = $prefix ? "{$prefix}.{$key}" : $key;
        
        // Перевірка наявності обов'язкового поля
        $is_required = in_array($current_key, $required_fields) || 
                       (!in_array($current_key, $optional_fields) && count($required_fields) == 0);
        
        if ($is_required && !array_key_exists($key, $data)) {
            Error ("Error: Missing required field: $current_key");
            continue;
        }
        
        if (!isset($data[$key])) continue;
        
        // Перевірка вкладених структур
        if (is_array($type) && isset($type[0]) && is_array($type[0])) {
            foreach ($data[$key] as $item) {
                validateJsonStructure($item, $type[0], $required_fields, $optional_fields, $offtop_param, $current_key);
            }
        } elseif (is_array($type)) {
            validateJsonStructure($data[$key], $type, $required_fields, $optional_fields, $offtop_param, $current_key);
        } else {
            // Перевірка відповідності типу
            if (!validateFieldType($data[$key], $type)) {
                Error ("Error: Field $current_key expected type $type, but got " . gettype($data[$key]) );
            }
        }
    }
    return true;
}


?>