<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
$jinfo = getJobInfoById();
$info = getInfoById();
$lconn = ad_bind();
//loop over each merged info array item
$au = fetchAllAdUsers($lconn);
foreach($au as $k=>$a) {
	echo "main email is this ->".$k."\n";	
	print_r($a['userprincipalname']);
	print_r($a['proxyaddresses']);
	echo "\n\n\n\n\n";
}
die();
