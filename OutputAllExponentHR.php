<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
$jinfo = getAllJobInfo();
$info = getAllInfo();
print_r($jinfo);
print_r($info);
//$lconn = ad_bind();
//loop over each merged info array item
//$au = fetchAllAdUsers($lconn);
//print_r($au);
foreach($jinfo as $k=>$v) {
	echo $v['Email']."\n";
}
?>
