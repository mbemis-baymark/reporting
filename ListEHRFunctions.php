<?php

include_once 'exponenthr_functions.php';
include_once 'ad_functions.php';
//$einfo = getContactInfoById(); 
$sc = getSoapConnector();
print_r($sc->__getFunctions());
?>
