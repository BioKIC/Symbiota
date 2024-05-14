<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
include_once($SERVER_ROOT . '/content/lang/collections/datasets/index.' . $LANG_TAG . '.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/datasets/index.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$action = array_key_exists('submitaction',$_REQUEST) ? $_REQUEST['submitaction'] : '';

//Sanitize input variables
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';
$isPublic = (isset($_POST['ispublic'])&&is_numeric($_POST['ispublic']) ? 1 : 0);

$datasetManager = new OccurrenceDataset();

$statusStr = '';
if($action == 'createNewDataset'){
	if($IS_ADMIN || array_key_exists('ClCreate',$USER_RIGHTS)){
		if(!$datasetManager->createDataset($_POST['name'],$_POST['notes'],$_POST['description'],$isPublic,$SYMB_UID)){
			$statusStr = implode(',',$datasetManager->getErrorArr());
		}
	}
	else {
		$statusStr = 'You don\'t have permission to create a dataset';
	}
}
elseif($action == 'addSelectedToDataset'){
	$datasetID = $_POST['datasetid'];
	if(!$datasetID && $_POST['name']) $datasetManager->createDataset($_POST['name'],'',$SYMB_UID);
}
elseif($action == 'addAllToDataset'){

}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE; ?> <?php echo (isset($LANG['OCC_DAT_MNG']) ? $LANG['OCC_DAT_MNG'] : 'Occurrence Dataset Manager') ?> </title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
		<script type="text/javascript" src="../../js/symb/shared.js"></script>
		<script type="text/javascript" src="../../js/tinymce/tinymce.min.js"></script>
		<script type="text/javascript">
			// Adds WYSIWYG editor to description field
			tinymce.init({
				selector: '#description',
				plugins: 'link lists image',
				menubar: '',
				toolbar: ['undo redo | bold italic underline | link | alignleft aligncenter alignright | formatselect | bullist numlist | indent outdent | blockquote | image'],
				branding: false,
        		default_link_target: "_blank",
				paste_as_text: true
			});
		</script>
		<script type="text/javascript">
			function validateAddForm(f){
				if(f.adduser.value == ""){
					alert("Enter a user (login or last name)");
					return false
				}
				if(f.adduser.value.indexOf(" [#") == -1){
					$.ajax({
						url: "rpc/getuserlist.php",
						dataType: "json",
						data: {
							term: f.adduser.value
						},
						success: function(data) {
							if(data && data != ""){
								f.adduser.value = data;
								alert("Located login: "+data);
								f.submit();
							}
							else{
								alert("Unable to locate user");
							}
						}
					});
					return false;
				}
				return true;
			}
		</script>
		<style>
			fieldset{ padding:15px;margin:15px; }
			legend{ font-weight: bold; }
			.dataset-item{ margin-bottom: 10px }
		</style>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_datasets_indexMenu) ? $collections_datasets_indexMenu : false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'> <?php echo (isset($LANG['HOME']) ? $LANG['HOME'] : 'Home') ?> </a> &gt;&gt;
		<a href="../../profile/viewprofile.php?tabindex=1"> <?php echo (isset($LANG['MY_PROFILE']) ? $LANG['MY_PROFILE'] : 'My Profile') ?> </a> &gt;&gt;
		<a href="index.php"><b> <?php echo (isset($LANG['DATLIST']) ? $LANG['DATLIST'] : 'Dataset Listing') ?> </b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<?php
		if($statusStr){
			$color = 'green';
			if(strpos($statusStr,'ERROR') !== false) $color = 'red';
			elseif(strpos($statusStr,'WARNING') !== false) $color = 'orange';
			elseif(strpos($statusStr,'NOTICE') !== false) $color = 'yellow';
			echo '<div style="margin:15px;color:'.$color.';">';
			echo $statusStr;
			echo '</div>';
		}
		$dataSetArr = $datasetManager->getDatasetArr();
		?>
		<div>
		<div style="float:right;margin:10px;" title="<?php echo (isset($LANG['CRT_NEW_DAT']) ? $LANG['CRT_NEW_DAT'] : 'Create New Dataset') ?>" >
	 		<a href="#" onclick="toggle('adddiv');return false;"><img src="../../images/add.png"  style="width:1.5em;" alt="<?php echo (isset($LANG['ADD_BUTTON']) ? $LANG['ADD_BUTTON'] : 'Add Button') ?>"/> </a>
		</div>
		<h1 class="page-heading"> <?php echo (isset($LANG['OCC_DAT_MNG']) ? $LANG['OCC_DAT_MNG'] : 'Occurrence Dataset Manager') ?> </h1>
		<div> <?php echo (isset($LANG['TOOL_DESCR']) ? $LANG['TOOL_DESCR'] : 'These tools will allow you to define and manage dataset profiles. Once a profile is created, you can link occurrence records via the occurrence search and display pages.') ?> </div>
		<div id=adddiv style="display:none">
			<fieldset>
				<legend><b> <?php echo (isset($LANG['CRT_NEW_DAT']) ? $LANG['CRT_NEW_DAT'] : 'Create New Dataset') ?> </b></legend>
				<form name="adminform" action="index.php" method="post" onsubmit="return validateEditForm(this)">
					<div>
						<p><b> <?php echo (isset($LANG['NAME']) ? $LANG['NAME'] : 'Name') ?> </b></p>
						<input name="name" type="text" style="width:90%" />
					</div>
          <div>
            <p>
              <input type="checkbox" name="ispublic" id="ispublic" value="1" />
            <b> <?php echo (isset($LANG['PUB_VIS']) ? $LANG['PUB_VIS'] : 'Publicly Visible') ?> </b>
            </p>
          </div>
					<div>
						<p><b> <?php echo (isset($LANG['NOTES']) ? $LANG['NOTES'] : 'Notes (Internal usage, not displayed publicly)') ?></b></p>
						<input name="notes" type="text" style="width:90%;" />
					</div>
          <div>
            <p><b> <?php echo (isset($LANG['DESCR']) ? $LANG['DESCR'] : 'Description (Displayed publicly)') ?> </b> </p>
            <textarea name="description" id="description" cols="100" rows="10"></textarea>
          </div>
					<div style="margin:15px">
						<button name="submitaction" type="submit" value="createNewDataset"> <?php echo (isset($LANG['CRT_NEW_DAT']) ? $LANG['CRT_NEW_DAT'] : 'Create New Dataset') ?> </button>
					</div>
				</form>
			</fieldset>
		</div>
		<?php
		if($dataSetArr){
			?>
			<fieldset>
				<legend><b> <?php echo (isset($LANG['OWNED']) ? $LANG['OWNED'] : 'Owned by You') ?> </b></legend>
				<?php
				if(array_key_exists('owner',$dataSetArr)){
					$ownerArr = $dataSetArr['owner'];
					unset($dataSetArr['owner']);
					foreach($ownerArr as $dsid => $dsArr){
						?>
						<div class="dataset-item">
							<div>
								<a href="datasetmanager.php?datasetid=<?php echo $dsid; ?>" title="<?php echo (isset($LANG['MNG_EDIT']) ? $LANG['MNG_EDIT'] : 'Manage and edit dataset') ?>">
									<?php
									echo '<b>'.$dsArr['name'].' (#'.$dsid.')</b>';
									?>
								</a>
							</div>
							<div style="margin-left:15px;">
								<?php
								echo ($dsArr['notes'] ? '<div>'.$dsArr['notes'].'</div>' : '');
								echo '<div>Created: '.$dsArr["ts"].'</div>';
								?>
							</div>
						</div>
						<?php
					}
				}
				else{
					echo '<div style="font-weight:bold;">' . (isset($LANG['NO_OWNED']) ? $LANG['NO_OWNED'] : 'There are no datasets owned by you') . '</div>';
				}
				?>
			</fieldset>
			<fieldset>
				<legend><?php echo (isset($LANG['SHARED']) ? $LANG['SHARED'] : 'Shared with You') ?> </legend>
				<?php
				if(array_key_exists('other',$dataSetArr)){
					$otherArr = $dataSetArr['other'];
					foreach($otherArr as $dsid => $dsArr){
						?>
						<div>
							<a href="datasetmanager.php?datasetid=<?php echo htmlspecialchars($dsid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>" title="Access Dataset">
								<?php
								$role = 'Dataset reader';
								if($dsArr['role'] == 'DatasetAdmin') $role = 'Dataset Administator';
								elseif($dsArr['role'] == 'DatasetEditor') $role = 'Dataset Editor';
								echo '<b>'.$dsArr["name"].' (#'.$dsid.')</b> - '.$role;
								?>
							</a>
						</div>
						<div style="margin-left:15px;">
							<?php
							echo ($dsArr["notes"]?$dsArr["notes"].'<br/>':'');
							echo 'Created: '.$dsArr["ts"];
							?>
						</div>
						<?php
					}
				}
				else echo '<div style="font-weight:bold;">' . (isset($LANG['NO_SHARED']) ? $LANG['NO_SHARED'] : 'There are no datasets shared with you') . '</div>';
				?>
			</fieldset>
			<?php
		}
		else{
			?>
			<div style="margin:20px">
				<div style="font-weight:bold"> <?php echo (isset($LANG['NO_LOGIN']) ? $LANG['NO_LOGIN'] : 'There are no datasets associated to your login') ?> </div>
				<div style="margin-top:15px"><a href="#" onclick="toggle('adddiv');"> <?php echo (isset($LANG['CRT_NEW_DAT']) ? $LANG['CRT_NEW_DAT'] : 'Create New Dataset') ?> </a></div>
			</div>
			<?php
		}
		?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>
