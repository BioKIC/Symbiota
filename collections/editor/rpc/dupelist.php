<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/collections/editor/rpc/editor_rpc.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/editor/rpc/editor_rpc.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/editor/rpc/editor_rpc.en.php');


$recordedBy = array_key_exists('recordedby',$_REQUEST)?trim(urldecode($_REQUEST['recordedby'])):'';
$recordNumber = array_key_exists('recordnumber',$_REQUEST)?trim($_REQUEST['recordnumber']):'';
$eventDate = array_key_exists('eventdate',$_REQUEST)?trim($_REQUEST['eventdate']):'';
$catNum = array_key_exists('catnum',$_POST)?trim($_POST['catnum']):'';
$queryOccid = array_key_exists('occid',$_POST)?$_POST['occid']:'';
$currentOccid = array_key_exists('curoccid',$_REQUEST)?$_REQUEST['curoccid']:'';
$dupeOccid = array_key_exists('dupeoccid',$_POST)?$_POST['dupeoccid']:'';
$dupeTitle = array_key_exists('dupetitle',$_POST)?$_POST['dupetitle']:'';
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$dupeManager = new OccurrenceDuplicate();
$dupArr = $dupeManager->getDupeList($recordedBy, $recordNumber, $eventDate, $catNum, $queryOccid, $currentOccid);

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> <?php echo $LANG['DUPLICATE_LINKER'] ?></title>
	<script>
		<?php 
		if($action == 'Link as Duplicate'){
			$dupeManager->linkDuplicates($currentOccid,$dupeOccid,$dupeTitle);
			echo 'window.opener.document.getElementById("dupeRefreshForm").submit();';
			echo 'self.close();';
		}
		?>
		
		function validateDupeForm(f){


			return true;
		}

		function openIndWindow(occid){
			$url = "../../individual/index.php?occid="+occid;
			indWindow=open($url,"indlist","resizable=1,scrollbars=1,toolbar=0,width=1000,height=800,left=100,top=100");
		}
	</script>
</head>
<body>
	<!-- inner text -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['DUPLICATE_LINKER']; ?></h1>
		<fieldset style="padding:15px;">
			<legend><b><?php echo $LANG['LINK_NEW_SPECIMEN'] ?></b></legend>
			<form name="adddupform" method="post" action="dupelist.php" onsubmit="return validateDupeForm(this)">
				<div style="margin:3px;">
					<b><?php echo $LANG['LAST_NAME'] ?></b>
					<input name="recordedby" type="text" value="<?php echo $recordedBy; ?>" />
				</div>
				<div style="margin:3px;">
					<b><?php echo $LANG['NUMBER'] ?></b>
					<input name="recordnumber" type="text" value="<?php echo $recordNumber; ?>" />
				</div>
				<div style="margin:3px;">
					<b><?php echo $LANG['DATE'] ?></b>
					<input name="eventdate" type="text" value="<?php echo $eventDate; ?>" />
				</div>
				<div style="margin:3px;">
					<b><?php echo $LANG['CATALOG_NUMBER'] ?></b>
					<input name="catnum" type="text" value="" />
				</div>
				<div style="margin:3px;">
					<b><?php echo $LANG['OCCID'] ?></b>
					<input name="occid" type="text" value="" />
 				</div>
				<div style="margin:20px;">
					<input name="curoccid" type="hidden" value="<?php echo $currentOccid; ?>" />
					<button name="" type="submit" value="Search for Duplicates"><?php echo $LANG['SEARCH_DUPLICATES'] ?></button>
 				</div>
			</form>
		</fieldset>
		<fieldset>
			<legend><b><?php echo $LANG['POSSIBLE_DUPLICATES'] ?></b></legend>
			<?php 
			if($dupArr){
				foreach($dupArr as $dupOccid => $occArr){
					?>
					<div style="margin:30px 10px">
						<div>
							<?php 
							echo $occArr['collname'];
							?>
						</div>
						<div>
							<?php 
							echo $occArr['recordedby'] . ' ' . $occArr['recordnumber'] . ' <span style="margin-left:15px">' . $occArr['eventdate'];
							if($occArr['verbatimeventdate']) echo ' (' . $occArr['verbatimeventdate'] . ')';
							echo '</span>';
							echo '<span style="margin-left:50px">' . $occArr['catalognumber'] . '</span>';
							?>
						</div>
						<div>
							<?php 
							echo trim($occArr['country'] . ', ' . $occArr['stateprovince'] . ', ' . $occArr['county'] . ', ' . $occArr['locality'],' ,');
							?>
						</div>
						<div>
							<a href="#" onclick="openIndWindow(<?php echo $dupOccid; ?>)"><?php echo $LANG['MORE_DETAILS'] ?></a>
						</div>
						<div style="margin:5px 0px 20px 15px;">
							<form action="dupelist.php" method="post">
								<input name="curoccid" type="hidden" value="<?php echo $currentOccid; ?>" />
								<input name="dupeoccid" type="hidden" value="<?php echo $dupOccid; ?>" />
								<input name="dupetitle" type="hidden" value="<?php echo $occArr['recordedby'] . ' ' . $occArr['recordnumber'] . ' ' . $occArr['eventdate']; ?>"  />
								<button name="submitaction" type="submit" value="Link as Duplicate"><?php echo $LANG['LINK_AS_DUPLICATE'] ?></button>
							</form>
						</div>
					</div>
					<?php
				}
			}
			else{
				echo '<div style="margin:20px;font-weight:bold">' . $LANG['NO_SPECIMENS_FOUND'] . '</div>';
			}
			?>
		</fieldset>
	</div>
</body>
</html>