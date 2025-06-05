<?php
include_once 'ad_functions.php';
$uname = "testLDAP_EC3";
$lconn = ad_bind();
$dn_user = 'givenName='.$uname;
$ldap_base_dn = 'OU=Users,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
$record = ldap_search($lconn, $ldap_base_dn, $dn_user);
print_r($record);
$r = ldap_get_entries($lconn, $record);
print_r($r);
ldap_close($lconn);
?>
