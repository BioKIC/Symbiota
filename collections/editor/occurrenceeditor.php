<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.'.$LANG_TAG.'.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/occurrenceeditor.en.php');


header('Content-Type: text/html; charset=' . $CHARSET);
$occId = array_key_exists('occid', $_REQUEST) ? filter_var($_REQUEST['occid'], FILTER_SANITIZE_NUMBER_INT) : '';
$collId = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : false;
$tabTarget = array_key_exists('tabtarget', $_REQUEST) ? filter_var($_REQUEST['tabtarget'], FILTER_SANITIZE_NUMBER_INT) : 0;
$goToMode = array_key_exists('gotomode', $_REQUEST) ? filter_var($_REQUEST['gotomode'], FILTER_SANITIZE_NUMBER_INT) : 0;
$occIndex = array_key_exists('occindex', $_REQUEST) ? filter_var($_REQUEST['occindex'], FILTER_SANITIZE_NUMBER_INT) : false;
$crowdSourceMode = array_key_exists('csmode', $_REQUEST) ? filter_var($_REQUEST['csmode'], FILTER_SANITIZE_NUMBER_INT) : 0;

$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';
if(!$action && array_key_exists('carryover', $_REQUEST)) $goToMode = 2;

//Create Occurrence Manager
$occManager = null;
if(strpos($action,'Determination') || strpos($action,'Verification')){
	include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');
	$occManager = new OccurrenceEditorDeterminations();
}
elseif(strpos($action,'Image')){
	include_once($SERVER_ROOT.'/classes/OccurrenceEditorImages.php');
	$occManager = new OccurrenceEditorImages();
}
else{
	include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
	$occManager = new OccurrenceEditorManager();
}

if($crowdSourceMode){
	$occManager->setCrowdSourceMode(1);
}

$displayQuery = 0;
$isGenObs = 0;
$collMap = Array();
$collType = 'spec';
$occArr = array();
$imgArr = array();
$specImgArr = array();
$fragArr = array();
$qryCnt = false;
$statusStr = '';
$navStr = '';

$isEditor = 0;
$LOCALITY_AUTO_LOOKUP = 1;
$CATNUM_DUPE_CHECK = true;
$OTHER_CATNUM_DUPE_CHECK = true;
if($SYMB_UID){
	//Set variables
	$occManager->setOccId($occId);
	$occManager->setCollId($collId);
	$collMap = $occManager->getCollMap();
	if($collId && isset($collMap['collid']) && $collId != $collMap['collid']){
		$collId = $collMap['collid'];
		$occManager->setCollId($collId);
	}
	if($collMap){
		if($collMap['colltype']=='General Observations'){
			$isGenObs = 1;
			$collType = 'obs';
		}
		elseif($collMap['colltype']=='Observations'){
			$collType = 'obs';
		}
	}

	//Set default option variables, will rework later
	if($isGenObs){
		if(file_exists('includes/config/occurVarGenObs'.$SYMB_UID.'.php')){
			//Specific to particular collection
			include('includes/config/occurVarGenObs'.$SYMB_UID.'.php');
		}
		elseif(file_exists('includes/config/occurVarGenObsDefault.php')){
			//Specific to Default values for portal
			include('includes/config/occurVarGenObsDefault.php');
		}
	}
	else{
		if($collId && file_exists('includes/config/occurVarColl'.$collId.'.php')){
			//Specific to particular collection
			include('includes/config/occurVarColl'.$collId.'.php');
		}
		elseif(file_exists('includes/config/occurVarDefault.php')){
			//Specific to Default values for portal
			include('includes/config/occurVarDefault.php');
		}
		if($crowdSourceMode && file_exists('includes/config/crowdSourceVar.php')){
			//Specific to Crowdsourcing
			include('includes/config/crowdSourceVar.php');
		}
	}
	if(defined('LOCALITYAUTOLOOKUP') && !LOCALITYAUTOLOOKUP) $LOCALITY_AUTO_LOOKUP = LOCALITYAUTOLOOKUP;
	if(defined('CATNUMDUPECHECK') && !CATNUMDUPECHECK) $CATNUM_DUPE_CHECK = false;
	if(defined('OTHERCATNUMDUPECHECK') && !OTHERCATNUMDUPECHECK) $OTHER_CATNUM_DUPE_CHECK = false;

	//0 = not editor, 1 = admin, 2 = editor, 3 = taxon editor, 4 = crowdsource editor or collection allows public edits
	//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences
	if($IS_ADMIN || ($collId && array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin']))){
		$isEditor = 1;
	}
	else{
		if($isGenObs){
			if(!$occId && array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
				//Approved General Observation editors can add records
				$isEditor = 2;
			}
			elseif($action){
				//Lets assume that Edits where submitted and they remain on same specimen, user is still approved
				 $isEditor = 2;
			}
			elseif($occManager->getObserverUid() == $SYMB_UID){
				//Users can edit their own records
				$isEditor = 2;
			}
		}
		elseif(array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])){
			//Is an assigned editor for this collection
			$isEditor = 2;
		}
		elseif($crowdSourceMode && $occManager->isCrowdsourceEditor()){
			//Is a crowdsourcing editor (CS status is open (=0) or CS status is pending (=5) and active user was original editor
			$isEditor = 4;
		}
		elseif($collMap && $collMap['publicedits']){
			//Collection is set as allowing public edits
			$isEditor = 4;
		}
		elseif(array_key_exists('CollTaxon',$USER_RIGHTS) && $occId){
			//Check to see if this user is authorized to edit this occurrence given their taxonomic editing authority
			$isEditor = $occManager->isTaxonomicEditor();
		}
	}
	include_once 'editProcessor.php';
	if($action == 'saveOccurEdits'){
		$statusStr = $occManager->editOccurrence($_POST,$isEditor);
	}
	if($isEditor && $isEditor != 3){
		if($action == 'Save OCR'){
			$statusStr = $occManager->insertTextFragment($_POST['imgid'],$_POST['rawtext'],$_POST['rawnotes'],$_POST['rawsource']);
			if(is_numeric($statusStr)){
				$newPrlid = $statusStr;
				$statusStr = '';
			}
		}
		elseif($action == 'Save OCR Edits'){
			$statusStr = $occManager->saveTextFragment($_POST['editprlid'],$_POST['rawtext'],$_POST['rawnotes'],$_POST['rawsource']);
		}
		elseif($action == 'Delete OCR'){
			$statusStr = $occManager->deleteTextFragment($_POST['delprlid']);
		}
	}
	if($isEditor){
		//Available to full editors and taxon editors
		if($action == 'submitDetermination'){
			//Adding a new determination
			$statusStr = $occManager->addDetermination($_POST,$isEditor);
			$tabTarget = 1;
		}
		elseif($action == 'submitDeterminationEdit'){
			$statusStr = $occManager->editDetermination($_POST);
			$tabTarget = 1;
		}
		elseif($action == 'Delete Determination'){
			$statusStr = $occManager->deleteDetermination($_POST['detid']);
			$tabTarget = 1;
		}
		//Only full editors can perform following actions
		if($isEditor == 1 || $isEditor == 2){
			if($action == 'addOccurRecord'){
				if($occManager->addOccurrence($_POST)){
					$occManager->setQueryVariables();
					$qryCnt = $occManager->getQueryRecordCount();
					$qryCnt++;
					if($goToMode) $occIndex = $qryCnt;			//Go to new record
					else $occId = $occManager->getOccId();		//Stay on record and get $occId
				}
				else $statusStr = $occManager->getErrorStr();
			}
			elseif($action == 'Delete Occurrence'){
				if($occManager->deleteOccurrence($occId)){
					$occId = 0;
					$occManager->setOccId(0);
				}
				else $statusStr = $occManager->getErrorStr();
			}
			elseif($action == 'Transfer Record'){
				$transferCollid = $_POST['transfercollid'];
				if($transferCollid){
					if($occManager->transferOccurrence($occId,$transferCollid)){
						if(!isset($_POST['remainoncoll']) || !$_POST['remainoncoll']){
							$occManager->setCollId($transferCollid);
							$collId = $transferCollid;
							$collMap = $occManager->getCollMap();
						}
					}
					else{
						$statusStr = $occManager->getErrorStr();
					}
				}
			}
			elseif($action == 'cloneRecord'){
				$cloneArr = $occManager->cloneOccurrence($_POST);
				if($cloneArr){
					$statusStr = (isset($LANG['CLONES_CREATED'])?$LANG['CLONES_CREATED']:'Success! The following new clone record(s) have been created').' ';
					$statusStr .= '<div style="margin:5px 10px;color:black">';
					$statusStr .= '<div><a href="occurrenceeditor.php?occid=' . htmlspecialchars($occId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">#' . htmlspecialchars($occId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> - ' . htmlspecialchars((isset($LANG['CLONE_SOURCE'])?$LANG['CLONE_SOURCE']:'clone source'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</div>';
					$occId = current($cloneArr);
					$occManager->setOccId($occId);
					foreach($cloneArr as $cloneOccid){
						if($cloneOccid==$occId) $statusStr .= '<div>#'.$cloneOccid.' - '.(isset($LANG['THIS_RECORD'])?$LANG['THIS_RECORD']:'this record').'</div>';
						else $statusStr .= '<div><a href="occurrenceeditor.php?occid=' . htmlspecialchars($cloneOccid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">#' . htmlspecialchars($cloneOccid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></div>';
					}
					$statusStr .= '</div>';
					if(isset($_POST['targetcollid']) && $_POST['targetcollid'] && $_POST['targetcollid'] != $collId){
						$collId = $_POST['targetcollid'];
						$occManager->setCollId($collId);
						$collMap = $occManager->getCollMap();
					}
					$occManager->setQueryVariables(array('eb'=>$PARAMS_ARR['un'],'de'=>date('Y-m-d')));
					$qryCnt = $occManager->getQueryRecordCount();
					$occIndex = $qryCnt - count($cloneArr);
				}
			}
			elseif($action == 'Submit Image Edits'){
				$occManager->editImage($_POST);
				if($errArr = $occManager->getErrorArr()){
					if(isset($errArr['web'])){
						if(!$errArr['web']) $statusStr .= $LANG['ERROR_UPDATING_IMAGE'].': web image<br />';
					}
					if(isset($errArr['tn'])){
						if(!$errArr['tn']) $statusStr .= $LANG['ERROR_UPDATING_IMAGE'].': thumbnail<br />';
					}
					if(isset($errArr['orig'])){
						if(!$errArr['orig']) $statusStr .= $LANG['ERROR_UPDATING_IMAGE'].': large image<br />';
					}
					if(isset($errArr['error'])) $statusStr .= $LANG['ERROR_EDITING_IMAGE'].': '.$errArr['error'];
				}
				$tabTarget = 2;
			}
			elseif($action == 'Submit New Image'){
				if($occManager->addImage($_POST)){
					$statusStr = (isset($LANG['IMAGE_ADD_SUCCESS'])?$LANG['IMAGE_ADD_SUCCESS']:'Image added successfully');
					$tabTarget = 2;
				}
				if($occManager->getErrorStr()){
					$statusStr .= $occManager->getErrorStr();
				}
			}
			elseif($action == 'Delete Image'){
				$removeImg = (array_key_exists('removeimg',$_POST)?$_POST['removeimg']:0);
				if($occManager->deleteImage($_POST["imgid"], $removeImg)){
					$statusStr = (isset($LANG['IMAGE_DEL_SUCCESS'])?$LANG['IMAGE_DEL_SUCCESS']:'Image deleted successfully');
					$tabTarget = 2;
				}
				else{
					$statusStr = $occManager->getErrorStr();
				}
			}
			elseif($action == 'Remap Image'){
				if($occManager->remapImage($_POST['imgid'], $_POST['targetoccid'])){
					$statusStr = (isset($LANG['IMAGE_REMAP_SUCCESS'])?$LANG['IMAGE_REMAP_SUCCESS']:'SUCCESS: Image remapped to record').' <a href="occurrenceeditor.php?occid=' . htmlspecialchars($_POST["targetoccid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($_POST["targetoccid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
				}
				else{
					$statusStr = (isset($LANG['IMAGE_REMAP_ERROR'])?$LANG['IMAGE_REMAP_ERROR']:'ERROR linking image to new specimen').': '.$occManager->getErrorStr();
				}
			}
			elseif($action == 'remapImageToNewRecord'){
				$newOccid = $occManager->remapImage($_POST['imgid'], 'new');
				if($newOccid){
					$statusStr = (isset($LANG['IMAGE_REMAP_SUCCESS'])?$LANG['IMAGE_REMAP_SUCCESS']:'SUCCESS: Image remapped to record').' <a href="occurrenceeditor.php?occid=' . htmlspecialchars($newOccid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($newOccid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
				}
				else{
					$statusStr = (isset($LANG['NEW_IMAGE_ERROR'])?$LANG['NEW_IMAGE_ERROR']:'ERROR linking image to new blank specimen').': '.$occManager->getErrorStr();
				}
			}
			elseif($action == "Disassociate Image"){
				if($occManager->remapImage($_POST["imgid"])){
					$statusStr = (isset($LANG['DISASS_SUCCESS'])?$LANG['DISASS_SUCCESS']:'SUCCESS disassociating image').' <a href="../../imagelib/imgdetails.php?imgid=' . htmlspecialchars($_POST["imgid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">#' . htmlspecialchars($_POST["imgid"], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
				}
				else{
					$statusStr = (isset($LANG['DISASS_ERORR'])?$LANG['DISASS_ERORR']:'ERROR disassociating image').': '.$occManager->getErrorStr();
				}

			}
			elseif($action == "Apply Determination"){
				$makeCurrent = 0;
				if(array_key_exists('makecurrent',$_POST)) $makeCurrent = 1;
				$statusStr = $occManager->applyDetermination($_POST['detid'],$makeCurrent);
				$tabTarget = 1;
			}
			elseif($action == "Make Determination Current"){
				$statusStr = $occManager->makeDeterminationCurrent($_POST['detid']);
				$tabTarget = 1;
			}
			elseif($action == "Submit Verification Edits"){
				$statusStr = $occManager->editIdentificationRanking($_POST['confidenceranking'],$_POST['notes']);
				$tabTarget = 1;
			}
			elseif($action == 'linkChecklistVoucher'){
				$statusStr = $occManager->linkChecklistVoucher($_POST['clidvoucher'],$_POST['tidvoucher']);
			}
			elseif($action == 'deletevoucher'){
				$statusStr = $occManager->deleteChecklistVoucher($_REQUEST['delclid']);
			}
			elseif($action == 'editgeneticsubmit'){
				$statusStr = $occManager->editGeneticResource($_POST);
			}
			elseif($action == 'deletegeneticsubmit'){
				$statusStr = $occManager->deleteGeneticResource($_POST['genid']);
			}
			elseif($action == 'addgeneticsubmit'){
				$statusStr = $occManager->addGeneticResource($_POST);
			}
		}
	}

	if($goToMode){
		//Adding new record, override query form and prime for current user's dataentry for the day
		$occId = 0;
		$occManager->setQueryVariables(array('eb'=>$PARAMS_ARR['un'],'de'=>date('Y-m-d')));
		if(!$qryCnt){
			$qryCnt = $occManager->getQueryRecordCount();
			$occIndex = $qryCnt;
		}
	}
	if(is_numeric($occIndex)){
		//Query Form has been activated
		$occManager->setQueryVariables();
		if($action == 'Delete Occurrence'){
			//Reset query form index to one less, unless it's already 1, then just reset
			$qryCnt = $occManager->getQueryRecordCount();		//Value won't be returned unless set in cookies in previous query
			if($qryCnt > 1){
				if(($occIndex + 1) >= $qryCnt) $occIndex = $qryCnt - 2;
				$qryCnt--;
			}
			else{
				unset($_SESSION['editorquery']);
				$occIndex = false;
			}
		}
		elseif($action == 'saveOccurEdits'){
			//Get query count and then reset; don't use new count for this display
			$qryCnt = $occManager->getQueryRecordCount();
			$occManager->getQueryRecordCount(1);
		}
		else{
			$qryCnt = $occManager->getQueryRecordCount();
		}
	}
	elseif(isset($_SESSION['editorquery'])){
		//Make sure query variables are null
		unset($_SESSION['editorquery']);
	}
	$occManager->setOccIndex($occIndex);

	if($occId || (!$goToMode && $occIndex !== false)){
		$oArr = $occManager->getOccurMap();
		if($oArr){
			$occId = $occManager->getOccId();
			$occArr = $oArr[$occId];
			$occIndex = $occManager->getOccIndex();
			if(!$collMap){
				$collMap = $occManager->getCollMap();
				if(!$isEditor){
					if(isset($USER_RIGHTS['CollAdmin']) && in_array($collMap['collid'],$USER_RIGHTS['CollAdmin'])){
						$isEditor = 1;
					}
					elseif(isset($USER_RIGHTS['CollEditor']) && in_array($collMap['collid'],$USER_RIGHTS['CollEditor'])){
						$isEditor = 1;
					}
				}
			}
		}
	}
	elseif($goToMode == 2) $occArr = $occManager->carryOverValues($_REQUEST);
	if(!$isEditor && $crowdSourceMode && $occManager->isCrowdsourceEditor()) $isEditor = 4;

	if($qryCnt !== false){
		$navStr = '<b>';
		if($occIndex > 0) $navStr .= '<a href="#" onclick="return submitQueryForm(0);" title="'.(isset($LANG['FIRST_REC'])?$LANG['FIRST_REC']:'First Record').'">';
		$navStr .= '|&lt;';
		if($occIndex > 0) $navStr .= '</a>';
		$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		if($occIndex > 0) $navStr .= '<a href="#" onclick="return submitQueryForm(\'back\');" title="'.(isset($LANG['PREV_REC'])?$LANG['PREV_REC']:'Previous Record').'">';
		$navStr .= '&lt;&lt;';
		if($occIndex > 0) $navStr .= '</a>';
		$recIndex = ($occIndex<$qryCnt?($occIndex + 1):'*');
		$navStr .= '&nbsp;&nbsp;| '.$recIndex.' of '.$qryCnt.' |&nbsp;&nbsp;';
		if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm(\'forward\');"  title="'.(isset($LANG['NEXT_REC'])?$LANG['NEXT_REC']:'Next Record').'">';
		$navStr .= '&gt;&gt;';
		if($occIndex<$qryCnt-1) $navStr .= '</a>';
		$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
		if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm('.($qryCnt-1).');" title="'.(isset($LANG['LAST_REC'])?$LANG['LAST_REC']:'Last Record').'">';
		$navStr .= '&gt;|';
		if($occIndex<$qryCnt-1) $navStr .= '</a> ';
		if(!$crowdSourceMode){
			$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			$navStr .= '<a href="occurrenceeditor.php?gotomode=1&collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" onclick="return verifyLeaveForm()" title="' . htmlspecialchars((isset($LANG['NEW_REC'])?$LANG['NEW_REC']:'New Record'), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">&gt;*</a>';
		}
		$navStr .= '</b>';
	}

	//Images and other things needed for OCR
	$specImgArr = $occManager->getImageMap();
	if($specImgArr){
		$imgUrlPrefix = (isset($IMAGE_DOMAIN)?$IMAGE_DOMAIN:'');
		$imgCnt = 1;
		foreach($specImgArr as $imgId => $i2){
			$iUrl = $i2['url'];
			if($iUrl == 'empty' && $i2['origurl']) $iUrl = $i2['origurl'];
			if($imgUrlPrefix && substr($iUrl,0,4) != 'http') $iUrl = $imgUrlPrefix.$iUrl;
			$imgArr[$imgCnt]['imgid'] = $imgId;
			$imgArr[$imgCnt]['web'] = $iUrl;
			if($i2['origurl']){
				$lgUrl = $i2['origurl'];
				if($imgUrlPrefix && substr($lgUrl,0,4) != 'http') $lgUrl = $imgUrlPrefix.$lgUrl;
				$imgArr[$imgCnt]['lg'] = $lgUrl;
			}
			if(isset($i2['error'])) $imgArr[$imgCnt]['error'] = $i2['error'];
			$imgCnt++;
		}
		$fragArr = $occManager->getRawTextFragments();
	}

	$isLocked = false;
	if($occId) $isLocked = $occManager->getLock();

}
else{
	header('Location: ../../profile/index.php?refurl=../collections/editor/occurrenceeditor.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?= $CHARSET; ?>">
	<title><?= $DEFAULT_TITLE . ' ' . $LANG['OCCEDITOR'] ?></title>
	<link href="<?= $CSS_BASE_PATH ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<link href="<?= $CSS_BASE_PATH ?>/symbiota/variables.css" type="text/css" rel="stylesheet">
	<?php
	//include_once($SERVER_ROOT.'/includes/head.php');
    if($crowdSourceMode == 1){
		?>
		<link href="includes/config/occureditorcrowdsource.css?ver=5" type="text/css" rel="stylesheet" id="editorCssLink" />
		<?php
    }
    else{
		?>
		<link href="<?= $CSS_BASE_PATH ?>/symbiota/collections/editor/occurrenceeditor.css?ver=9" type="text/css" rel="stylesheet" id="editorCssLink" >
		<?php
		if(isset($CSSARR)){
			foreach($CSSARR as $cssVal){
				echo '<link href="includes/config/' . $cssVal . '?ver=170601" type="text/css" rel="stylesheet" />';
			}
		}
		if(isset($JSARR)){
			foreach($JSARR as $jsVal){
				echo '<script src="includes/config/'.$jsVal.'?ver=170601" type="text/javascript"></script>';
			}
		}
	}
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		var collId = "<?php echo (isset($collMap['collid'])?$collMap['collid']:(is_numeric($collId)?$collId:0)); ?>";
		var csMode = "<?php echo $crowdSourceMode; ?>";
		var tabTarget = <?php echo (is_numeric($tabTarget)?$tabTarget:'0'); ?>;
		var imgArr = [];
		var imgLgArr = [];
		var localityAutoLookup = <?php echo $LOCALITY_AUTO_LOOKUP; ?>;

		<?php
		if($imgArr){
			foreach($imgArr as $iCnt => $iArr){
				echo 'imgArr['.$iCnt.'] = "'.$iArr['web'].'";'."\n";
				if(isset($iArr['lg'])) echo 'imgLgArr['.$iCnt.'] = "'.$iArr['lg'].'";'."\n";
			}
		}
		?>

		$(document).ready(function() {
			<?php
			if($CATNUM_DUPE_CHECK) echo '$("#catalognumber").on("change", function(e) { searchCatalogNumber(this.form,true); });'."\n";
			if($OTHER_CATNUM_DUPE_CHECK) echo '$("input[name=\'idvalue[]\']").on("change", function(e) { searchOtherCatalogNumbers(this); });'."\n";
			?>
		});

		function requestImage(){
            $.ajax({
                type: "POST",
                url: 'rpc/makeactionrequest.php',
                data: { <?php echo ' occid: '.$occManager->getOccId(); ?>, requesttype: 'Image' },
                success: function( response ) {
                   $('div#imagerequestresult').html(response);
                }
            });
        }
	</script>
	<script src="../../js/symb/collections.coordinateValidation.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/wktpolygontools.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.georef.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/localitySuggest.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.main.js?ver=9" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.tools.js?ver=4" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.imgtools.js?ver=3" type="text/javascript"></script>
	<script src="../../js/jquery.imagetool-1.7.js?ver=140310" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.query.js?ver=6" type="text/javascript"></script>
	<style type="text/css">
		fieldset > legend{ font-weight:bold; }
		select{ margin-bottom: 2px; }
		#identifierDiv img{ width:10px; margin-left: 5px; }
		#innertext{ background-color: white; margin: 0px 10px; }
		.fieldGroupDiv {
			display: flex;
			align-items: center;
			gap: 0.75rem;
			margin-bottom: 1rem;
			input {
				margin: 0
			}
			a {
				display: flex;
			}
		}
		.fieldDiv{
			display: inline;
		}

		.editimg{ width: 15px; }

		.button-toggle {
			background-color: transparent;
			color: var(--body-text-color);
			border-radius: 5px;
			border: 2px solid var(--darkest-color);

			&.active {
				background-color: var(--darkest-color);
				color: white;
			}
			&:hover {
				background-color: var(--medium-color);
				border: 2px solid var(--medium-color);
				color: var(--light-color);
			}
		}
		#labelProcFieldset{
			padding:15px;
		}
	</style>
</head>
<body>
	<div role="main" id="innertext">
		<div id="titleDiv">
			<?php
			if($collMap) echo '<h1 class="page-heading" style="font-size: 1.5rem;"> Occurrence Editor: ' . $collMap['collectionname'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').') </h1>';
			?>
		</div>
		<?php
		if($isEditor && ($occId || ($collId && $isEditor < 3))){
			if(!$occArr && !$goToMode) $displayQuery = 1;
			if($statusStr){
				?>
				<div id="statusdiv" style="margin:5px 0px 5px 15px;">
					<b><?php echo (isset($LANG['ACTION_STATUS'])?$LANG['ACTION_STATUS']:'Action Status'); ?>: </b>
					<span style="color:<?php echo (stripos($statusStr,'ERROR')!==false?'red':'green'); ?>;"><?= $statusStr; ?></span>
					<?php
					if($action == 'Delete Occurrence'){
						?>
						<br/>
						<a href="#" style="margin:5px;" onclick="window.opener.location.href = window.opener.location.href;window.close();">
							<?php echo (isset($LANG['RETURN_TO_SEARCH'])?$LANG['RETURN_TO_SEARCH']:'Return to Search Page'); ?>
						</a>
						<?php
					}
					?>
				</div>
				<?php
			}
			if($occId && $isLocked){
				?>
				<div style="margin:25px;border:2px double;padding:20px;width:90%;">
					<div style="color:red;font-weight:bold;font-size:110%;">
						<?= $LANG['REC_LOCKED']; ?>
					</div>
					<div>
						<?= $LANG['LOCK_EXPLAIN']; ?>
					</div>
					<div style="margin:20px;font-weight:bold;">
						<a href="../individual/index.php?occid=<?= $occManager->getOccId() ?>" target="_blank"><?= (isset($LANG['READ_ONLY'])?$LANG['READ_ONLY']:'Read-only Display') ?></a>
					</div>
				</div>
				<?php
			}
			else{
				?>
				<table id="edittable">
					<tr><td id="editortd" valign="top">
						<div id="navDiv">
							<?php
							if($navStr){
								?>
								<div style="float:right;">
									<?php echo $navStr; ?>
								</div>
								<?php
							}
							?>
							<div class='navpath'>
								<a href="../../index.php" onclick="return verifyLeaveForm()"><?= (isset($LANG['HOME'])?$LANG['HOME']:'Home'); ?></a> &gt;&gt;
								<?php
								if($crowdSourceMode){
									?>
									<a href="../specprocessor/crowdsource/index.php"><?= (isset($LANG['CENTRAL_CROWD'])?$LANG['CENTRAL_CROWD']:'Crowd Source Central') ?></a> &gt;&gt;
									<?php
								}
								else{
									if($isGenObs){
										?>
										<a href="../../profile/viewprofile.php?tabindex=1" onclick="return verifyLeaveForm()"><?= (isset($LANG['PERS_MANAGEMENT'])?$LANG['PERS_MANAGEMENT']:'Personal Management') ?></a> &gt;&gt;
										<?php
									}
									else{
										if($isEditor == 1 || $isEditor == 2){
											?>
											<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1" onclick="return verifyLeaveForm()"><?= (isset($LANG['COL_MANAGEMENT'])?$LANG['COL_MANAGEMENT']:'Collection Management') ?></a> &gt;&gt;
											<?php
										}
									}
								}
								if($occId) echo '<a href="../individual/index.php?occid=' . $occManager->getOccId() . '">' . (isset($LANG['PUBLIC_DISPLAY'])?$LANG['PUBLIC_DISPLAY']:'Public Display') . '</a> &gt;&gt;';
								?>
								<b><?php if($isEditor == 3) echo $LANG['TAXONOMIC_EDITOR']; ?></b>
							</div>
						</div>
						<?php if($isEditor && $isEditor != 3):?>
						<div id="querySymbolDiv" style="margin:5px 5px 5px 0px;">
							<button class="button-toggle" type="button" onclick="toggleQueryForm(); toggleButtonVisuals(this, 'querydiv', [])">
								<?= $LANG['SEARCH_FILTER'] ?>
							</button>
						</div>
						<div style="margin-bottom:5px">
							<?php include 'includes/queryform.php'; ?>
						</div>
						<?php endif?>
						<?php if ($occArr || $goToMode == 1 || $goToMode == 2): ?>
						<div id="occedittabs" style="clear:both;">
							<ul>
								<li>
									<a href="#occdiv">
										<?php
										if($occId) echo (isset($LANG['OCC_DATA'])?$LANG['OCC_DATA']:'Occurrence Data');
										else echo '<span style="color:red;">'.(isset($LANG['NEW_OCC_RECORD'])?$LANG['NEW_OCC_RECORD']:'New Occurrence Record').'</span>';
										?>
									</a>
								</li>
								<?php
								if($occId && $isEditor){
									// Get symbiota user email as the annotator email (for fp)
									include_once($SERVER_ROOT . '/classes/ProfileManager.php');
									$pHandler = new ProfileManager();
									$pHandler->setUid($SYMB_UID);
									$person = $pHandler->getPerson();
									$userEmail = ($person?$person->getEmail():'');

									$anchorVars = 'occid='.$occManager->getOccId().'&occindex='.$occIndex.'&csmode='.$crowdSourceMode.'&collid='.$collId;
									$detVars = 'identby=' . urlencode($occArr['identifiedby'] ?? '') . '&dateident=' . urlencode($occArr['dateidentified'] ?? '') .
										'&sciname=' . urlencode($occArr['sciname'] ?? '') . '&em=' . $isEditor .
										'&annotatorname=' . urlencode($USER_DISPLAY_NAME ?? '') . '&annotatoremail=' . urlencode($userEmail ?? '') .
										(isset($collMap['collectioncode']) ? '&collectioncode=' . urlencode($collMap['collectioncode']) : '') .
										(isset($collMap['institutioncode']) ? '&institutioncode=' . urlencode($collMap['institutioncode']) : '') .
										'&catalognumber=' . urlencode($occArr['catalognumber'] ?? '');
									if($isEditor < 4){
										?>
										<li id="detTab">
											<a href="includes/determinationtab.php?<?= $anchorVars . '&' . $detVars ?>" style=""><?= $LANG['DET_HISTORY'] ?></a>
										</li>
										<?php
									}
									if($isEditor == 1 || $isEditor == 2){
										?>
										<li id="imgTab">
											<a href="includes/imagetab.php?<?= $anchorVars ?>" style=""><?= $LANG['IMAGES'] ?></a>
										</li>
										<?php
										if(isset($collMap['matSampleActivated'])){
											?>
											<li id="matSampleTab">
												<a href="includes/materialsampleinclude.php?<?= $anchorVars ?>"><?= $LANG['MATERIAL_SAMPLE'] ?></a>
											</li>
											<?php
										}
										?>
										<li id="resourceTab">
											<a href="includes/resourcetab.php?<?= $anchorVars ?>" style=""><?= $LANG['LINKED_RES'] ?></a>
										</li>
										<?php
										if($occManager->traitCodingActivated()){
											?>
											<li id="traitTab">
												<a href="includes/traittab.php?<?= $anchorVars ?>" style=""><?= $LANG['TRAITS'] ?></a>
											</li>
											<?php
										}
										?>
										<li id="adminTab">
											<a href="includes/admintab.php?<?= $anchorVars ?>" style=""><?= $LANG['ADMIN'] ?></a>
										</li>
										<?php
									}
								}
								?>
							</ul>
							<div id="occdiv">
								<form id="fullform" name="fullform" action="occurrenceeditor.php" method="post" onsubmit="return verifyFullForm(this);">
									<fieldset>
										<legend><?= $LANG['COLLECTOR_INFO'] ?></legend>
										<?php

										// If it's a new record in a general observation collection, get the person info to autofill name, country & state
										if($isGenObs && !$occId) {
											$pHandler = new ProfileManager();
											$pHandler->setUid($SYMB_UID);
											$user = $pHandler->getPerson();
											$occArr['recordedby'] = $user->getFirstName() . ' ' . $user->getLastName();

											// Don't add locality if carrying over locality information
											if($goToMode != 2) {
												$occArr['country'] = $user->getCountry();
												$occArr['stateprovince'] = $user->getState();
											}
										}

										if($occId){
											if($fragArr || $specImgArr){
												?>
												<div style="float:right;margin:-7px -4px 0px 0px;font-weight:bold;">
													<span id="imgProcOnSpan" style="display:block;">
														<a href="#" onclick="toggleImageTdOn();return false;">&gt;&gt;</a>
													</span>
													<span id="imgProcOffSpan" style="display:none;">
														<a href="#" onclick="toggleImageTdOff();return false;">&lt;&lt;</a>
													</span>
												</div>
												<?php
											}
											if($crowdSourceMode){
												?>
												<div style="float:right;margin:-7px 10px 0px 0px;font-weight:bold;">
													<span id="longtagspan" style="cursor:pointer;" onclick="toggleCsMode(0);return false;"><?php echo (isset($LANG['LONG_FORM'])?$LANG['LONG_FORM']:'Long Form'); ?></span>
													<span id="shorttagspan" style="cursor:pointer;display:none;" onclick="toggleCsMode(1);return false;"><?php echo (isset($LANG['SHORT_FORM'])?$LANG['SHORT_FORM']:'Short Form'); ?></span>
												</div>
												<?php
											}
										}
										?>
										<div style="clear:both;">
											<div id="catalogNumberDiv" class="field-div">
												<?php echo $LANG['CATALOG_NUMBER']; ?>
												<a href="#" onclick="return dwcDoc('catalogNumber')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="catalognumber" name="catalognumber" value="<?php echo array_key_exists('catalognumber',$occArr)?$occArr['catalognumber']:''; ?>" onchange="fieldChanged('catalognumber');" <?php if($isEditor > 2) echo 'disabled'; ?> autocomplete="off" />
											</div>
											<div id="otherCatalogNumbersDiv" class="field-div">
												<div id="identifierDiv" class="divTable">
													<div class="divTableHeading">
														<div class="divTableRow">
															<div class="divTableHead"><?php echo $LANG['IDENT_NAME']; ?> <a href="#" onclick="return dwcDoc('otherCatalogNumbers')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a></div>
															<div class="divTableHead"><?php echo $LANG['IDENT_VALUE']; ?></div>
														</div>
													</div>
													<div id="identifierBody" class="divTableBody">
														<?php
														if(isset($occArr['identifiers'])){
															foreach($occArr['identifiers'] as $idKey => $idArr){
																?>
																<div id="idRow-<?php echo $idKey; ?>" class="divTableRow">
																	<div class="divTableCell">
																		<input name="idkey[]" type="hidden" value="<?php echo $idKey; ?>" />
																		<input class="idNameInput" name="idname[]" type="text" value="<?php echo $idArr['name']; ?>" onchange="fieldChanged('idname');" autocomplete="off" />
																	</div>
																	<div class="divTableCell">
																		<input class="idValueInput" name="idvalue[]" type="text" value="<?php echo $idArr['value']; ?>" onchange="fieldChanged('idvalue');" autocomplete="off" /><a href="#" onclick="deleteIdentifier(<?php echo "'".$idKey."',".$occId; ?>);return false" tabindex="-1"><img src="../../images/del.png" /></a>
																	</div>
																</div>
																<?php
															}
														}
														?>
														<div class="divTableRow">
															<div class="divTableCell">
																<input name="idkey[]" type="hidden" value="newidentifier" />
																<input class="idNameInput" name="idname[]" type="text" value="" onchange="fieldChanged('idname');" autocomplete="off" />
															</div>
															<div class="divTableCell">
																<input class="idValueInput" name="idvalue[]" type="text" value="" onchange="fieldChanged('idvalue');" autocomplete="off" /><a href="#" onclick="addIdentifierField(this);return false" tabindex="-1"><img src="../../images/plus.png" style="width:1em;" /></a>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div style="clear:both;">
											<div id="recordedByDiv" class="field-div">
												<?php echo $LANG['RECORDED_BY']; ?>
												<a href="#" onclick="return dwcDoc('recordedBy')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="recordedby" maxlength="255" value="<?php echo array_key_exists('recordedby',$occArr)?$occArr['recordedby']:''; ?>" onchange="fieldChanged('recordedby');" />
											</div>
											<div id="recordNumberDiv" class="field-div">
												<?php echo $LANG['RECORD_NUMBER']; ?>
												<a href="#" onclick="return dwcDoc('recordNumber')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="recordnumber" maxlength="45" value="<?php echo array_key_exists('recordnumber',$occArr)?$occArr['recordnumber']:''; ?>" onchange="recordNumberChanged(this);" />
											</div>
											<div id="eventDateDiv" class="field-div" title="Earliest Date Collected">
												<?php echo $LANG['EVENT_DATE']; ?>
												<a href="#" onclick="return dwcDoc('eventDate')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="eventdate" value="<?php echo array_key_exists('eventdate',$occArr)?$occArr['eventdate']:''; ?>" onchange="eventDateChanged(this);" />
											</div>
											<div id="eventDate2Div" class="field-div" title="Latest Date Collected">
												<?= $LANG['EVENT_DATE2']; ?>
												<a href="#" onclick="return dwcDoc('eventDate2')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="eventdate2" value="<?= array_key_exists('eventdate2',$occArr)?$occArr['eventdate2']:''; ?>" onchange="eventDate2Changed(this);" >
											</div>
											<?php
											if($ACTIVATE_DUPLICATES){
												?>
												<div id="dupesDiv">
													<button type="button" value="Duplicates" onclick="searchDupes(this.form);" ><?php echo $LANG['DUPLICATES']; ?></button><br/>
													<input type="checkbox" name="autodupe" value="1" onchange="autoDupeChanged(this)" tabindex="-1" />
													<?php echo (isset($LANG['AUTO_SEARCH'])?$LANG['AUTO_SEARCH']:'Auto search'); ?>
												</div>
												<?php
											}
											?>
										</div>
										<div style="clear:both;">
											<div id="associatedCollectorsDiv" class="field-div">
												<div class="flabel">
													<?php echo $LANG['ASSOCIATED_COLLECTORS']; ?>
													<a href="#" onclick="return dwcDoc('associatedCollectors')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												</div>
												<input type="text" name="associatedcollectors" maxlength="255" value="<?php echo array_key_exists('associatedcollectors',$occArr)?$occArr['associatedcollectors']:''; ?>" onchange="fieldChanged('associatedcollectors');" />
											</div>
											<div id="verbatimEventDateDiv" class="field-div">
												<div class="flabel">
													<?php echo $LANG['VERBATIM_EVENT_DATE']; ?>
													<a href="#" onclick="return dwcDoc('verbatimEventDate')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												</div>
												<input type="text" name="verbatimeventdate" maxlength="255" value="<?php echo array_key_exists('verbatimeventdate',$occArr)?$occArr['verbatimeventdate']:''; ?>" onchange="verbatimEventDateChanged(this)" />
											</div>
											<?php
											if($loanArr = $occManager->getLoanData()){
												?>
												<fieldset style="float:right;margin:3px;padding:5px;border:1px solid red;">
													<legend style="color:red;"><?php echo (isset($LANG['OUT_ON_LOAN'])?$LANG['OUT_ON_LOAN']:'Out On Loan'); ?></legend>
													<b><?php echo (isset($LANG['TO'])?$LANG['TO']:'To'); ?>:</b> <a href="../loans/outgoing.php?tabindex=1&collid=<?php echo htmlspecialchars($occManager->getCollId(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&loanid=' . htmlspecialchars($loanArr['id'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>"><?php echo htmlspecialchars($loanArr['code'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a><br/>
													<b><?php echo (isset($LANG['DUE_DATE'])?$LANG['DUE_DATE']:'Due date'); ?>:</b> <?php echo (isset($loanArr['date'])?$loanArr['date']:(isset($LANG['NOT_DEFINED'])?$LANG['NOT_DEFINED']:'Not Defined')); ?>
												</fieldset>
												<?php
											}
											?>
											<div id="dupeMsgDiv">
												<div id="dupesearch"><?php echo (isset($LANG['SEARCHING_DUPE'])?$LANG['SEARCHING_DUPE']:'Searching for Duplicates'); ?>...</div>
												<div id="dupenone" style="display:none;color:red;"><?php echo (isset($LANG['NO_DUPES_FOUND'])?$LANG['NO_DUPES_FOUND']:'No Duplicates Found'); ?></div>
												<div id="dupedisplay" style="display:none;color:green;"><?php echo (isset($LANG['DISPLAY_DUPES'])?$LANG['DISPLAY_DUPES']:'Displaying Duplicates'); ?></div>
											</div>
										</div>
										<?php
										if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI){
											$exsArr = $occManager->getExsiccati();
											?>
											<div id="exsDiv">
												<div id="ometidDiv" class="field-div">
													<?php echo (isset($LANG['EXS_TITLE'])?$LANG['EXS_TITLE']:'Exsiccati Title'); ?><br/>
													<input id="exstitleinput" name="exstitle" value="<?php echo (isset($exsArr['exstitle'])?$exsArr['exstitle']:''); ?>" />
													<input id="ometidinput" name="ometid" type="text" style="display: none;" value="<?php echo (isset($exsArr['ometid'])?$exsArr['ometid']:''); ?>" onchange="fieldChanged('ometid')" />
												</div>
												<div id="exsnumberDiv" class="field-div">
													<?php echo (isset($LANG['NUMBER'])?$LANG['NUMBER']:'Number'); ?><br/>
													<input name="exsnumber" type="text" value="<?php echo isset($exsArr['exsnumber'])?$exsArr['exsnumber']:''; ?>" onchange="fieldChanged('exsnumber')" />
												</div>
											</div>
											<?php
										}
										?>
									</fieldset>
									<fieldset>
										<legend><?php echo (isset($LANG['LATEST_ID'])?$LANG['LATEST_ID']:'Latest Identification'); ?></legend>
										<div style="clear:both;">
											<div id="scinameDiv" class="field-div">
												<?php echo $LANG['SCINAME']; ?>
												<a href="#" onclick="return dwcDoc('scientificName')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="ffsciname" name="sciname" maxlength="250" value="<?php echo array_key_exists('sciname',$occArr)?$occArr['sciname']:''; ?>" onchange="fieldChanged('sciname');" <?php if($isEditor > 2) echo 'disabled'; ?> />
												<input type="hidden" id="tidinterpreted" name="tidinterpreted" value="<?php echo array_key_exists('tidinterpreted',$occArr)?$occArr['tidinterpreted']:''; ?>" />
												<?php
												if($isEditor == 3){
													echo '<div style="clear:both;color:red;margin-left:5px;">'.(isset($LANG['LIMITED_EDITING'])?$LANG['LIMITED_EDITING']:'Limited editing rights: use determination tab to edit identification').'</div>';
												}
												elseif($isEditor == 4){
													echo '<div style="clear:both;color:red;margin-left:5px;">'.(isset($LANG['NEED_FULL'])?$LANG['NEED_FULL']:'Note: Full editing permissions are needed to edit an identification').'</div>';
												}
												?>
											</div>
											<div id="scientificNameAuthorshipDiv">
												<?php echo $LANG['AUTHOR']; ?>
												<a href="#" onclick="return dwcDoc('scientificNameAuthorship')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" value="<?php echo array_key_exists('scientificnameauthorship',$occArr)?$occArr['scientificnameauthorship']:''; ?>" onchange="fieldChanged('scientificnameauthorship');" <?php if($isEditor > 2) echo 'disabled'; ?> />
											</div>
										</div>
										<div style="clear:both;padding:3px 0px 0px 10px;">
											<?php
											if(!$occId){
												echo '<div id="idRankDiv">';
												echo $LANG['IDENTIFICATION_CONFIDENCE'];
												echo ' <a href="#" onclick="return dwcDoc(\'idConfidence\')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a> ';
												echo '<select name="confidenceranking" onchange="fieldChanged(\'confidenceranking\')">';
												echo '<option value="">'.(isset($LANG['UNDEFINED'])?$LANG['UNDEFINED']:'Undefined').'</option>';
												$idRankArr = array(10 => 'Absolute', 9 => 'Very High', 8 => 'High', 7 => 'High - verification requested', 6 => 'Medium - insignificant material', 5 => 'Medium', 4 => 'Medium - verification requested',3 => 'Low - insignificant material', 2 => 'Low', 1 => 'Low - ID Requested', 0 => 'ID Requested');
												foreach($idRankArr as $rankKey => $rankText){
													echo '<option value="'.$rankKey.'">'.$rankKey.' - '.$rankText.'</option>';
												}
												echo '</select>';
												echo '</div>';
											}
											?>
											<div id="identificationQualifierDiv" class="field-div">
												<?php echo $LANG['ID_QUALIFIER']; ?>
												<a href="#" onclick="return dwcDoc('identificationQualifier')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" name="identificationqualifier" size="25" value="<?php echo array_key_exists('identificationqualifier',$occArr)?$occArr['identificationqualifier']:''; ?>" onchange="fieldChanged('identificationqualifier');" <?php if($isEditor > 2) echo 'disabled'; ?> />
											</div>
											<div  id="familyDiv">
												<?php echo $LANG['FAMILY']; ?>
												<a href="#" onclick="return dwcDoc('family')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" name="family" maxlength="50" tabindex="0" value="<?php echo array_key_exists('family',$occArr)?$occArr['family']:''; ?>" onchange="fieldChanged('family');" />
											</div>
										</div>
										<div style="clear:both;padding:3px 0px 0px 10px;">
											<div id="identifiedByDiv" class="field-div">
												<?php echo $LANG['IDENTIFIED_BY']; ?>
												<a href="#" onclick="return dwcDoc('identifiedBy')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" name="identifiedby" maxlength="255" value="<?php echo array_key_exists('identifiedby',$occArr)?$occArr['identifiedby']:''; ?>" onchange="fieldChanged('identifiedby');" />
											</div>
											<div id="dateIdentifiedDiv" class="field-div">
												<?php echo $LANG['DATE_IDENTIFIED']; ?>
												<a href="#" onclick="return dwcDoc('dateIdentified')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" name="dateidentified" maxlength="45" value="<?php echo array_key_exists('dateidentified',$occArr)?$occArr['dateidentified']:''; ?>" onchange="fieldChanged('dateidentified');" />
											</div>
											<div id="idrefToggleDiv" onclick="toggle('idrefdiv');" title="<?php echo $LANG['TOGG_ADD_FIELDS'] ?>">
												<img class="seemore" src="../../images/tochild.png" style="width:1.3em;height:1.3em">
											</div>
										</div>
										<div  id="idrefdiv">
											<div id="identificationReferencesDiv" class="field-div">
												<?php echo $LANG['ID_REFERENCES']; ?>:
												<a href="#" onclick="return dwcDoc('identificationReferences')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" name="identificationreferences" value="<?php echo array_key_exists('identificationreferences',$occArr)?$occArr['identificationreferences']:''; ?>" onchange="fieldChanged('identificationreferences');" />
											</div>
											<div id="identificationRemarksDiv" class="field-div">
												<?php echo $LANG['ID_REMARKS']; ?>:
												<a href="#" onclick="return dwcDoc('identificationRemarks')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" name="identificationremarks" value="<?php echo array_key_exists('identificationremarks',$occArr)?$occArr['identificationremarks']:''; ?>" onchange="fieldChanged('identificationremarks');" />
											</div>
											<div id="taxonRemarksDiv" class="field-div">
												<?php echo $LANG['TAXON_REMARKS']; ?>:
												<a href="#" onclick="return dwcDoc('taxonRemarks')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" name="taxonremarks" value="<?php echo array_key_exists('taxonremarks',$occArr)?$occArr['taxonremarks']:''; ?>" onchange="fieldChanged('taxonremarks');" />
											</div>
										</div>
									</fieldset>
									<fieldset>
										<?php
										$continent = array_key_exists('continent', $occArr) ? $occArr['continent'] : '';
										$waterBody = array_key_exists('waterbody', $occArr) ? $occArr['waterbody'] : '';
										$islandGroup = array_key_exists('islandgroup', $occArr) ? $occArr['islandgroup'] : '';
										$island = array_key_exists('island', $occArr) ? $occArr['island'] : '';
										$displayGeo1Div = false;
										if($continent || $waterBody || $islandGroup || $island){
											//If any field contain data, force display of geography1 div
											$displayGeo1Div = true;
										}
										?>
										<legend><?php echo $LANG['LOCALITY']; ?></legend>
										<div id="geography1-div" class="fieldGroup-div" style="<?= ($displayGeo1Div ? 'display:flex' : 'display:none') ?>">
											<div id="continentDiv" class="field-div">
												<?= $LANG['CONTINENT']; ?>
												<a href="#" onclick="return dwcDoc('continent')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" id="ffcontinent" name="continent" value="<?= $continent ?>" onchange="fieldChanged('continent');" autocomplete="off" />
											</div>
											<div id="waterBodyDiv" class="field-div">
												<?= $LANG['WATER_BODY']; ?>
												<a href="#" onclick="return dwcDoc('waterBody')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" id="ffwaterbody" name="waterbody" value="<?= $waterBody ?>" onchange="fieldChanged('waterbody');" autocomplete="off" />
											</div>
											<div id="islandGroupDiv" class="field-div">
												<?= $LANG['ISLAND_GROUP']; ?>
												<a href="#" onclick="return dwcDoc('islandGroup')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" id="ffislandgroup" name="islandgroup" value="<?= $islandGroup ?>" onchange="fieldChanged('islandgroup');" autocomplete="off" />
											</div>
											<div id="islandDiv" class="field-div">
												<?= $LANG['ISLAND']; ?>
												<a href="#" onclick="return dwcDoc('island')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<input type="text" id="ffisland" name="island" value="<?= $island ?>" onchange="fieldChanged('island');" autocomplete="off" />
											</div>
										</div>
										<div class="fieldGroup-div">
											<div id="countryDiv" class="field-div">
												<?php echo $LANG['COUNTRY']; ?>
												<a href="#" onclick="return dwcDoc('country')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="ffcountry" name="country" value="<?php echo array_key_exists('country',$occArr)?$occArr['country']:''; ?>" onchange="fieldChanged('country');" autocomplete="off" />
											</div>
											<div id="stateProvinceDiv" class="field-div">
												<?php echo $LANG['STATEPROVINCE']; ?>
												<a href="#" onclick="return dwcDoc('stateProvince')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="ffstate" name="stateprovince" value="<?php echo array_key_exists('stateprovince',$occArr)?$occArr['stateprovince']:''; ?>" onchange="stateProvinceChanged(this.value)" autocomplete="off" />
											</div>
											<div id="countyDiv" class="field-div">
												<?php echo $LANG['COUNTY']; ?>
												<a href="#" onclick="return dwcDoc('county')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="ffcounty" name="county" value="<?php echo array_key_exists('county',$occArr)?$occArr['county']:''; ?>" onchange="fieldChanged('county');" autocomplete="off" />
											</div>
											<div id="municipalityDiv" class="field-div">
												<?php echo $LANG['MUNICIPALITY']; ?>
												<a href="#" onclick="return dwcDoc('municipality')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="ffmunicipality" name="municipality" value="<?php echo array_key_exists('municipality',$occArr)?$occArr['municipality']:''; ?>" onchange="fieldChanged('municipality');" autocomplete="off" />
											</div>
											<div id="locationIdDiv" class="field-div">
												<?php echo $LANG['LOCATION_ID']; ?>
												<a href="#" onclick="return dwcDoc('locationID')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="locationid" name="locationid" value="<?php echo array_key_exists('locationid',$occArr)?$occArr['locationid']:''; ?>" onchange="fieldChanged('locationid');" autocomplete="off" />
												<a id="geography1Toggle" onclick="toggle('geography1-div', 'flex');" title="<?php echo $LANG['TOGG_ADD_FIELDS'] ?>">
													<img class="seemore" src="../../images/toparent.png" style="width:1.3em;height:1.3em">
												</a>
											</div>
										</div>
										<div id="localityDiv" class="fieldGroup-div">
											<?php echo $LANG['LOCALITY']; ?>
											<a href="#" onclick="return dwcDoc('locality')" tabindex="-1"><img class="docimg" src="../../images/qmark.png"></a>
											<br />
											<textarea id="fflocality" name="locality" onchange="fieldChanged('locality');"><?php echo array_key_exists('locality',$occArr)?$occArr['locality']:''; ?></textarea>
											<a id="localityExtraToggle" onclick="toggle('localityExtraDiv');" title="<?php echo $LANG['TOGG_ADD_FIELDS'] ?>">
												<img class="seemore" src="../../images/tochild.png" style="width:1.3em" />
											</a>
										</div>
										<?php
										$localityExtraDiv = 'none';
										if(array_key_exists("locationremarks",$occArr) && $occArr["locationremarks"]) $localityExtraDiv = "block";
										?>
										<div id="localityExtraDiv"  class="fieldGroup-div" style="display:<?php echo $localityExtraDiv; ?>">
											<div id="locationRemarksDiv">
												<?php echo $LANG['LOCATION_REMARKS']; ?>
												<a href="#" onclick="return dwcDoc('locationRemarks')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="locationremarks" name="locationremarks" value="<?php echo array_key_exists('locationremarks',$occArr)?$occArr['locationremarks']:''; ?>" onchange="fieldChanged('locationremarks');" />
											</div>
										</div>
										<?php
										if($LOCALITY_AUTO_LOOKUP){
											echo '<div id="localAutoDeactivatedDiv">';
											echo '<input name="localautodeactivated" type="checkbox" value="1" onchange="localAutoChanged(this)" ' . ($LOCALITY_AUTO_LOOKUP == 2? 'checked' : '') . ' tabindex="-1" > ';
											echo (isset($LANG['DEACTIVATE_LOOKUP'])?$LANG['DEACTIVATE_LOOKUP']:'Deactivate Locality Lookup').'</div>';
										}
										?>
										<div id="localSecurityDiv">
											<div style="float:left;">
												<?php
												echo $LANG['LOCALITY_SECURITY'];
												$securityCode = array_key_exists('localitysecurity',$occArr)&&$occArr['localitysecurity']?$occArr['localitysecurity']:0;
												$lsrValue = array_key_exists('localitysecurityreason',$occArr)?$occArr['localitysecurityreason']:'';
												?>:
												<select name="localitysecurity" onchange="securityChangedByUser(this.form);" title="<?php echo (isset($LANG['SECURITY_SETTINGS'])?$LANG['SECURITY_SETTINGS']:'Security Settings'); ?>" tabindex="-1">
													<option value="0"><?= $LANG['SECURITY_NOT_APPLIED'] ?></option>
													<option value="1" ' <?= ($securityCode ? 'SELECTED' : '') ?>><?= $LANG['SECURITY_APPLIED'] ?></option>
												</select>
												<a href="#" onclick="return dwcDoc('localitySecurity')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
											</div>
											<div id="locsecreason" style="margin-left:5px;border:2px solid gray;float:left;display:<?php echo ($lsrValue||$securityCode?'inline':'none') ?>;padding:3px">
												<div ><input name="lockLocalitySecurity" type="checkbox" onchange="securityLockChanged(this)" tabindex="-1" <?php echo ($lsrValue?'checked':'') ?> /> <?php echo (isset($LANG['LOCK_SEC_SETTING'])?$LANG['LOCK_SEC_SETTING']:'Lock Security Setting'); ?></div>
												<?= $LANG['LOCALITY_SECURITY_REASON'] ?>:
												<input type="text" name="localitysecurityreason" tabindex="-1" onchange="localitySecurityReasonChanged();" value="<?php echo $lsrValue; ?>" title="<?php echo (isset($LANG['EXPLAIN_SEC_STATUS'])?$LANG['EXPLAIN_SEC_STATUS']:'Entering any text will lock security status on or off; leave blank to accept default security status'); ?>" />
											</div>
										</div>
										<div style="clear:both;" class="fieldGroup-div">
											<div id="decimalLatitudeDiv" class="field-div">
												<?php echo $LANG['DECIMAL_LATITUDE']; ?>
												<br/>
												<?php
												$latValue = '';
												if(array_key_exists('decimallatitude', $occArr) && $occArr['decimallatitude'] != '') {
													$latValue = $occArr['decimallatitude'];
												}
												?>
												<input type="text" id="decimallatitude" name="decimallatitude" maxlength="15" value="<?php echo $latValue; ?>" onchange="decimalLatitudeChanged(this.form)" />
											</div>
											<div id="decimalLongitudeDiv" class="field-div">
												<?php echo $LANG['DECIMAL_LONGITUDE']; ?>
												<br/>
												<?php
												$longValue = "";
												if(array_key_exists("decimallongitude",$occArr) && $occArr["decimallongitude"] != "") {
													$longValue = $occArr["decimallongitude"];
												}
												?>
												<input type="text" id="decimallongitude" name="decimallongitude" maxlength="15" value="<?php echo $longValue; ?>" onchange="decimalLongitudeChanged(this.form);" />
											</div>
											<div id="coordinateUncertaintyInMetersDiv" class="field-div">
												<?php echo $LANG['COORDINATE_UNCERTAINITY_IN_METERS']; ?>
												<a href="#" onclick="return dwcDoc('coordinateUncertaintyInMeters')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" maxlength="10" value="<?php echo array_key_exists('coordinateuncertaintyinmeters',$occArr)?$occArr['coordinateuncertaintyinmeters']:''; ?>" onchange="coordinateUncertaintyInMetersChanged(this.form);" title="<?php echo (isset($LANG['UNCERTAINTY_METERS'])?$LANG['UNCERTAINTY_METERS']:'Uncertainty in Meters'); ?>" />
											</div>
											<div id="mapIconDiv" onclick="openMappingAid();" title="<?= $LANG['MAP_COORDS'] ?>">
												<img src="../../images/world.png" style="width:1.2em;" />
											</div>
											<div id="geoLocateDiv" title="<?php echo (isset($LANG['GEOLOCATE_LOC'])?$LANG['GEOLOCATE_LOC']:'GeoLocate Locality'); ?>">
												<a href="#" onclick="geoLocateLocality();" tabindex="-1"><img src="../../images/geolocate.png" style="width:1.2em;" /></a>
											</div>
											<div id="coordCloningDiv" title="<?php echo (isset($LANG['COORD_CLONE_TOOL'])?$LANG['COORD_CLONE_TOOL']:'Coordinate Cloning Tool'); ?>" >
												<button type="button" value="C" tabindex="-1" onclick="geoCloneTool()" ><?php echo (isset($LANG['C'])?$LANG['C']:'C') ?></button>
											</div>
											<div id="geoToolsDiv" title="<?php echo (isset($LANG['CONVERSION_TOOLS'])?$LANG['CONVERSION_TOOLS']:'Tools for converting additional formats'); ?>" >
												<button type="button" value="F" tabindex="-1" onclick="toggleCoordDiv()" ><?php echo (isset($LANG['F'])?$LANG['F']:'F') ?></button>
											</div>
											<div id="geodeticDatumDiv" class="field-div">
												<?php echo $LANG['GEODETIC_DATUM']; ?>
												<a href="#" onclick="return dwcDoc('geodeticDatum')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" id="geodeticdatum" name="geodeticdatum" maxlength="255" value="<?php echo array_key_exists('geodeticdatum',$occArr)?$occArr['geodeticdatum']:''; ?>" onchange="fieldChanged('geodeticdatum');" />
											</div>
											<div id="verbatimCoordinatesDiv" class="field-div">
												<div style="float:left;margin:18px 2px 0px 2px" title="<?php echo (isset($LANG['RECALCULATE_COORDS'])?$LANG['RECALCULATE_COORDS']:'Recalculate Decimal Coordinates'); ?>">
													<a href="#" onclick="parseVerbatimCoordinates(document.fullform,1);return false" tabindex="-1">&lt;&lt;</a>
												</div>
												<div style="float:left;">
													<?php echo $LANG['VERBATIM_COORDINATES']; ?>
													<a href="#" onclick="return dwcDoc('verbatimCoordinates')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="verbatimcoordinates" maxlength="255" value="<?php echo array_key_exists('verbatimcoordinates',$occArr)?$occArr['verbatimcoordinates']:''; ?>" onchange="verbatimCoordinatesChanged(this.form);" title="" />
												</div>
											</div>
										</div>
										<div style="clear:both;">
											<div id="elevationDiv" class="field-div">
												<?php echo $LANG['ELEVATION_IN_METERS']; ?>
												<a href="#" onclick="return dwcDoc('minimumElevationInMeters')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="minimumelevationinmeters" maxlength="6" value="<?php echo array_key_exists('minimumelevationinmeters',$occArr)?$occArr['minimumelevationinmeters']:''; ?>" onchange="minimumElevationInMetersChanged(this.form);" title="<?php echo (isset($LANG['MIN_ELEVATION'])?$LANG['MIN_ELEVATION']:'Minimum Elevation in Meters'); ?>" /> -
												<input type="text" name="maximumelevationinmeters" maxlength="6" value="<?php echo array_key_exists('maximumelevationinmeters',$occArr)?$occArr['maximumelevationinmeters']:''; ?>" onchange="maximumElevationInMetersChanged(this.form);" title="<?php echo (isset($LANG['MAX_ELEVATION'])?$LANG['MAX_ELEVATION']:'Maximum Elevation in Meters'); ?>" />
											</div>
											<div id="verbatimElevationDiv" class="field-div">
												<div style="float:left;margin:18px 2px 0px 2px" title="<?php echo (isset($LANG['RECALCULATE_ELEV'])?$LANG['RECALCULATE_ELEV']:'Recalculate Elevation in Meters'); ?>">
													<a href="#" onclick="parseVerbatimElevation(document.fullform);return false" tabindex="-1">&lt;&lt;</a>
												</div>
												<div style="float:left;">
													<?php echo $LANG['VERBATIM_ELEVATION']; ?>
													<a href="#" onclick="return dwcDoc('verbatimElevation')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="verbatimelevation" maxlength="255" value="<?php echo array_key_exists('verbatimelevation',$occArr)?$occArr['verbatimelevation']:''; ?>" onchange="verbatimElevationChanged(this.form);" />
												</div>
											</div>
											<div id="depthDiv" class="field-div">
												<?php echo $LANG['DEPTH_IN_METERS']; ?>
												<a href="#" onclick="return dwcDoc('minimumDepthInMeters')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="minimumdepthinmeters" maxlength="6" value="<?php echo array_key_exists('minimumdepthinmeters',$occArr)?$occArr['minimumdepthinmeters']:''; ?>" onchange="minimumDepthInMetersChanged(this.form);" title="<?php echo $LANG['MIN_DEPTH']; ?>" /> -
												<input type="text" name="maximumdepthinmeters" maxlength="6" value="<?php echo array_key_exists('maximumdepthinmeters',$occArr)?$occArr['maximumdepthinmeters']:''; ?>" onchange="maximumDepthInMetersChanged(this.form);" title="<?php echo $LANG['MAX_DEPTH']; ?>" />
											</div>
											<div id="verbatimDepthDiv" class="field-div">
												<div style="float:left;">
													<?php echo $LANG['VERBATIM_DEPTH']; ?>
													<a href="#" onclick="return dwcDoc('verbatimDepth')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="verbatimdepth" maxlength="255" value="<?php echo array_key_exists('verbatimdepth',$occArr)?$occArr['verbatimdepth']:''; ?>" onchange="fieldChanged('verbatimdepth');" />
												</div>
											</div>
											<div id="georefExtraToggleDiv" onclick="toggle('georefExtraDiv');">
												<img class="seemore" src="../../images/tochild.png" style="width:1.3em;height:1.3em" title="<?php echo $LANG['TOGG_ADD_FIELDS'] ?>" >
											</div>
										</div>
										<?php
										include_once('includes/geotools.php');
										$georefExtraDiv = 'display:';
										if(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]){
											$georefExtraDiv .= "block";
										}
										elseif(array_key_exists("footprintwkt",$occArr) && $occArr["footprintwkt"]){
											$georefExtraDiv .= "block";
										}
										elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]){
											$georefExtraDiv .= "block";
										}
										elseif(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]){
											$georefExtraDiv .= "block";
										}
										elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]){
											$georefExtraDiv .= "block";
										}
										elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]){
											$georefExtraDiv .= "block";
										}
										?>
										<div id="georefExtraDiv" style="<?php echo $georefExtraDiv; ?>;">
											<div style="clear:both;">
												<div id="georeferencedByDiv" class="field-div">
													<?php echo $LANG['GEOREFERENCED_BY']; ?>
													<br/>
													<input type="text" name="georeferencedby" maxlength="255" value="<?php echo array_key_exists('georeferencedby',$occArr)?$occArr['georeferencedby']:''; ?>" onchange="fieldChanged('georeferencedby');" />
												</div>
												<div id="georeferenceSourcesDiv" class="field-div">
													<?php echo $LANG['GEOREFERENCE_SOURCES']; ?>
													<a href="#" onclick="return dwcDoc('georeferenceSources')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="georeferencesources" maxlength="255" value="<?php echo array_key_exists('georeferencesources',$occArr)?$occArr['georeferencesources']:''; ?>" onchange="fieldChanged('georeferencesources');" />
												</div>
												<div id="georeferenceRemarksDiv" class="field-div">
													<?php echo $LANG['GEOREFERENCE_REMARKS']; ?>
													<br/>
													<input type="text" name="georeferenceremarks" maxlength="255" value="<?php echo array_key_exists('georeferenceremarks',$occArr)?$occArr['georeferenceremarks']:''; ?>" onchange="fieldChanged('georeferenceremarks');" />
												</div>
											</div>
											<div style="clear:both;">
												<div id="georeferenceProtocolDiv" class="field-div">
													<?php echo $LANG['GEOREFERENCE_PROTOCOL']; ?>
													<a href="#" onclick="return dwcDoc('georeferenceProtocol')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="georeferenceprotocol" maxlength="255" value="<?php echo array_key_exists('georeferenceprotocol',$occArr)?$occArr['georeferenceprotocol']:''; ?>" onchange="fieldChanged('georeferenceprotocol');" />
												</div>
												<div id="georeferenceVerificationStatusDiv" class="field-div">
													<?php echo $LANG['GEOREFERENCE_VERIFICATION_STATUS']; ?>
													<a href="#" onclick="return dwcDoc('georeferenceVerificationStatus')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="georeferenceverificationstatus" maxlength="32" value="<?php echo array_key_exists('georeferenceverificationstatus',$occArr)?$occArr['georeferenceverificationstatus']:''; ?>" onchange="fieldChanged('georeferenceverificationstatus');" />
												</div>
												<div id="footprintWktDiv" class="field-div">
													<?php echo $LANG['FOOTPRINT_WKT']; ?>
													<br/>
													<div id="mapPolyAidDiv" style="float:right;margin-top:-2px;margin-left:2px;" onclick="openMappingPolyAid();" title="">
														<img src="../../images/world.png" style="width:14px;" >
													</div>
													<textarea name="footprintwkt" id="footprintwkt" onchange="footPrintWktChanged(this)" style="height:40px;resize:vertical;" ><?php echo array_key_exists('footprintwkt',$occArr)?$occArr['footprintwkt']:''; ?></textarea>
												</div>
											</div>
										</div>
									</fieldset>
									<?php
									if(isset($collMap['paleoActivated'])) include('includes/paleoinclude.php');
									?>
									<fieldset>
										<legend><?php echo $LANG['MISC']; ?></legend>
										<div id="habitatDiv" class="field-div">
											<?php echo $LANG['HABITAT']; ?>
											<a href="#" onclick="return dwcDoc('habitat')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
											<br/>
											<input type="text" name="habitat" value="<?php echo array_key_exists('habitat',$occArr)?$occArr['habitat']:''; ?>" onchange="fieldChanged('habitat');" />
										</div>
										<div id="substrateDiv" class="field-div">
											<?php echo $LANG['SUBSTRATE']; ?>
											<a href="#" onclick="return dwcDoc('substrate')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
											<br/>
											<input type="text" name="substrate" maxlength="500" value="<?php echo array_key_exists('substrate',$occArr)?$occArr['substrate']:''; ?>" onchange="fieldChanged('substrate');" />
										</div>
										<?php
										if(!empty($QUICK_HOST_ENTRY_IS_ACTIVE)) { // Quick host field
											$quickHostArr = $occManager->getQuickHost();
											?>
											<div id="hostDiv" class="field-div">
												<?php echo $LANG['HOST']; ?><br/>
												<input type="text" name="host" id="quickhost" maxlength="500" value="<?php echo ($quickHostArr?$quickHostArr['verbatimsciname']:''); ?>" onchange="fieldChanged('host');" />
												<input type="hidden" name="hostassocid" value="<?php echo ($quickHostArr?$quickHostArr['associd']:''); ?>" />
											</div>
											<?php
										}
										?>
										<div id="associatedTaxaDiv" class="field-div">
											<?php echo $LANG['ASSOCIATED_TAXA']; ?>
											<a href="#" onclick="return dwcDoc('associatedTaxa')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
											<br/>
											<textarea name="associatedtaxa" onchange="fieldChanged('associatedtaxa');"><?php echo array_key_exists('associatedtaxa',$occArr)?$occArr['associatedtaxa']:''; ?></textarea>
											<?php
											if(!isset($ACTIVATEASSOCTAXAAID) || $ACTIVATEASSOCTAXAAID){
												echo '<a href="#" onclick="openAssocSppAid();return false;"><img class="editimg" src="../../images/list.png"></a>';
											}
											?>
										</div>
										<div id="verbatimAttributesDiv" class="field-div">
											<?php echo $LANG['VERBATIM_ATTRIBUTES']; ?>
											<a href="#" onclick="return dwcDoc('verbatimAttributes')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
											<br/>
											<input type="text" name="verbatimattributes" value="<?php echo array_key_exists('verbatimattributes',$occArr)?$occArr['verbatimattributes']:''; ?>" onchange="fieldChanged('verbatimattributes');" />
										</div>
										<div id="occurrenceRemarksDiv" class="field-div">
											<?php echo $LANG['OCCURRENCE_REMARKS']; ?>
											<a href="#" onclick="return dwcDoc('occurrenceRemarks')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a>
											<br/>
											<input type="text" name="occurrenceremarks" value="<?php echo array_key_exists('occurrenceremarks',$occArr)?$occArr['occurrenceremarks']:''; ?>" onchange="fieldChanged('occurrenceremarks');" title="<?php echo $LANG['OCC_REMARKS']; ?>" />
											<span id="dynPropToggleSpan" onclick="toggle('dynamicPropertiesDiv');" title="<?php echo $LANG['TOGG_ADD_FIELDS'] ?>" >
												<img class="seemore" src="../../images/tochild.png" style="width:1.3em;height:1.3em">
											</span>
										</div>
										<div id="dynamicPropertiesDiv" class="field-div" style="display:<?= empty($occArr['dynamicproperties']) ? 'none' : '' ?>">
											<?php echo $LANG['DYNAMIC_PROPERTIES']; ?>
											<a href="#" onclick="return dwcDoc('dynamicProperties')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
											<input type="text" name="dynamicproperties" value="<?php echo array_key_exists('dynamicproperties',$occArr)?$occArr['dynamicproperties']:''; ?>" onchange="fieldChanged('dynamicproperties');" />
										</div>
										<div style="padding:2px;">
											<div id="lifeStageDiv" class="field-div">
												<?php echo $LANG['LIFE_STAGE']; ?>
												<a href="#" onclick="return dwcDoc('lifeStage')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="lifestage" maxlength="45" value="<?php echo array_key_exists('lifestage',$occArr)?$occArr['lifestage']:''; ?>" onchange="fieldChanged('lifestage');" />
											</div>
											<div id="sexDiv" class="field-div">
												<?php echo $LANG['SEX']; ?>
												<a href="#" onclick="return dwcDoc('sex')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="sex" maxlength="45" value="<?php echo array_key_exists('sex',$occArr)?$occArr['sex']:''; ?>" onchange="fieldChanged('sex');" />
											</div>
											<div id="individualCountDiv" class="field-div">
												<?php echo $LANG['INDIVIDUAL_COUNT']; ?>
												<a href="#" onclick="return dwcDoc('individualCount')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="individualcount" maxlength="45" value="<?php echo array_key_exists('individualcount',$occArr)?$occArr['individualcount']:''; ?>" onchange="fieldChanged('individualcount');" />
											</div>
											<div id="samplingProtocolDiv" class="field-div">
												<?php echo $LANG['SAMPLING_PROTOCOL']; ?>
												<a href="#" onclick="return dwcDoc('samplingProtocol')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="samplingprotocol" maxlength="100" value="<?php echo array_key_exists('samplingprotocol',$occArr)?$occArr['samplingprotocol']:''; ?>" onchange="fieldChanged('samplingprotocol');" />
											</div>
											<div id="preparationsDiv" class="field-div">
												<?php echo $LANG['PREPARATIONS']; ?>
												<a href="#" onclick="return dwcDoc('preparations')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="preparations" maxlength="100" value="<?php echo array_key_exists('preparations',$occArr)?$occArr['preparations']:''; ?>" onchange="fieldChanged('preparations');" />
											</div>
											<div id="reproductiveConditionDiv" class="field-div">
												<?php echo $LANG['REPRODUCTIVE_CONDITION']; ?>
												<a href="#" onclick="return dwcDoc('reproductiveCondition')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<?php
												if(!empty($REPRODUCTIVE_CONDITION_TERMS)){
													?>
													<select name="reproductivecondition" onchange="fieldChanged('reproductivecondition');">
														<option value="">-----------------</option>
														<?php
														foreach($REPRODUCTIVE_CONDITION_TERMS as $term){
															echo '<option value="'.$term.'" '.(isset($occArr['reproductivecondition']) && $term==$occArr['reproductivecondition']?'SELECTED':'').'>'.$term.'</option>';
														}
														?>
													</select>
													<?php
												}
												else echo '<input type="text" name="reproductivecondition" maxlength="255" value="'.(array_key_exists('reproductivecondition',$occArr)?$occArr['reproductivecondition']:'').'" onchange="fieldChanged(\'reproductivecondition\');" />';
												?>
											</div>
											<div id="behaviorDiv" class="field-div">
												<?= $LANG['BEHAVIOR']; ?>
												<a href="#" onclick="return dwcDoc('behavior')" tabindex="-1"><img class="docimg" src="../../images/qmark.png"></a>
												<br/>
												<input type="text" id="ffbehavior" name="behavior" value="<?= array_key_exists('behavior',$occArr)?$occArr['behavior']:''; ?>" onchange="fieldChanged('behavior');" autocomplete="off" />
											</div>
											<div id="vitalityDiv" class="field-div">
												<?= $LANG['VITALITY']; ?>
												<a href="#" onclick="return dwcDoc('vitality')" tabindex="-1"><img class="docimg" src="../../images/qmark.png"></a>
												<br/>
												<input type="text" id="ffvitality" name="vitality" value="<?= array_key_exists('vitality',$occArr)?$occArr['vitality']:''; ?>" onchange="fieldChanged('vitality');" autocomplete="off" />
											</div>
											<div id="establishmentMeansDiv" class="field-div">
												<?php echo $LANG['ESTABLISHMENT_MEANS']; ?>
												<a href="#" onclick="return dwcDoc('establishmentMeans')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="establishmentmeans" maxlength="32" value="<?php echo array_key_exists('establishmentmeans',$occArr)?$occArr['establishmentmeans']:''; ?>" onchange="fieldChanged('establishmentmeans');" />
											</div>
											<div>
											<div id="cultivationStatusDiv" class="field-div">
												<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]?1:0; ?>
												<input type="checkbox" name="cultivationstatus" value="1" <?php echo $hasValue?'CHECKED':''; ?> onchange="fieldChanged('cultivationstatus');" />
												<?php echo $LANG['CULTIVATION_STATUS']; ?>
											</div>
											</div>
										</div>
									</fieldset>
									<fieldset>
										<legend><?php echo $LANG['CURATION']; ?></legend>
										<div style="padding:3px;clear:both;">
											<div id="typeStatusDiv" class="field-div">
												<?php echo $LANG['TYPE_STATUS']; ?>
												<a href="#" onclick="return dwcDoc('typeStatus')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="typestatus" maxlength="255" value="<?php echo array_key_exists('typestatus',$occArr)?$occArr['typestatus']:''; ?>" onchange="fieldChanged('typestatus');" />
											</div>
											<div id="dispositionDiv" class="field-div">
												<?php echo $LANG['DISPOSITION']; ?>
												<a href="#" onclick="return dwcDoc('disposition')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="disposition" value="<?php echo array_key_exists('disposition',$occArr)?$occArr['disposition']:''; ?>" onchange="fieldChanged('disposition');" />
											</div>
											<div id="occurrenceIdDiv" class="field-div" title="If different than institution code">
												<?php echo $LANG['OCCURRENCE_ID']; ?>
												<a href="#" onclick="return dwcDoc('occurrenceid')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="occurrenceid" maxlength="255" value="<?php echo array_key_exists('occurrenceid',$occArr)?$occArr['occurrenceid']:''; ?>" onchange="fieldChanged('occurrenceid');" />
											</div>
											<div id="fieldNumberDiv" class="field-div" title="An identifier given to the collecting event in the field">
												<?php echo $LANG['FIELD_NUMBER']; ?>
												<a href="#" onclick="return dwcDoc('fieldnumber')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="fieldnumber" maxlength="45" value="<?php echo array_key_exists('fieldnumber',$occArr)?$occArr['fieldnumber']:''; ?>" onchange="fieldChanged('fieldnumber');" />
											</div>
											<div id="languageDiv" class="field-div">
												<?php echo $LANG['LANGUAGE']; ?>
												<a href="#" onclick="return dwcDoc('language')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="language" maxlength="20" value="<?php echo array_key_exists('language',$occArr)?$occArr['language']:''; ?>" onchange="fieldChanged('language');" />
											</div>
											<div id="labelProjectDiv" class="field-div">
												<?php echo $LANG['LABEL_PROJECT']; ?>
												<a href="#" onclick="return dwcDoc('labelproject')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="labelproject" maxlength="45" value="<?php echo array_key_exists('labelproject',$occArr)?$occArr['labelproject']:''; ?>" onchange="fieldChanged('labelproject');" />
											</div>
											<div id="duplicateQuantityDiv" class="field-div" title="aka label quantity">
												<?php echo $LANG['DUPLICATE_COUNT']; ?>
												<a href="#" onclick="return dwcDoc('duplicatequantity')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="duplicatequantity" value="<?php echo array_key_exists('duplicatequantity',$occArr)?$occArr['duplicatequantity']:''; ?>" onchange="fieldChanged('duplicatequantity');" />
											</div>
										</div>
										<div style="padding:3px;clear:both;">
											<div id="institutionCodeDiv" class="field-div" title="<?php echo $LANG['INST_CODE_EXPLAIN']; ?>">
												<?php echo $LANG['INSTITUTION_CODE']; ?>
												<a href="#" onclick="return dwcDoc('institutionCode')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="institutioncode" maxlength="32" value="<?php echo array_key_exists('institutioncode',$occArr)?$occArr['institutioncode']:''; ?>" onchange="fieldChanged('institutioncode');" />
											</div>
											<div id="collectionCodeDiv" class="field-div" title="<?php echo $LANG['COLL_CODE_EXPLAIN']; ?>">
												<?php echo $LANG['COLLECTION_CODE']; ?>
												<a href="#" onclick="return dwcDoc('collectionCode')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="collectioncode" maxlength="32" value="<?php echo array_key_exists('collectioncode',$occArr)?$occArr['collectioncode']:''; ?>" onchange="fieldChanged('collectioncode');" />
											</div>
											<div id="ownerInstitutionCodeDiv" class="field-div" title="<?php echo $LANG['OWNER_CODE_EXPLAIN']; ?>">
												<?php echo $LANG['OWNER_INSTITUTION_CODE']; ?>
												<a href="#" onclick="return dwcDoc('ownerInstitutionCode')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="ownerinstitutioncode" maxlength="32" value="<?php echo array_key_exists('ownerinstitutioncode',$occArr)?$occArr['ownerinstitutioncode']:''; ?>" onchange="fieldChanged('ownerinstitutioncode');" />
											</div>
											<div id="basisOfRecordDiv" class="field-div">
												<?php echo $LANG['BASIS_OF_RECORD']; ?>
												<a href="#" onclick="return dwcDoc('basisOfRecord')" tabindex="-1"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<?php
												$borArr = array('FossilSpecimen'=>0,'HumanObservation'=>0,'LivingSpecimen'=>0,'MachineObservation'=>0,'PreservedSpecimen'=>0);
												if(isset($occArr['basisofrecord']) && $occArr['basisofrecord']){
													if(in_array($occArr['basisofrecord'],$borArr)) $borArr[$occArr['basisofrecord']] = 1;
													else $borArr[$occArr['basisofrecord']] = 2;
												}
												if(!isset($occArr['basisofrecord']) || !$occArr['basisofrecord']){
													if($collType == 'obs') $borArr['HumanObservation'] = 1;
													elseif($collType == 'paleo') $borArr['FossilSpecimen'] = 1;
													elseif($collType == 'spec') $borArr['PreservedSpecimen'] = 1;
												}
												?>
												<select name="basisofrecord" onchange="fieldChanged('basisofrecord');">
													<?php
													foreach($borArr as $bValue => $statueCode){
														if($statueCode == 2) echo '<option value="">---'.$LANG['NON_SANCTIONED'].'---</option><option SELECTED>'.$bValue.'</option>';
														else echo '<option '.($statueCode?'SELECTED':'').'>'.$bValue.'</option>';
													}
													?>
												</select>
											</div>
											<div id="processingStatusDiv" class="field-div">
												<?php echo $LANG['PROCESSING_STATUS']; ?><br/>
												<?php
												$pStatus = '';
												if(!empty($occArr['processingstatus'])) $pStatus = strtolower($occArr['processingstatus']);
												if(!$pStatus && !$occId) $pStatus = 'pending review';
												?>
												<select name="processingstatus" onchange="fieldChanged('processingstatus');">
													<option value=''><?php echo $LANG['NO_SET_STATUS']; ?></option>
													<option value=''>-------------------</option>
													<?php
													foreach($processingStatusArr as $v){
														//Don't display these options if editor is crowd sourced
														$keyOut = strtolower($v);
														if($isEditor < 4 || ($keyOut != 'reviewed' && $keyOut != 'closed')){
															echo '<option value="'.$keyOut.'" '.($pStatus==$keyOut?'SELECTED':'').'>'.ucwords($v).'</option>';
														}
													}
													if($pStatus && $pStatus != 'isnull' && !in_array($pStatus,$processingStatusArr)){
														echo '<option value="'.$pStatus.'" SELECTED>'.$pStatus.'</option>';
													}
													?>
												</select>
											</div>
											<div id="dataGeneralizationsDiv" class="field-div" title="<?php echo $LANG['AKA_GENERAL']; ?>">
												<?php echo $LANG['DATA_GENERALIZATIONS']; ?><br/>
												<input type="text" name="datageneralizations" value="<?php echo array_key_exists('datageneralizations',$occArr)?$occArr['datageneralizations']:''; ?>" onchange="fieldChanged('datageneralizations');" />
											</div>
										</div>
										<?php
										if($occId){
											?>
											<div id="pkDiv">
												<hr/>
												<div style="float:left;" title="<?php echo $LANG['PRIMARY_KEY']; ?>">
													<?php if($occId) echo 'Key: '.$occManager->getOccId(); ?>
												</div>
												<div style="float:left;margin-left:50px;">
													<?php if(array_key_exists('datelastmodified',$occArr)) echo $LANG['MODIFIED'].': '.$occArr['datelastmodified']; ?>
												</div>
												<div style="float:left;margin-left:50px;">
													<?php
													if(array_key_exists('recordenteredby',$occArr)){
														echo $LANG['ENTERED_BY'].': '.($occArr['recordenteredby']?$occArr['recordenteredby']:$LANG['NOT_RECORDED']);
													}
													if(isset($occArr['dateentered']) && $occArr['dateentered']) echo ' ['.$occArr['dateentered'].']';
													?>
												</div>
											</div>
											<?php
										}
										?>
									</fieldset>
									<?php
									if($navStr){
										//echo '<div style="float:right;margin-right:20px;">'.$navStr.'</div>'."\n";
									}
									if(!$occId){
										$userChecklists = $occManager->getUserChecklists();
										if($userChecklists){
											?>
											<fieldset>
												<legend><?php echo $LANG['CHECKLIST_VOUCHER']; ?></legend>
												<?php echo $LANG['LINK_TO_CHECK']; ?>:
												<select name="clidvoucher">
													<option value=""><?php echo $LANG['NO_CHECKLIST']; ?></option>
													<option value="">---------------------------------------------</option>
													<?php
													foreach($userChecklists as $clid => $clName){
														echo '<option value="'.$clid.'">'.$clName.'</option>';
													}
													?>
												</select>
											</fieldset>
											<?php
										}
									}
									?>
									<div id="bottomSubmitDiv">
										<input type="hidden" name="occid" value="<?php echo $occManager->getOccId(); ?>" />
										<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
										<input type="hidden" name="observeruid" value="<?php echo $SYMB_UID; ?>" />
										<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
										<input type="hidden" name="linkdupe" value="" />
										<?php
										if($occId){
											?>
											<div style="float:left">
												<?php echo $LANG['STATUS_AUTO_SET']; ?>:
												<select name="autoprocessingstatus" onchange="autoProcessingStatusChanged(this)">
													<option value=''><?php echo $LANG['NOT_ACTIVATED']; ?></option>
													<option value=''>-------------------</option>
													<?php
													foreach($processingStatusArr as $v){
														$keyOut = strtolower($v);
														//Don't display all options if editor is crowd sourced
														if($isEditor < 4 || ($keyOut != 'reviewed' && $keyOut != 'closed')){
															echo '<option value="'.$keyOut.'" '.($crowdSourceMode && $keyOut == "pending review"?'SELECTED':'').'>'.ucwords($v).'</option>';
														}
													}
													?>
												</select>
												<div id="editButtonDiv">
													<button type="submit" id="saveEditsButton" name="submitaction" value="saveOccurEdits" style="width:150px;" onclick="return verifyFullFormEdits(this.form)" disabled><?php echo $LANG['SAVE_EDITS']; ?></button>
													<input type="hidden" name="occindex" value="<?php echo is_numeric($occIndex)?$occIndex:''; ?>" />
													<input type="hidden" name="editedfields" value="" />
												</div>
											</div>
											<?php
											if($isEditor == 1 || $isEditor == 2){
												?>
												<div id="cloneDiv" style="float:right;">
													<fieldset class="optionBox">
														<legend><?php echo $LANG['RECORD_CLONING']; ?></legend>
														<div class="fieldGroup-div">
															<label><?php echo $LANG['CARRY_OVER']; ?>:</label>
															<span>
																<input name="carryover" type="radio" value="1" checked /><?php echo $LANG['COLL_EVENT_FIELDS']; ?>
																<input name="carryover" type="radio" value="0" /><?php echo $LANG['ALL_FIELDS']; ?>
															</span>
														</div>
														<div class="fieldGroup-div">
															<span><input name="carryoverimages" type="checkbox" value="1" /></span>
															<label><?php echo $LANG['CARRY_OVER_IMAGES']; ?></label>
														</div>
														<div class="fieldGroup-div" title="Relationship to this occurrence">
															<label><?php echo $LANG['RELATIONSHIP']; ?>:</label>
															<select name="assocrelation">
																<option value="0"><?php echo $LANG['UNDEFINED']; ?></option>
																<option value="0">---------------------------------</option>
																<?php
																$vocabArr = $occManager->getAssociationControlVocab();
																foreach($vocabArr as $id => $termVal){
																	echo '<option value="'.$termVal.'">'.$termVal.'</option>';
																}
																?>
															</select>
														</div>
														<?php
														$targetArr = $occManager->getCollectionList(true);
														unset($targetArr[$collId]);
														if(count($targetArr) > 1){
															?>
															<div class="fieldGroup-div">
																<label><?php echo $LANG['TARGET_COLL']; ?>:</label>
																<select name="targetcollid" style="max-width: 250px">
																	<option value="0"><?php echo $LANG['CURRENT_COLL']; ?></option>
																	<option value="0">---------------------------------</option>
																	<?php
																	foreach($targetArr as $id => $collVal){
																		echo '<option value="'.$id.'">'.$collVal.'</option>';
																	}
																	?>
																</select>
															</div>
															<?php
														}
														?>
														<div class="fieldGroup-div">
															<label><?php echo $LANG['NUMBER_RECORDS']; ?>:</label> <input id="clonecount" name="clonecount" type="text" value="1" style="width:40px" />
														</div>
														<div class="fieldGroup-div"><a href="#" onclick="return prePopulateCatalogNumbers();"><?php echo htmlspecialchars($LANG['PRE_POPULATE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></div>
														<fieldset id="cloneCatalogNumber-Fieldset" style="display:none">
															<div id="cloneCatalogNumberDiv" class="fieldGroup-div"></div>
														</fieldset>
														<div style="margin:10px">
															<button name="submitaction" type="submit" value="cloneRecord"><?php echo $LANG['CREATE_RECORD']; ?></button>
														</div>
													</fieldset>
												</div>
												<?php
											}
										}
										else{
											?>
											<div id="addButtonDiv">
												<input name="recordenteredby" type="hidden" value="<?php echo $PARAMS_ARR['un']; ?>" />
												<button name="submitaction" type="submit" value="addOccurRecord" style="width:150px;font-weight:bold;margin:10px;"><?php echo $LANG['ADD_RECORD']; ?></button>
												<input name="qrycnt" type="hidden" value="<?php echo $qryCnt?$qryCnt:''; ?>" />
												<div style="margin-left:15px;font-weight:bold;">
													<?php echo $LANG['FOLLOW_UP']; ?>:
												</div>
												<div style="margin-left:20px;">
													<input name="gotomode" type="radio" value="1" <?php echo ($goToMode==1?'CHECKED':''); ?> /> <?php echo $LANG['GO_TO_NEW']; ?><br/>
													<input name="gotomode" type="radio" value="2" <?php echo ($goToMode==2?'CHECKED':''); ?> /> <?php echo $LANG['GO_NEW_CARRYOVER']; ?><br/>
													<input name="gotomode" type="radio" value="0" <?php echo (!$goToMode?'CHECKED':''); ?> /> <?php echo $LANG['REMAIN_ON_PAGE']; ?>
												</div>
											</div>
											<?php
										}
										?>
									</div>
									<div style="clear:both;">&nbsp;</div>
								</form>
							</div>
						</div>
						<?php endif ?>

						<?php if($qryCnt == 0 && !$occId && array_key_exists('q_catalognumber',$_POST)): ?>
						<div style="clear:both;padding:20px;font-weight:bold;font-size:120%;">
							<?php echo (isset($LANG['NONE_FOUND'])?$LANG['NONE_FOUND']:'No records found matching the query'); ?>
						</div>
						<?php endif ?>
					</td>
					<td id="imgtd" style="display:none;width:430px;" valign="top">
						<?php
						if($occId && ($fragArr || $specImgArr )){
							include_once('includes/imgprocessor.php');
						}
						?>
					</td></tr>
				</table>
				<?php
			}
		}
		else{
			if(!$collId && !$occId) echo $LANG['ERROR_ID_NULL'];
			elseif(!$isEditor){
				echo '<div style="margin:30px;">';
				if($crowdSourceMode){
					echo $LANG['ERROR_NO_CROWDSOURCING'].'<div style="margin:15px"><a href="../specprocessor/crowdsource/index.php">'.$LANG['RETURN_TO_CROWDSOURCING'].'</a></div>';
				}
				else echo $LANG['ERROR_NOT_AUTHORIZED'];
				echo '</div>';
			}
			elseif($isEditor == 4 && !$occId){
				echo '<b>'.$LANG['ERROR_CANT_ADD'];
			}
		}
		?>
	</div>
</body>
</html>
