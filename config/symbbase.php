<?php
header('X-Frame-Options: DENY');
header('Cache-control: private'); // IE 6 FIX
date_default_timezone_set('America/Phoenix');
$CODE_VERSION = '3.1.8';

set_include_path(get_include_path() . PATH_SEPARATOR . $SERVER_ROOT . PATH_SEPARATOR . $SERVER_ROOT.'/config/' . PATH_SEPARATOR . $SERVER_ROOT.'/classes/');

session_start(array('gc_maxlifetime'=>3600,'cookie_path'=>$CLIENT_ROOT,'cookie_secure'=>(isset($COOKIE_SECURE)&&$COOKIE_SECURE?true:false),'cookie_httponly'=>true));

include_once($SERVER_ROOT.'/classes/Encryption.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');

$pHandler = new ProfileManager();
//Check session data to see if signed in
$PARAMS_ARR = Array();				//params => 'un=egbot&dn=Edward&uid=301'
$USER_RIGHTS = Array();
if(isset($_SESSION['userparams'])) $PARAMS_ARR = $_SESSION['userparams'];
if(isset($_SESSION['userrights'])) $USER_RIGHTS = $_SESSION['userrights'];
if(isset($_COOKIE['SymbiotaCrumb']) && !$PARAMS_ARR){
	$tokenArr = json_decode(Encryption::decrypt($_COOKIE['SymbiotaCrumb']), true);
	if($tokenArr){
		if((isset($_REQUEST['submit']) && $_REQUEST['submit'] == 'logout') || isset($_REQUEST['loginas'])){
	        $pHandler->deleteToken($pHandler->getUid($tokenArr[0]),$tokenArr[1]);
		}
		else{
			if($pHandler->setUserName($tokenArr[0])){
				$pHandler->setRememberMe(true);
				$pHandler->setToken($tokenArr[1]);
				if($pHandler->authenticate()){
					if(isset($_SESSION['userparams'])) $PARAMS_ARR = $_SESSION['userparams'];
					if(isset($_SESSION['userrights'])) $USER_RIGHTS = $_SESSION['userrights'];
				}
				else $pHandler->reset();
			}
		}
	}
}

if(!isset($CSS_BASE_PATH) || $CSS_BASE_PATH == $CLIENT_ROOT . '/css/symb') $CSS_BASE_PATH = $CLIENT_ROOT . '/css';

$EXTERNAL_PORTAL_HOSTS = [];

$USER_DISPLAY_NAME = (array_key_exists('dn',$PARAMS_ARR)?$PARAMS_ARR['dn']:'');
$USERNAME = (array_key_exists('un',$PARAMS_ARR)?$PARAMS_ARR['un']:0);
$SYMB_UID = (array_key_exists('uid',$PARAMS_ARR)?$PARAMS_ARR['uid']:0);
$IS_ADMIN = (array_key_exists('SuperAdmin',$USER_RIGHTS)?1:0);

//Set accessibilty variables
$ACCESSIBILITY_ACTIVE = false;
if($SYMB_UID){
	$isAccessiblePreferred = $pHandler->getAccessibilityPreference($SYMB_UID);
	if($isAccessiblePreferred){
		$ACCESSIBILITY_ACTIVE = true;
	}
}

//$AVAILABLE_LANGS = array('en','es','fr','pt','ab','aa','af','sq','am','ar','hy','as','ay','az','ba','eu','bn','dz','bh','bi','br','bg','my','be','km','ca','zh','co','hr','cs','da','nl','eo','et','fo','fj','fi','fy','gd','gl','ka','de','el','kl','gn','gu','ha','iw','hi','hu','is','in','ia','ie','ik','ga','it','ja','jw','kn','ks','kk','rw','ky','rn','ko','ku','lo','la','lv','ln','lt','mk','mg','ms','ml','mt','mi','mr','mo','mn','na','ne','no','oc','or','om','ps','fa','pl','pa','qu','rm','ro','ru','sm','sg','sa','sr','sh','st','tn','sn','sd','si','ss','sk','sl','so','su','sw','sv','tl','tg','ta','tt','te','th','bo','ti','to','ts','tr','tk','tw','uk','ur','uz','vi','vo','cy','wo','xh','ji','yo','zu');
$AVAILABLE_LANGS = array('en','es','fr','pt');
//Multi-langauge support
$LANG_TAG = 'en';
if(isset($_REQUEST['lang']) && $_REQUEST['lang']){
	$LANG_TAG = $_REQUEST['lang'];
	setcookie('lang', $LANG_TAG, time() + (3600 * 24 * 30),'/');
}
else if(isset($_COOKIE['lang']) && $_COOKIE['lang']){
	$LANG_TAG = $_COOKIE['lang'];
}
else{
	if(strlen($DEFAULT_LANG) == 2) $LANG_TAG = $DEFAULT_LANG;
}
if($LANG_TAG != 'en' && !in_array($LANG_TAG, $AVAILABLE_LANGS)) $LANG_TAG = 'en';

//Sanitization
const HTML_SPECIAL_CHARS_FLAGS = ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE;

$CSS_VERSION = '16';

?>
