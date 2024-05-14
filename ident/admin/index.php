<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/KeyCharAdmin.php');
include_once($SERVER_ROOT . '/content/lang/ident/index.' . $LANG_TAG . '.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/admin/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$langId = array_key_exists('langid',$_REQUEST)?$_REQUEST['langid']:'';

$charManager = new KeyCharAdmin();
$charManager->setLangId($langId);

$charArr = $charManager->getCharacterArr();
$headingArr = $charManager->getHeadingArr();

$isEditor = false;
if($IS_ADMIN || array_key_exists("KeyAdmin",$USER_RIGHTS)){
	$isEditor = true;
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title> <?php echo $LANG['CHAR_ADMIN']; ?> </title>
	<?php

	include_once($SERVER_ROOT.'/includes/head.php');
	?>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript">
		function validateNewCharForm(f){
			if(f.charname.value == ""){
				alert("<?php echo (isset($LANG['ALERT_NAME']) ? $LANG['ALERT_NAME'] : 'Character name must have a value'); ?>");
				return false;
			}
			if(f.chartype.value == ""){
				alert("<?php echo (isset($LANG['ALERT_TYPE']) ? $LANG['ALERT_TYPE'] : 'A character type must be selected'); ?>");
				return false;
			}
			if(f.sortsequence.value && !isNumeric(f.sortsequence.value)){
				alert("<?php echo (isset($LANG['ALERT_SORT']) ? $LANG['ALERT_SORT'] : 'Sort Sequence must be a numeric value only'); ?>");
				return false;
			}
			return true;
		}

		function openHeadingAdmin(){
			newWindow = window.open("headingadmin.php","headingWin","scrollbars=1,toolbar=0,resizable=1,width=800,height=600,left=50,top=50");
			if (newWindow.opener == null) newWindow.opener = self;
		}
	</script>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'> <?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home'); ?> </a> &gt;&gt;
		<b> <?php echo (isset($LANG['CHAR_MGMT']) ? $LANG['CHAR_MGMT'] : 'Character Management'); ?> </b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['CHAR_ADMIN']; ?></h1>
		<?php
		if($isEditor){
			?>
			<div id="addeditchar">
				<div>
					<a href="#" onclick="toggle('addchardiv');">
						<img src="../../images/add.png" style='width:1.5em;' alt="<?php echo (isset($LANG['ADD_BTN']) ? $LANG['ADD_BTN'] : 'Create new character'); ?>" />
					</a>
				</div>
				<div id="addchardiv" style="display:none;margin-bottom:8px;">
					<form name="newcharform" action="chardetails.php" method="post" onsubmit="return validateNewCharForm(this)">
						<fieldset>
							<legend><b> <?php echo (isset($LANG['NEW_CHAR']) ? $LANG['NEW_CHAR'] : 'New Character'); ?> </b></legend>
							<div>
							<label for="charname"> <?php echo (isset($LANG['CHAR_NAME']) ? $LANG['CHAR_NAME'] : 'Character Name'); ?>: </label>
								<input type="text" id="charname" name="charname" autocomplete="off" maxlength="255" style="width:400px;" />
							</div>
							<div class="flex-form">
								<div>
									<label for="chartype"> <?php echo (isset($LANG['TYPE']) ? $LANG['TYPE'] : 'Type'); ?>:</label>
									<select id="chartype" name="chartype">
										<option value="UM"> <?php echo (isset($LANG['MULTI_STATE']) ? $LANG['MULTI_STATE'] : 'Multi-state'); ?> </option>
									</select>
								</div>
								<div>
								<label for="difficultyrank"> <?php echo (isset($LANG['DIFFICULTY']) ? $LANG['DIFFICULTY'] : 'Difficulty'); ?>: </label>
									<select id="difficultyrank" name="difficultyrank">
										<option value="">---------------</option>
										<option value="1"> <?php echo (isset($LANG['EASY']) ? $LANG['EASY'] : 'Easy'); ?> </option>
										<option value="2"> <?php echo (isset($LANG['INTERMEDIATE']) ? $LANG['INTERMEDIATE'] : 'Intermediate'); ?> </option>
										<option value="3"> <?php echo (isset($LANG['ADVANCED']) ? $LANG['ADVANCED'] : 'Advanced'); ?> </option>
										<option value="4"> <?php echo (isset($LANG['HIDDEN']) ? $LANG['HIDDEN'] : 'Difficulty'); ?> </option>
									</select>
								</div>
								<div>
									<label for="hid"> <?php echo (isset($LANG['GROUPING']) ? $LANG['GROUPING'] : 'Grouping'); ?>: </label>
									<select id="hid" name="hid" style="max-width:300px;">
										<option value=""> <?php echo (isset($LANG['NOT_ASSIGNED']) ? $LANG['NOT_ASSIGNED'] : 'Not Assigned'); ?> </option>
										<option value="">---------------------</option>
										<?php
										$hArr = $headingArr;
										asort($hArr);
										foreach($hArr as $k => $v){
											echo '<option value="'.$k.'">'.$v['name'].'</option>';
										}
										?>
									</select>
									<a href="#" onclick="openHeadingAdmin(); return false;"> <img src="../../images/edit.png" style='width:1.3em;' alt="<?php echo (isset($LANG['EDIT_BTN']) ? $LANG['EDIT_BTN'] : 'Create new group'); ?>" /></a>
								</div>
							</div>
							<div class="flex-form">
								<div>
									<label for="sortsequence"> <?php echo (isset($LANG['SORT_SQNCE']) ? $LANG['SORT_SQNCE'] : 'Sort Sequence'); ?> </label>
									<input type="text" id="sortsequence" name="sortsequence" autocomplete="off" />
								</div>
							</div>
							<div style="width:100%;padding-top:6px;">
								<button name="formsubmit" type="submit" value="Create"> <?php echo (isset($LANG['CREATE_BTN']) ? $LANG['CREATE_BTN'] : 'Create'); ?> </button>
							</div>
						</fieldset>
					</form>
				</div>
				<div id="charlist" style="padding-left:10px;">
					<?php
					if($charArr){
						?>
						<h3> <?php echo (isset($LANG['CHARS']) ? $LANG['CHARS'] : 'Characters'); ?> </h3>
						<ul>
							<?php
							foreach($headingArr as $hid => $hArr){
								if(array_key_exists($hid, $charArr)){
									?>
									<li>
										<a href="#" onclick="toggle('char-<?php echo $hid; ?>');return false;"><b><?php echo $hArr['name']; ?></b></a>
										<div id="char-<?php echo $hid; ?>" style="display:block;">
											<ul>
												<?php
												$charList = $charArr[$hid];
												foreach($charList as $cid => $charName){
													if ($charName)
														echo '<li><a href="chardetails.php?cid=' . htmlspecialchars($cid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($charName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
												}
												?>
											</ul>
										</div>
									</li>
									<?php
								}
							}
							if(array_key_exists(0, $charArr)){
								$noHeaderArr = $charArr[0];
								?>
								<li>
									<a href="#" onclick="toggle('char-0');return false;"><b> <?php echo (isset($LANG['NO_GRP_ASSSIGNED']) ? $LANG['NO_GRP_ASSSIGNED'] : ' No Assigned Grouping'); ?> </b></a>
									<div id="char-0" style="display:block;">
										<ul>
											<?php
											foreach($noHeaderArr as $cid => $charName){
												echo '<li><a href="chardetails.php?cid=' . htmlspecialchars($cid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '">' . htmlspecialchars($charName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
											}
											?>
										</ul>
									</div>
								</li>
								<?php
							}
							?>
						</ul>
					<?php
					}
					else{
						echo '<div style="font-weight:bold;font-size:120%;">' . (isset($LANG['NO_CHAR']) ? $LANG['NO_CHAR'] : 'There are no existing characters') . '</div>';
					}
					?>
				</div>
			</div>
			<?php
		}
		else{
			echo '<h2>' . (isset($LANG['NO_AUTH']) ? $LANG['NO_AUTH'] : 'You are not authorized to add characters') .'</h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>
