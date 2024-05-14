<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OpenIdProfileManager.php');
include_once($SERVER_ROOT . '/config/auth_config.php');
require_once($SERVER_ROOT . '/vendor/autoload.php');
use Jumbojett\OpenIDConnectClient;
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/profile/authCallback.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT.'/content/lang/profile/authCallback.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/profile/authCallback.en.php');


$profManager = new OpenIdProfileManager();

$AUTH_PROVIDER = $AUTH_PROVIDER ?? 'oid';

$oidc = new OpenIDConnectClient($providerUrls[$AUTH_PROVIDER], $clientIds[$AUTH_PROVIDER], $clientSecrets[$AUTH_PROVIDER], $providerUrls[$AUTH_PROVIDER]); // assumes that the issuer is identical to the providerUrl, as seems to be the case for microsoft

if(isset($shouldUpgradeInsecureRequests)){
  $oidc->setHttpUpgradeInsecureRequests($shouldUpgradeInsecureRequests);
}
if(isset($shouldVerifyPeers)){
  $oidc->setVerifyPeer($shouldVerifyPeers);
}


if (array_key_exists('code', $_REQUEST) && $_REQUEST['code']) {
  
  try{
    $status = $oidc->authenticate();
  }
  catch (Exception $ex){
    $_SESSION['last_message'] = $LANG['CAUGHT_EXCEPTION'] . ' ' . $ex->getMessage() . ' <ERR/>';
    header('Location:' . $CLIENT_ROOT . '/profile/index.php');
    exit();
  }  
  if($status){
    $sub = $oidc->requestUserInfo('sub');
    if($profManager->authenticate($sub, $providerUrls['oid'])){
      if($_SESSION['refurl']){
        header("Location:" . $_SESSION['refurl']);
        unset($_SESSION['refurl']);
      }
    }
    else {
      if ($email = $oidc->requestUserInfo('email')){
        // Authprovider returned a subscriber; however, user was not authenticated to local user account
        try{
          $status = $profManager->linkLocalUserOidSub($email, $sub, $oidc->getProviderURL());
        }catch (Exception $ex){
          $_SESSION['last_message'] = $LANG['CAUGHT_EXCEPTION'] . ' '  . $ex->getMessage();
          header('Location:' . $CLIENT_ROOT . '/profile/index.php');
          exit();
        }
        if($status){
          if($profManager->authenticate($sub, $providerUrls['oid'])){
            if($_SESSION['refurl']){
              header("Location:" . $_SESSION['refurl']);
              unset($_SESSION['refurl']);
            }
          }
          else{
            $_SESSION['last_message'] = $LANG['UNKNOWN_ERROR'] . " <ERR/>";
            header('Location:' . $CLIENT_ROOT . '/profile/index.php');
            //@TODO Consider logging this error to PHP logfiles
          }
        }else{
          $_SESSION['last_message'] = $LANG['ERROR'] . " <ERR/>";
          header('Location:'. $CLIENT_ROOT . '/profile/index.php');
        }
        
      }
      else{
        $_SESSION['last_message'] = $LANG['UNABLE_RETRIEVE_EMAIL'] . " <ERR/>";
        header('Location:' . $CLIENT_ROOT . '/profile/index.php');
      }
    }
  }
  $_SESSION['last_message'] = $LANG['AUTHENTICATION_FAILED'] . " <ERR/>";
  header('Location:' . $CLIENT_ROOT . '/profile/index.php');
}