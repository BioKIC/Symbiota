<?php
header('X-Frame-Options: DENY');
header('Cache-control: private'); // IE 6 FIX
date_default_timezone_set('America/Phoenix');
$CODE_VERSION = '3.0.10';

if(!isset($CLIENT_ROOT) && isset($clientRoot)) $CLIENT_ROOT = $clientRoot;
if(substr($CLIENT_ROOT,-1) == '/') $CLIENT_ROOT = substr($CLIENT_ROOT,0,strlen($CLIENT_ROOT)-1);
if(!isset($SERVER_ROOT) && isset($serverRoot)) $SERVER_ROOT = $serverRoot;
if(substr($SERVER_ROOT,-1) == '/') $SERVER_ROOT = substr($SERVER_ROOT,0,strlen($SERVER_ROOT)-1);
set_include_path(get_include_path() . PATH_SEPARATOR . $SERVER_ROOT . PATH_SEPARATOR . $SERVER_ROOT.'/config/' . PATH_SEPARATOR . $SERVER_ROOT.'/classes/');

session_start(array('gc_maxlifetime'=>3600,'cookie_path'=>$CLIENT_ROOT,'cookie_secure'=>(isset($COOKIE_SECURE)&&$COOKIE_SECURE?true:false),'cookie_httponly'=>true));

include_once($SERVER_ROOT.'/classes/Encryption.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');

//Check cookie to see if signed in
$PARAMS_ARR = Array();				//params => 'un=egbot&dn=Edward&uid=301'
$USER_RIGHTS = Array();
if(isset($_SESSION['userparams'])) $PARAMS_ARR = $_SESSION['userparams'];
if(isset($_SESSION['userrights'])) $USER_RIGHTS = $_SESSION['userrights'];
if(isset($_COOKIE['SymbiotaCrumb']) && !$PARAMS_ARR){
	$tokenArr = json_decode(Encryption::decrypt($_COOKIE['SymbiotaCrumb']), true);
	if($tokenArr){
		$pHandler = new ProfileManager();
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

if(!isset($CSS_BASE_PATH) || $CSS_BASE_PATH == $CLIENT_ROOT . '/css/symb') $CSS_BASE_PATH = $CLIENT_ROOT . '/css/';
if(!isset($CSS_VERSION_RELEASE)) $CSS_BASE_PATH .= 'v202209';

$CSS_VERSION = '13';
$USER_DISPLAY_NAME = (array_key_exists('dn',$PARAMS_ARR)?$PARAMS_ARR['dn']:'');
$USERNAME = (array_key_exists('un',$PARAMS_ARR)?$PARAMS_ARR['un']:0);
$SYMB_UID = (array_key_exists('uid',$PARAMS_ARR)?$PARAMS_ARR['uid']:0);
$IS_ADMIN = (array_key_exists('SuperAdmin',$USER_RIGHTS)?1:0);

//Temporarly needed so that old configuration will still work
if(!isset($DEFAULT_LANG) && isset($defaultLang)) $DEFAULT_LANG = $defaultLang;
if(!isset($DEFAULT_PROJ_ID) && isset($defaultProjId)) $DEFAULT_PROJ_ID = $defaultProjId;
if(!isset($DEFAULT_TITLE) && isset($defaultTitle)) $DEFAULT_TITLE = $defaultTitle;
if(!isset($ADMIN_EMAIL) && isset($adminEmail)) $ADMIN_EMAIL = $adminEmail;
if(!isset($CHARSET) && isset($charset)) $CHARSET = $charset;
if(!isset($TEMP_DIR_ROOT) && isset($tempDirRoot)) $TEMP_DIR_ROOT = $tempDirRoot;
if(!isset($LOG_PATH) && isset($logPath)) $LOG_PATH = $logPath;
if(!isset($IMAGE_DOMAIN) && isset($imageDomain)) $IMAGE_DOMAIN = $imageDomain;
if(!isset($IMAGE_ROOT_URL) && isset($imageRootUrl)) $IMAGE_ROOT_URL = $imageRootUrl;
if(!isset($IMAGE_ROOT_PATH) && isset($imageRootPath)) $IMAGE_ROOT_PATH = $imageRootPath;
if(!isset($IMG_WEB_WIDTH) && isset($imgWebWidth)) $IMG_WEB_WIDTH = $imgWebWidth;
if(!isset($IMG_TN_WIDTH) && isset($imgTnWidth)) $IMG_TN_WIDTH = $imgTnWidth;
if(!isset($IMG_LG_WIDTH) && isset($imgLgWidth)) $IMG_LG_WIDTH = $imgLgWidth;
if(!isset($IMG_FILE_SIZE_LIMIT) && isset($imgFileSizeLimit)) $IMG_FILE_SIZE_LIMIT = $imgFileSizeLimit;
if(!isset($USE_IMAGE_MAGICK) && isset($useImageMagick)) $USE_IMAGE_MAGICK = $useImageMagick;
if(!isset($TESSERACT_PATH) && isset($tesseractPath)) $TESSERACT_PATH = $tesseractPath;
if(!isset($OCCURRENCE_MOD_IS_ACTIVE) && isset($occurrenceModIsActive)) $OCCURRENCE_MOD_IS_ACTIVE = $occurrenceModIsActive;
if(!isset($FLORA_MOD_IS_ACTIVE) && isset($floraModIsActive)) $FLORA_MOD_IS_ACTIVE = $floraModIsActive;
if(!isset($KEY_MOD_IS_ACTIVE) && isset($keyModIsActive)) $KEY_MOD_IS_ACTIVE = $keyModIsActive;
if(!isset($REQUEST_TRACKING_IS_ACTIVE) && isset($RequestTrackingIsActive)) $REQUEST_TRACKING_IS_ACTIVE = $RequestTrackingIsActive;
if(!isset($QUICK_HOST_ENTRY_IS_ACTIVE) && isset($QuickHostEntryIsActive)) $QUICK_HOST_ENTRY_IS_ACTIVE = $QuickHostEntryIsActive;
if(!isset($GOOGLE_MAP_KEY) && isset($googleMapKey)) $GOOGLE_MAP_KEY = $googleMapKey;
if(!isset($MAPPING_BOUNDARIES) && isset($mappingBoundaries)) $MAPPING_BOUNDARIES = $mappingBoundaries;
if(!isset($GOOGLE_ANALYTICS_KEY) && isset($googleAnalyticsKey)) $GOOGLE_ANALYTICS_KEY = $googleAnalyticsKey;
if(!isset($DYN_CHECKLIST_RADIUS) && isset($dynChecklistRadius)) $DYN_CHECKLIST_RADIUS = $dynChecklistRadius;
if(!isset($DISPLAY_COMMON_NAMES) && isset($displayCommonNames)) $DISPLAY_COMMON_NAMES = $displayCommonNames;
if(!isset($RIGHTS_TERMS) && isset($rightsTerms)) $RIGHTS_TERMS = $rightsTerms;
if(!isset($REPRODUCTIVE_CONDITION_TERMS) && isset($reproductiveConditionTerms)) $REPRODUCTIVE_CONDITION_TERMS = $reproductiveConditionTerms;
if(!isset($GLOSSARY_EXPORT_BANNER) && isset($glossaryExportBanner)) $GLOSSARY_EXPORT_BANNER = $glossaryExportBanner;

//temporatly needed until all variables within code are mapped to constants
if(!isset($defaultLang) && isset($DEFAULT_LANG)) $defaultLang = $DEFAULT_LANG;
if(!isset($defaultProjId) && isset($DEFAULT_PROJ_ID)) $defaultProjId = $DEFAULT_PROJ_ID;
if(!isset($adminEmail) && isset($ADMIN_EMAIL)) $adminEmail = $ADMIN_EMAIL;
if(!isset($charset) && isset($CHARSET)) $charset = $CHARSET;
if(!isset($tempDirRoot) && isset($TEMP_DIR_ROOT)) $tempDirRoot = $TEMP_DIR_ROOT;
if(!isset($logPath) && isset($LOG_PATH)) $logPath = $LOG_PATH;
if(!isset($imageDomain) && isset($IMAGE_DOMAIN)) $imageDomain = $IMAGE_DOMAIN;
if(!isset($imageRootUrl) && isset($IMAGE_ROOT_URL)) $imageRootUrl = $IMAGE_ROOT_URL;
if(!isset($imageRootPath) && isset($IMAGE_ROOT_PATH)) $imageRootPath = $IMAGE_ROOT_PATH;
if(!isset($imgWebWidth) && isset($IMG_WEB_WIDTH)) $imgWebWidth = $IMG_WEB_WIDTH;
if(!isset($imgTnWidth) && isset($IMG_TN_WIDTH)) $imgTnWidth = $IMG_TN_WIDTH;
if(!isset($imgLgWidth) && isset($IMG_LG_WIDTH)) $imgLgWidth = $IMG_LG_WIDTH;
if(!isset($imgFileSizeLimit) && isset($IMG_FILE_SIZE_LIMIT)) $imgFileSizeLimit = $IMG_FILE_SIZE_LIMIT;
if(!isset($useImageMagick) && isset($USE_IMAGE_MAGICK)) $useImageMagick = $USE_IMAGE_MAGICK;
if(!isset($occurrenceModIsActive) && isset($OCCURRENCE_MOD_IS_ACTIVE)) $occurrenceModIsActive = $OCCURRENCE_MOD_IS_ACTIVE;
if(!isset($floraModIsActive) && isset($FLORA_MOD_IS_ACTIVE)) $floraModIsActive = $FLORA_MOD_IS_ACTIVE;
if(!isset($keyModIsActive) && isset($KEY_MOD_IS_ACTIVE)) $keyModIsActive = $KEY_MOD_IS_ACTIVE;
if(!isset($RequestTrackingIsActive) && isset($REQUEST_TRACKING_IS_ACTIVE)) $RequestTrackingIsActive = $REQUEST_TRACKING_IS_ACTIVE;
if(!isset($QuickHostEntryIsActive) && isset($QUICK_HOST_ENTRY_IS_ACTIVE)) $QuickHostEntryIsActive = $QUICK_HOST_ENTRY_IS_ACTIVE;
if(!isset($googleMapKey) && isset($GOOGLE_MAP_KEY)) $googleMapKey = $GOOGLE_MAP_KEY;
if(!isset($mappingBoundaries) && isset($MAPPING_BOUNDARIES)) $mappingBoundaries = $MAPPING_BOUNDARIES;
if(!isset($googleAnalyticsKey) && isset($GOOGLE_ANALYTICS_KEY)) $googleAnalyticsKey = $GOOGLE_ANALYTICS_KEY;
if(!isset($dynChecklistRadius) && isset($DYN_CHECKLIST_RADIUS)) $dynChecklistRadius = $DYN_CHECKLIST_RADIUS;
if(!isset($displayCommonNames) && isset($DISPLAY_COMMON_NAMES)) $displayCommonNames = $DISPLAY_COMMON_NAMES;
if(!isset($rightsTerms) && isset($RIGHTS_TERMS)) $rightsTerms = $RIGHTS_TERMS;
if(!isset($reproductiveConditionTerms) && isset($REPRODUCTIVE_CONDITION_TERMS)) $reproductiveConditionTerms = $REPRODUCTIVE_CONDITION_TERMS;
if(!isset($glossaryExportBanner) && isset($GLOSSARY_EXPORT_BANNER)) $glossaryExportBanner = $GLOSSARY_EXPORT_BANNER;

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
//if(!$LANG_TAG || strlen($LANG_TAG) != 2) $LANG_TAG = 'en';

//Sanitization
const HTML_SPECIAL_CHARS_FLAGS = ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE;

if($LANG_TAG != 'en' && !in_array($LANG_TAG, $AVAILABLE_LANGS)) $LANG_TAG = 'en';
?>
