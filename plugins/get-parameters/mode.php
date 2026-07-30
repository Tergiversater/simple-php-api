<?php

function ApplyModeGetParameter()
{
    global $method_use, $api_get, $api_db, $api_upd_field, $api_upd_suffix;
    global $can_part, $sql_filter;

    if ($method_use['Partial']) {
        $can_part = Is_Field_Set($api_db, $api_upd_field);
    }
    if (!$can_part or $api_get['mode'] != 'partial') {
        $api_get['from'] = 0;
    } else {
        $sql_filter[] = $api_upd_field.'>= (sysdate-'.$api_get['from'].'/(24*3600)) ';
    }

    if ($method_use['Preparation'] and !(($api_get['number'] ?? 0) > 0)) {
        $prepare = true;
        if (($api_get['pageIndex'] ?? 1) == 1) {
            $prepare = PrepareTable($api_db, $api_get['from'], $api_upd_suffix);
        }
        if ($prepare) {
            $api_db = ($api_get['from'] > 0) ? 'SYS_'.$api_db.$api_upd_suffix : 'SYS_'.$api_db;
        }
    }
}

RegisterGetParameterPlugin('mode', array(
    'parameters' => array(
        'mode' => array('str', 'full', 'full,partial'),
        'from' => array('int,abs,nz', 86400)
    ),
    'prepare' => 'ApplyModeGetParameter'
));

?>
