<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceSkeletal.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/skeletalsubmit.'.$LANG_TAG.'.php')) include_once($SERVER_ROOT.'/content/lang/collections/editor/skeletalsubmit.'.$LANG_TAG.'.php');
else include_once($SERVER_ROOT.'/content/lang/collections/editor/skeletalsubmit.en.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/skeletalsubmit.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$collid  = $_REQUEST["collid"];
$action = array_key_exists("formaction",$_REQUEST)?$_REQUEST["formaction"]:"";

$skeletalManager = new OccurrenceSkeletal();
if($collid){
	$skeletalManager->setCollid($collid);
	$collMap = $skeletalManager->getCollectionMap();
}

$statusStr = '';
$isEditor = 0;
if($collid){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor'])){
		$isEditor = 1;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['OCC_SKEL_SUBMIT']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.skeletal.js?ver=2" type="text/javascript"></script>
	<script src="../../js/symb/collections.editor.autocomplete.js?ver=1" type="text/javascript"></script>
	<script src="../../js/symb/shared.js?ver=1" type="text/javascript"></script>
	<style>
		label{  }
		fieldset{ padding: 15px; }
		legend{ font-weight: bold; }
	</style>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&emode=1"><?php echo htmlspecialchars($LANG['COL_MNGMT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<b><?php echo $LANG['OCC_SKEL_SUBMIT']; ?></b>
	</div>
	<!-- inner text -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?php echo $LANG['OCC_SKEL_SUBMIT'] . ': ' . $collMap['collectionname']; ?></h1>
		<?php
		if($statusStr){
			echo '<div style="margin:15px;color:red;">'.$statusStr.'</div>';
		}
		if($isEditor){
			?>
			<section class="fieldset-like">
				<h2>
					<span><?php echo $LANG['SKELETAL_DATA']; ?></span>
					<span onclick="toggle('descriptiondiv')" onkeypress="toggle('descriptiondiv')" tabindex="0"><img src="../../images/info.png" style="width:1em;" title="<?php echo $LANG['TOOL_DESCRIPTION']; ?>" aria-label="<?php echo (isset($LANG['IMG_TOOL_DESCRIPTION'])?$LANG['IMG_TOOL_DESCRIPTION']:'Description of Tool Button'); ?>"/></span>
					<span id="optionimgspan" onclick="showOptions()" onkeypress="showOptions()" tabindex="0"><img src="../../images/list.png" style="width:1em;" title="<?php echo $LANG['DISPLAY_OPTIONS']; ?>" aria-label="<?php echo (isset($LANG['IMG_DISPLAY_OPTIONS'])?$LANG['IMG_DISPLAY_OPTIONS']:'Display Options Button'); ?>"/></span>
				</h2>
				<div id="descriptiondiv" style="display:none;margin:10px;width:80%">
					<div style="margin-bottom:5px">
						<?php echo $LANG['SKELETAL_DESCIPRTION_1']; //This page is typically used to enter skeletal records into the system during the imaging process...?>
					</div>
					<div style="margin-bottom:5px">
						<?php echo $LANG['SKELETAL_DESCIPRTION_2']; //More complete data can be entered by clicking on the catalog number...?>
					</div>
					<div>
						<?php echo $LANG['SKELETAL_DESCIPRTION_3']; //Click the Display Option symbol located above scientific name to adjust field display...?>
					</div>
 				</div>
				<form id="defaultform" name="defaultform" action="skeletalsubmit.php" method="post" autocomplete="off" onsubmit="return submitDefaultForm(this)">
					<div id="optiondiv" style="display:none;position:absolute;background-color:white;">
						<fieldset style="margin-top: -10px;padding-top:5px">
							<legend><?php echo $LANG['OPTIONS']; ?></legend>
							<div style="float:right;"><a href="#" onclick="hideOptions()" style="color:red" ><?php echo $LANG['X_CLOSE']; ?></a></div>
							<div style="text-decoration: underline"><?php echo $LANG['FIELD_DISPLAY']; ?>:</div>
							<input type="checkbox" onclick="toggleFieldDiv('othercatalognumbersdiv')" /> <?php echo $LANG['OTHER_CAT_NUMS']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('authordiv')" CHECKED /> <?php echo $LANG['AUTHOR']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('familydiv')" CHECKED /> <?php echo $LANG['FAMILY']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('localitysecuritydiv')" CHECKED /> <?php echo $LANG['LOCALITY_SECURITY']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('countrydiv')" /> <?php echo $LANG['COUNTRY']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('statediv')" CHECKED /> <?php echo $LANG['STATE_PROVINCE']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('countydiv')" CHECKED /> <?php echo $LANG['COUNTY_PARISH']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('recordedbydiv')" /> <?php echo $LANG['COLLECTOR']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('recordnumberdiv')" /> <?php echo $LANG['COLLECTOR_NO']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('eventdatediv')" /> <?php echo $LANG['COLLECTION_DATE']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('labelprojectdiv')" /> <?php echo $LANG['LABEL_PROJECT']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('processingstatusdiv')" /> <?php echo $LANG['PROCESSING_STATUS']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('languagediv')" /> <?php echo $LANG['LANGUAGE']; ?><br/>
							<input type="checkbox" onclick="toggleFieldDiv('exsiccatadiv')" /> <?php echo $LANG['EXSICCATA']; ?><br/>
							<div style="text-decoration: underline"><?php echo $LANG['CATNUM_MATCH']; ?>:</div>
							<input name="addaction" type="radio" value="1" checked /> <?php echo $LANG['RESTRICT_IF_EXISTS']; ?> <br/>
							<input name="addaction" type="radio" value="2" /> <?php echo $LANG['APPEND_VALUES']; ?>
						</fieldset>
					</div>
					<?php echo $LANG['SESSION']; ?>: <span id="minutes">00</span>:<span id="seconds">00</span><br/>
					<?php echo $LANG['COUNT']; ?>: <span id="count">0</span><br/>
					<?php echo $LANG['RATE']; ?>: <span id="rate">0</span> <?php echo $LANG['PER_HOUR']; ?>

					<div class="flex-form" style="float:right">
							<div>
								<button name="clearform" type="reset" onclick="resetForm()" value="<?php echo $LANG['CLEAR'] ?>"><?php echo $LANG['CLEAR'] ?></button>
							</div>
						</div>
					<div class="flex-form">
						<div class="flex-form">
							<div id="scinamediv">
									<label for="fsciname"><?php echo $LANG['SCINAME']; ?>:</label>
									<input id="fsciname" name="sciname" type="text" value=""/>
									<input id="ftidinterpreted" name="tidinterpreted" type="hidden" value="" />
							</div>
						</div>
						<div class="flex-form">
							<div id="authordiv" class="left-breathing-room-rel">
								<label for="fscientificnameauthorship">
									<?php echo (isset($LANG['AUTHORSHIP']) ? $LANG['AUTHORSHIP'] : 'Authorship'). ':'; ?>
								</label>
								<input id="fscientificnameauthorship" name="scientificnameauthorship" type="text" value="" />
							</div>
						</div>
						<?php
						if($IS_ADMIN || isset($USER_RIGHTS['Taxonomy'])){
							?>
							<div style="float:left;padding:2px 3px;">
								<a href="../../taxa/taxonomy/taxonomyloader.php" target="_blank">
									<img src="../../images/add.png" style="width:1.5em" title="<?php echo $LANG['ADD_NAME_THESAURUS']; ?>" aria-label="<?php echo $LANG['ADD_NAME_THESAURUS']; ?>" />
								</a>
							</div>
							<?php
						}
						?>
						<div class="flex-form">
							<div id="familydiv">
								<label for="ffamily"><?php echo $LANG['FAMILY']; ?>:</label> <input id="ffamily" name="family" type="text" tabindex="0" value="" />
							</div>
							<div id="localitysecuritydiv">
								<input id="flocalitysecurity" name="localitysecurity" type="checkbox" tabindex="0" value="1" />
								<label for="flocalitysecurity">
									<?php echo $LANG['PROTECT_LOCALITY']; ?>
								</label>
							</div>
						</div>
						<div class="flex-form">
							<div id="countrydiv" style="display:none;float:left;margin:3px;">
								<label for="fcountry"><?php echo $LANG['COUNTRY']; ?></label><br/>
								<input id="fcountry" name="country" type="text" value="" autocomplete="off" />
							</div>
							<div id="statediv">
								<label for="fstateprovince"><?php echo $LANG['STATE_PROVINCE']; ?>:</label>
								<input id="fstateprovince" name="stateprovince" type="text" value="" autocomplete="off" onchange="localitySecurityCheck(this.form)" />
							</div>
							<div id="countydiv">
								<label for="fcounty"><?php echo $LANG['COUNTY_PARISH']; ?>:</label>
								<input id="fcounty" name="county" type="text" autocomplete="off" value="" />
							</div>
						</div>
						<div >
							<div id="recordedbydiv" style="display:none;float:left;margin:3px;">
								<label for="frecordedby"><?php echo $LANG['COLLECTOR']; ?></label><br/>
								<input id="frecordedby" name="recordedby" type="text" value="" />
							</div>
							<div id="recordnumberdiv" style="display:none;float:left;margin:3px;">
								<label for="frecordnumber"><?php echo $LANG['COLLECTOR_NO']; ?></label><br/>
								<input id="frecordnumber" name="recordnumber" type="text" value="" />
							</div>
							<div id="eventdatediv" style="display:none;float:left;margin:3px;">
								<label><?php echo $LANG['DATE']; ?></label><br/>
								<input id="feventdate" name="eventdate" type="text" value="" onchange="eventDateChanged(this)" />
							</div>
							<div id="labelprojectdiv" style="display:none;float:left;margin:3px;">
								<label><?php echo $LANG['LABEL_PROJECT']; ?></label><br/>
								<input id="flabelproject" name="labelproject" type="text" value="" />
							</div>
							<div id="processingstatusdiv" style="display:none;float:left;margin:3px">
								<label><?php echo $LANG['PROCESSING_STATUS']; ?></label><br/>
								<select id="fprocessingstatus" name="processingstatus" style="margin-top:4px;width:150px">
									<option value=""></option>
									<option>unprocessed</option>
									<option>stage 1</option>
									<option>stage 2</option>
									<option>stage 3</option>
									<option>expert required</option>
									<option>pending review-nfn</option>
									<option>pending review</option>
									<option>reviewed</option>
									<option>closed</option>
								</select>
							</div>
							<div id="languagediv" style="display:none;float:left;margin:3px;">
								<label><?php echo $LANG['LANGUAGE']; ?></label><br/>
								<select id="flanguage" name="language" style="margin-top:4px">
									<option value=""></option>
									<?php
									$langArr = $skeletalManager->getLanguageArr();
									foreach($langArr as $code => $langStr){
										echo '<option value="'.$code.'">'.$langStr.'</option>';
									}
									?>
								</select>
							</div>
							<div id="exsiccatadiv" style="display:none;clear:both;">
								<div id="ometidDiv" style="float:left">
									<label><?php echo (isset($LANG['EXSTITLE'])?$LANG['EXSTITLE']:'Exsiccati Title'); ?></label><br/>
									<input id="fexstitle" name="exstitle" value="" style="width: 600px" />
									<input id="fometid" name="ometid" type="hidden" value="" />
								</div>
								<div id="exsnumberDiv">
									<label><?php echo (isset($LANG['EXSNUMBER'])?$LANG['EXSNUMBER']:'Number'); ?></label><br/>
									<input id="fexsnumber" name="exsnumber" type="text" value="" />
								</div>
							</div>
						</div>

						<div class="flex-form">

							<div style="float:left;">
								<label for="fcatalognumber">
									<?php echo $LANG['CATALOGNUMBER']; ?>:
								</label>
								<input id="fcatalognumber" name="catalognumber" type="text" style="border-color:green;" />
							</div>
							<div id="othercatalognumbersdiv" style="display:none;float:left;margin-left:3px;">
								<label><?php echo $LANG['OTHER_CAT_NUMS']; ?></label><br/>
								<input id="fothercatalognumbers" name="othercatalognumbers" type="text" value="" />
							</div>
							<div>
								<input id="fcollid" name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<button name="recordsubmit" type="submit" value="Add Record"><?php echo $LANG['ADD_RECORD']; ?></button>
							</div>
						</div>

					</div>
				</form>
			</section>
			<section class="fieldset-like">
				<h2>
					<span><?php echo $LANG['RECORDS']; ?></span>
				</h2>
				<div id="occurlistdiv">
				</div>
			</section>
			<?php
		}
		else{
			if($collid){
				echo $LANG['NOT_AUTHORIZED'].'<br/>';
				echo $LANG['CONTACT_ADMIN'].'</b> ';
			}
			else{
				echo $LANG['ERROR_NO_ID'];
			}
		}
		?>
	</div>
<?php
	include($SERVER_ROOT.'/includes/footer.php');
?>
</body>
</html>
