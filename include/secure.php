<?php

include_once ('db_connect.php');


$headers = getallheaders();
$bearer = '_';
if (array_key_exists('Authorization',$headers)){
    $bearer = $headers['Authorization'];
    $res = preg_match('|(?<=Bearer )([A-Za-z0-9/-])+|',$bearer,$m);
    if ($res) {
        $bearer = $m[0];
    }

}


#$bearer = '123321';

/*
$conn = oci_connect($DB_USER, $DB_PASSWORD, $DB_SERVER.'/'.$DB_NAME);
if (!$conn) {
    $e = oci_error();
    Error ("DB CONN ERR");
    exit;
}
*/
$CONN=db_conn();

$sql = "BEGIN :res:=GET_SECURE(:test_in, :test_out); END;";
$STID = oci_parse($CONN, $sql);

oci_bind_by_name($STID,':test_in',$t_in,40);
oci_bind_by_name($STID,':test_out',$t_out,40);
oci_bind_by_name($STID,':res',$res,6);

$t_in = $bearer;

oci_execute($STID);

if ($res<>200) {
    Error ("Wrong Authorization DATA");
}

db_clear($STID);


?>