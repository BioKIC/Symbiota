<?php
$providerUrls = array('oid' => 'https://login.microsoftonline.com/AddMore/v2.0', 'google' => 'foo');
$clientIds = array('oid' => 'someGuid', 'google' => 'foo');
$clientSecrets = array('oid' => 'someGuid', 'google' => 'foo');
$callBackRedirect = 'https://' . $SERVER_HOST . $CLIENT_ROOT . '/profile/authCallback.php';

// Needed for local Dev Env Only
// $shouldUpgradeInsecureRequests = false; // this needs to be commented in if you're developing locally without ssl enabled
// $shouldVerifyPeers = false;
?>