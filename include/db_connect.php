<?php

include_once ("db_config.php");

function db_conn ()
{
    global $DB_USER, $DB_PASSWORD, $DB_SERVER, $DB_NAME, $DB_ENCODING;

    @$dbconn = oci_connect($DB_USER, $DB_PASSWORD, $DB_SERVER.'/'.$DB_NAME, $DB_ENCODING);
    if (!$dbconn) {
        $e = oci_error();
        Error ("DB CONN ERR: ".$e['message']);
    exit;
    }

    return $dbconn;
}

function db_close ()
{
    global $CONN, $STID, $RESULT;

    db_clear($STID);
    db_clear($RESULT);

    oci_close($CONN);
    $CONN = NULL;
}

function db_clear(&$stid)
{
    if (!is_null($stid)){
        oci_free_statement($stid);
    }
    $stid = NULL;
}


function get_sql_count($table, $fltr='')
{
    global $CONN;

    $sql = "select count(*) from ".$table.' '.$fltr;
    $stid = oci_parse($CONN, $sql);

    oci_execute($stid);

    $row = oci_fetch_array($stid, OCI_BOTH);

    db_clear($stid);

    return (isset($row[0]))?$row[0]:false;
}


function Is_Field_Set($table,$field)
{
    global $CONN;

    $sql = "BEGIN :res:=CHECK_FIELD(:p1_in, :p2_out); END;";
    $stid = oci_parse($CONN, $sql);

    oci_bind_by_name($stid,':p1_in',$table,32);
    oci_bind_by_name($stid,':p2_out',$field,32);
    oci_bind_by_name($stid,':res',$res,6);

    oci_execute($stid);

    oci_free_statement($stid);

    return ($res==200);

}

function PrepareTable ($table, $num = 0, $part = '_PART')
{
    global $CONN;

    $sql = "BEGIN :res:=SET_PREPARATION(:table_in, :num_in, :part_in); END;";
    $stid = oci_parse($CONN, $sql);

    oci_bind_by_name($stid,':table_in',$t_in,32);
    oci_bind_by_name($stid,':num_in',$num,11);
    oci_bind_by_name($stid,':part_in',$part,32);
    oci_bind_by_name($stid,':res',$res,6);

    $t_in = $table;

    oci_execute($stid);


    db_clear($stid);

    return ($res==200);

}

function PostData ($proc, $data)
{
    global $CONN;

    if ($proc == 'DUAL') {
        return 'Test OK';
    }

    $sql = "BEGIN :res:=SET_API_POST(:proc_in, :data_in, :res_out); END;";
    $stid = oci_parse($CONN, $sql);

    oci_bind_by_name($stid,':proc_in',$proc_in, 50);
    oci_bind_by_name($stid,':data_in',$data_in, 32000);
    oci_bind_by_name($stid,':res_out',$res_out, 32000);
    oci_bind_by_name($stid,':res',$res,12);

    $proc_in = $proc;
    $data_in = $data;

    oci_execute($stid);


    db_clear($stid);

    if ($res==200){
        return $res_out;
    }else{
        Error ("Internal Error $res");
    }

}



/*
$conn = oci_connect($DB_USER, $DB_PASSWORD, $DB_SERVER.'/'.$DB_NAME);
if (!$conn) {
    $e = oci_error();
    Error ("DB CONN ERR");
    exit;
}

$sql = "BEGIN :res:=GET_SECURE(:test_in, :test_out); END;";
$stmt = oci_parse($conn, $sql);

oci_bind_by_name($stmt,':test_in',$t_in,32);
oci_bind_by_name($stmt,':test_out',$t_out,32);
oci_bind_by_name($stmt,':res',$res,6);


#$t_in ="123321";
$t_in = $bearer;

oci_execute($stmt);

if ($res<>200) {
    Error ("Wrong Authorization DATA");
}

oci_free_statement($stmt);
oci_close($conn);
*/

?>