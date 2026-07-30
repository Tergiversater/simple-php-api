<?php

function ApplyNumberGetParameter()
{
    global $method_use, $api_get, $api_filter_field, $sql_filter;

    if ($method_use['PickOne'] and $api_get['number'] > 0) {
        $sql_filter[] = $api_filter_field.'='.$api_get['number'];
    }
}

RegisterGetParameterPlugin('number', array(
    'parameters' => array(
        'number' => array('int,abs,nz', 0)
    ),
    'prepare' => 'ApplyNumberGetParameter'
));

?>
