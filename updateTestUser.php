<?php
include_once 'ad_functions.php';
$uname = "testLDAP_EC5";
echo "before bind\n";
$lconn = ad_bind();
echo "after bind\n";
$dn_user = 'CN='.$uname.',OU=Users,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';
//$ldaprecord['cn'] = $uname;
//$ldaprecord['givenName'] = $uname;  
//$ldaprecord['sn'] = $uname;
//$ldaprecord['sAMAccountName'] = $uname;
//$ldaprecord['UserPrincipalName'] = "$uname@xx.xx";
//$ldaprecord['displayName'] = "$uname";
//$ldaprecord['name'] = "$uname";
//$ldaprecord['userAccountControl'] = "544";
//$ldaprecord['objectclass'][0] = 'top';
//$ldaprecord['objectclass'][1] = 'person';
//$ldaprecord['objectclass'][2] = 'organizationalPerson';
//$ldaprecord['objectclass'][3] = 'user';
//$ldaprecord['mail'] = "$uname@baymark.com";
$ldaprecord['st'] = 'FL';
//manager needs to be a looked up distinguishedName
$ldaprecord['manager'][0] = 'CN=Megan Singh,OU=Lewisville,OU=TX,OU=Users,OU=BMHS,DC=ad,DC=baymarkhealth,DC=com';//'CN='.'Nadine Robbins-Laurent,OU=Users,OU=BHS,DC=ad,DC=baymarkhealth,DC=com';
print_r($dn_user);
echo "\n";
print_r($ldaprecord);
$add = ldap_mod_replace($lconn, $dn_user, $ldaprecord);

    if($add) {
        echo "User successfully modified";
    } else {
        echo "User not modified";
    }
ldap_close($lconn);
?>
