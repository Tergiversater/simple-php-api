<?php


foreach ($api_get_param as $key => $item){
    if (isset($_GET[$key])){
        $tmp=strtolower($_GET[$key]);

        $tmp_i0=explode(',',$item[0]);

        if(in_array('int',$tmp_i0)){
            if (!is_numeric($tmp)){
                $tmp=$item[1];
            }
            if(in_array('abs',$tmp_i0)){
                $tmp=abs($tmp);
            }
            if(in_array('nz',$tmp_i0)){
                if($tmp==0){
                    $tmp=$item[1];
                }
            }
        }

        $max=array_search_part($tmp_i0,'max');
        if ($max!==false){
            if ($tmp>$max){
                $tmp=$max;
            }
        }

        if (isset($item[2])){
            $tmp_i2=explode(',',$item[2]);
            if (!in_array($tmp,$tmp_i2)){
                $tmp=$item[1];
            }
        }

        $api_get[$key]=$tmp;
    }else{
        $api_get[$key]=$item[1];
    }

}


function array_search_part($a,$text)
{

    $len=strlen($text);

    foreach ($a as $key => $item){
        if (substr($item,0,$len+1)==$text.':'){
            $tmp_a = explode(':',$item);
            return $tmp_a[1];
        }
    }

    return false;
}


?>