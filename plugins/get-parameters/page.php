<?php

function PreparePageGetParameter()
{
    global $method_use, $ans_total;
    if ($method_use['Paged']) {
        $ans_total = true;
    }
}

function CountPageGetParameter()
{
    global $ans_total, $api_db, $_filter, $sql_count, $page_total, $api_get;
    if (!$ans_total) {
        return;
    }

    $sql_count = get_sql_count($api_db, $_filter);
    $page_total = ceil($sql_count / $api_get['pageSize']);
    if ($page_total < $api_get['pageIndex']) {
        $api_get['pageIndex'] = $page_total;
    }
}

function WrapPageGetParameterQuery()
{
    global $ans_total, $api_get, $sql;
    if (!$ans_total) {
        return;
    }

    $offset = ($api_get['pageIndex'] - 1) * $api_get['pageSize'];
    $limit = $api_get['pageSize'] + $offset;
    $sql = "SELECT * FROM (SELECT t.* , rownum AS sys_rnum FROM (".$sql.") t WHERE rownum <= ".$limit.") WHERE sys_rnum > ".$offset;
}

function AddPageGetParameterAnswer()
{
    global $ans_total, $ans, $page_total, $api_get;
    if (!$ans_total) {
        return;
    }

    $ans['total_pages'] = $page_total;
    $ans['page_number'] = $api_get['pageIndex'];
    $ans['last'] = !($ans['page_number'] < $ans['total_pages']);
}

RegisterGetParameterPlugin('page', array(
    'parameters' => array(
        'pageSize' => array('int,abs,nz,max:1000', 100),
        'pageIndex' => array('int,abs,nz', 1)
    ),
    'prepare' => 'PreparePageGetParameter',
    'before_query' => 'CountPageGetParameter',
    'wrap_query' => 'WrapPageGetParameterQuery',
    'answer' => 'AddPageGetParameterAnswer'
));

?>
