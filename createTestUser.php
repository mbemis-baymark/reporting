<?php
include_once 'ad_functions.php';
$uname = "testBroy4";
$lconn = ad_bind();
//$snemail = getNextServiceNowUserCreationTickets();
//foreach($snemail as $req) {
$dn_user = 'CN='.$uname.',OU=Users,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
$ldaprecord['cn'] = $uname;
$ldaprecord['displayName'] = $uname;
$ldaprecord['givenName'] = $uname;  
$ldaprecord['sAMAccountName'] = $uname;
$ldaprecord['UserPrincipalName'] = "$uname@xx.xx";
$ldaprecord['objectclass'][0] = 'top';
$ldaprecord['objectclass'][1] = 'person';
$ldaprecord['objectclass'][2] = 'organizationalPerson';
$ldaprecord['objectclass'][3] = 'user';
$ldaprecord['mail'] = "$uname@baymark.com";
$ldaprecord['st'] = 'NH';
$add = ldap_add($lconn, $dn_user, $ldaprecord);
//	updateEHRAEmail("$uname@baymark.com");
//	updateSNEmailTicket($snemail['ticketnumber'], $ldaprecord['mail']);
if($add) {
	echo "User successfully added $uname\n";
} else {
	echo "User not added\n";
}
//}
ldap_close($lconn);
?>
