<?php
require_once('../config/symbini.php');
require_once($SERVER_ROOT.'/classes/PortalIndex.php');
//include_once($SERVER_ROOT.'/content/lang/admin/portalindex.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../admin/portalindex.php?'.htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$portalID = array_key_exists('portalid', $_REQUEST) ? filter_var($_REQUEST['portalid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$remoteID = array_key_exists('remoteid', $_REQUEST) ? filter_var($_REQUEST['remoteid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$remotePath = array_key_exists('remotePath', $_POST) ? filter_var($_POST['remotePath'], FILTER_SANITIZE_URL) : '';
$formSubmit = array_key_exists('formsubmit', $_POST) ? $_POST['formsubmit'] : '';

$portalManager = new PortalIndex();

$isEditor = 0;
if($IS_ADMIN) $isEditor = 1;
?>
<html>
	<head>
		<title><?php echo $DEFAULT_TITLE; ?> Portal Index Control Panel</title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			function validateHandshakeForm(f){
				if(f.remotePath.value == ""){
					alert("Enter URL to remote portal base index page");
					return false;
				}
				return true;
			}
		</script>
		<style type="text/css">
			fieldset{ margin:20px; padding:15px; }
			legend{ font-weight: bold; }
			label{  }
			button{ margin: 20px; }
			hr{ margin-top: 15px; margin-bottom: 15px; }
			.field-row{  }
		</style>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt;
			<b><a href="portalindex.php">Portal Index Control Panel</a></b>
		</div>
		<div id="innertext">
			<?php
			if(!isset($GLOBALS['ACTIVATE_PORTAL_INDEX'])){
				echo 'This feature has not yet been activated within this portal';
			}
			elseif($isEditor){
				if($formSubmit && $formSubmit != 'listCollections'){
					echo '<fieldset>';
					echo '<legend>Action Panel</legend>';
					if($formSubmit == 'importProfile'){
						if($collid = $portalManager->importProfile($portalID, $remoteID)) echo '<div><a href="../collections/misc/collprofiles.php?collid='.$collid.'" target="_blank">New snapshot collection created</a></div>';
						else echo '<div>failed to insert new collections: '.$portalManager->getErrorMessage().'</div>';
					}
					elseif($formSubmit == 'initiateHandshake'){
						if($resArr = $portalManager->initiateHandshake($remotePath)){
							if($resArr['status']) echo '<div>Success - handshake successful: '.$resArr['message'].'</div>';
							else echo '<div>ERROR - handshake failed: '.$resArr['message'].'</div>';
							//print_r($resArr);
						}
						else echo '<div>ERROR initiating handshake: '.$portalManager->getErrorMessage().'</div>';
					}
					echo '</fieldset>';
				}
				$selfArr = $portalManager->getSelfDetails();
				if($formSubmit != 'listCollections'){
					?>
					<fieldset>
						<legend>Current Portal Details</legend>
						<div class="field-row"><label>Portal title:</label> <?php echo $selfArr['portalName']; ?></div>
						<div class="field-row"><label>Root URL:</label> <?php echo $selfArr['urlRoot']; ?></div>
						<div class="field-row"><label>Global Unique Identifier:</label> <?php echo $selfArr['guid']; ?></div>
						<div class="field-row"><label>Manager email:</label> <?php echo $selfArr['managerEmail']; ?></div>
						<div class="field-row"><label>Software version:</label> <?php echo $selfArr['symbiotaVersion']; ?></div>
						<hr />
						<div class="handshake-div"><a href="#" onclick="$('.handshake-div').toggle(); return false;">Initiate Handshake with External Portal</a></div>
						<div class="handshake-div" style="display:none">
							<form action="portalindex.php" method="post" onsubmit="return validateHandshakeForm(this)">
								<div class="field-row"><label>Path to Remote Portal:</label> <input name="remotePath" type="text" value="<?php echo $remotePath; ?>" style="width: 500px" /></div>
								<div class="field-row"><button name="formsubmit" type="submit" value="initiateHandshake">Initiate Handshake</button></div>
							</form>
						</div>
					</fieldset>
					<?php
				}
				?>
				<fieldset>
					<legend>Portal Index</legend>
					<?php
					$indexArr = $portalManager->getPortalIndexArr($portalID);
					foreach($indexArr as $portalID => $portalArr){
						foreach($portalArr as $fieldName => $fieldValue){
							if($fieldValue){
								echo '<div><label>'.$fieldName.'</label>: ';
								$href = '';
								if($fieldName=='urlRoot') $href = $fieldValue;
								elseif($fieldName=='guid') $href = $portalArr['urlRoot'].'/api/v2/installation/ping';
								if($href) echo '<a href="'.$href.'" target="_blank">';
								echo $fieldValue;
								if($href) echo '</a>';
								echo '</div>';
							}
						}
						if($remoteID){
							$collectArr = $portalManager->getCollectionList($portalArr['urlRoot'], $remoteID);
							echo '<fieldset>';
							echo '<legend>Remote Collection #'.$remoteID.'</legend>';
							$remoteCollid = $collectArr['collID'];
							unset($collectArr['collID']);
							unset($collectArr['iid']);
							$internalArr = $collectArr['internal'];
							unset($collectArr['internal']);
							foreach($collectArr as $fName => $fValue){
								if($fValue){
									if($fName == 'fullDescription') $fValue = htmlentities($fValue);
									echo '<div><label>'.$fName.'</label>: '.$fValue.'</div>';
								}
							}
							$remoteUrl = $portalArr['urlRoot'].'/collections/misc/collprofiles.php?collid='.$remoteCollid;
							echo '<div><label>Remote collection</label>: <a href="'.$remoteUrl.'" target="_blank">'.$remoteUrl.'</a></div>';
							if($internalArr){
								echo '<fieldset>';
								echo '<legend>Internally Mapped Snapshot Collection</legend>';
								foreach($internalArr as $collid => $intArr){
									echo '<div><label>Management Type</label>: '.$intArr['managementType'].'</div>';
									echo '<div><label>Specimen count</label>: '.number_format($intArr['recordCnt']).'</div>';
									echo '<div><label>Refresh date</label>: '.$intArr['uploadDate'].'</div>';
									$internalUrl = $CLIENT_ROOT.'/collections/misc/collprofiles.php?collid='.$collid;
									echo '<div><label>Internal collection</label>: <a href="'.$internalUrl.'" target="_blank">'.$internalUrl.'</a></div>';
									if($importProfile = $portalManager->getDataImportProfile($collid)){
										foreach($importProfile as $uspid => $profileArr){
											echo '<hr/>';
											echo '<div style="margin:10px 5px">';
											echo '<div><label>Title</label>: '.$profileArr['title'].'</div>';
											echo '<div><label>Path</label>: '.$profileArr['path'].'</div>';
											echo '<div><label>Query string</label>: '.$profileArr['queryStr'].'</div>';
											echo '<div><label>Stored procedure (cleaning)</label>: '.$profileArr['cleanUpSp'].'</div>';
											echo '<div>Display all <a href="../collections/admin/specuploadmanagement.php?collid='.$collid.'" target="_blank">Import Profiles</a></div>';
											echo '<div>Initiate <a href="../collections/admin/specuploadmap.php?uploadtype=13&uspid='.$uspid.'&collid='.$collid.'" target="_blank">Data Import</a></div>';
											echo '</div>';
										}
									}
								}
								echo '</fieldset>';
							}
							else{
								?>
								<div style="margin: 0px 30px">
									<form name="collPubForm" method="post" action="portalindex.php">
										<input name="portalid" type="hidden" value="<?php echo $portalID; ?>" />
										<input name="remoteid" type="hidden" value="<?php echo $remoteID; ?>" />
										<button name="formsubmit" type="submit" value="importProfile">Create Internal Snapshot Profile</button>
									</form>
								</div>
								<?php
							}
							echo '</fieldset>';
						}
						elseif($formSubmit == 'listCollections'){
							if($collList = $portalManager->getCollectionList($portalArr['urlRoot'])){
								echo '<div><label>Collection Count</label>: '.count($collList).'</div>';
								echo '<table class="styledtable">';
								echo '<tr><th>ID</th><th>Institution Code</th><th>Collection Code</th><th>Collection Name</th><th>Dataset Type</th><th>Management</th><th>Mapped Internally</th></tr>';
								foreach($collList as $collArr){
									echo '<tr>';
									echo '<td><a href="portalindex.php?portalid='.$portalID.'&remoteid='.$collArr['collID'].'">'.$collArr['collID'].'</a></td>';
									echo '<td>'.$collArr['institutionCode'].'</td>';
									echo '<td>'.$collArr['collectionCode'].'</td>';
									echo '<td>'.$collArr['collectionName'].'</td>';
									echo '<td>'.$collArr['collType'].'</td>';
									echo '<td>'.$collArr['managementType'].'</td>';
									if(isset($collArr['internal']) && $collArr['internal'])
										$internal = '<a href="'.$CLIENT_ROOT.'/collections/misc/collprofiles.php?collid='.key($collArr['internal']).'" target="_blank">Yes</a>';
									else $internal = 'No';
									echo '<td>'.$internal.'</td>';
									echo '</tr>';
								}
								echo '</table>';
							}
						}
						else{
							?>
							<div style="margin:15px;">
								<form name="portalActionForm" method="post" action="portalindex.php">
									<input name="portalid" type="hidden" value="<?php echo $portalID; ?>" />
									<button name="formsubmit" type="submit" value="listCollections">List Collections</button>
								</form>
							</div>
							<?php
						}
						echo '<hr/>';
					}
					if(!$indexArr) echo '<div>Portal Index empty. No portals have yet been registered.</div>';
					?>
				</fieldset>
				<?php
			}
			else echo '<h2>ERROR: access denied</h2>';
			?>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>