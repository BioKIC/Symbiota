<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/vendor/autoload.php');
include_once($SERVER_ROOT . '/config/auth_config.php');

use Jumbojett\OpenIDConnectClient;

$AUTH_PROVIDER = $AUTH_PROVIDER ?? 'oid';
$oidc = new OpenIDConnectClient($providerUrls[$AUTH_PROVIDER],
                                $clientIds[$AUTH_PROVIDER],
                                $clientSecrets[$AUTH_PROVIDER]);

$oidc->addScope(array('openid'));
$oidc->addScope(array('email'));
$oidc->setResponseTypes(array('code'));
//$oidc->setResponseTypes(array('id_token'));
$oidc->setRedirectUrl($callBackRedirect);

if(isset($shouldUpgradeInsecureRequests)){
  $oidc->setHttpUpgradeInsecureRequests($shouldUpgradeInsecureRequests);
}
if(isset($shouldVerifyPeers)){
  $oidc->setVerifyPeer($shouldVerifyPeers);
}

// $_SESSION['oidIssuer'] = $oidc->getIssuer(); // moot for microsoft where it's the same as the providerUrl, but potentially useful for other auth providers?
$oidc->authenticate();

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
    <title>OpenID Connect Client</title>
    <style>
        body {
            font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
        }
    </style>
</head>
<body>
    <h1 class="page-heading">OpenID Connect Client</h1>
    <div>
        Hello <?php echo $name; ?>
    </div>

</body>
</html>

