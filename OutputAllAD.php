<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
//$jinfo = getJobInfoById();
//$info = getInfoById();
$lconn = ad_bind();
//loop over each merged info array item
$au = fetchAllAdUsers($lconn);
echo json_encode($au);

//print_r($au);
?>
