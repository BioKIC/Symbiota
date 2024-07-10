<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
if($LANG_TAG != 'en' && !file_exists($SERVER_ROOT . '/content/lang/profile/occurrencemenu.' . $LANG_TAG . '.php')) $LANG_TAG = 'en';
include_once($SERVER_ROOT . '/content/lang/profile/occurrencemenu.' . $LANG_TAG . '.php');
header('Content-Type: text/html; charset=' . $CHARSET);
unset($_SESSION['editorquery']);

$specHandler = new ProfileManager();
$specHandler->setUid($SYMB_UID);

$genArr = array();
$cArr = array();
$oArr = array();
$collArr = $specHandler->getCollectionArr();
foreach($collArr as $id => $collectionArr){
	if($collectionArr['colltype'] == 'General Observations') $genArr[$id] = $collectionArr;
	elseif($collectionArr['colltype'] == 'Preserved Specimens') $cArr[$id] = $collectionArr;
	elseif($collectionArr['colltype'] == 'Observations') $oArr[$id] = $collectionArr;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['OCCURRENCE_MENU'];?></title>	
		<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/main.css" type="text/css" rel="stylesheet">
		<style>
         .screen-reader-only{ 
            position: absolute;
            left: -10000px;
         }
      </style>
</head>
	<div style="margin:10px;">
		<h1 class="page-heading screen-reader-only">Occurrence Menu</h1>
	<?php
	if($SYMB_UID){
		if(!$collArr) echo '<div style="margin:40px 15px;font-weight:bold">' . $LANG['NO_PROJECTS'] . '</div>';
		foreach($genArr as $collId => $secArr){
			$cName = $secArr['collectionname'] . ' (' . $secArr['institutioncode'] . ($secArr['collectioncode']?'-' . $secArr['collectioncode']:'') . ')';
			?>
			<section class="fieldset-like">
				<h2>
					<span>
						<?php echo $cName; ?>
					</span>
				</h2>
				<div style="margin-left:10px">
					<?php
					echo $LANG['TOTAL_RECORDS'] . ': ' . $specHandler->getPersonalOccurrenceCount($collId);
					?>
				</div>
				<ul>
					<li>
						<a href="../collections/editor/occurrencetabledisplay.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['DISPLAY_ALL'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<li>
						<a href="../collections/editor/occurrencetabledisplay.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>&displayquery=1">
							<?php echo htmlspecialchars($LANG['SEARCH_RECORDS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<li>
						<a href="../collections/editor/occurrenceeditor.php?gotomode=1&collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['ADD_RECORD'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<li>
						<a href="../collections/reports/labelmanager.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['PRINT_LABELS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<li>
						<a href="../collections/reports/annotationmanager.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['PRINT_ANNOTATIONS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<li>
						<a href="../collections/editor/observationsubmit.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['SUBMIT_OBSERVATION'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<li>
						<a href="../collections/editor/editreviewer.php?display=1&collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['REVIEW_EDITS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<?php
					if (!empty($ACTIVATE_DUPLICATES)) {
						?>
						<li>
							<a href="../collections/datasets/duplicatemanager.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
								<?php echo htmlspecialchars($LANG['DUP_CLUSTER'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
							</a>
						</li>
						<?php
					}
					?>
					<li>
						<a href="#" onclick="newWindow = window.open('personalspecbackup.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>','bucollid','scrollbars=1,toolbar=0,resizable=1,width=400,height=200,left=20,top=20');">
							<?php echo htmlspecialchars($LANG['DOWNLOAD_BACKUP'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					<li>
						<a href="../collections/misc/commentlist.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['VIEW_COMMENTS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
						<?php if($commCnt = $specHandler->unreviewedCommentsExist($collId)) echo '- <span style="color:orange">' . $commCnt . ' ' . $LANG['UNREVIEWED'] . '</span>'; ?>
					</li>
					<!--
					<li>
						<a href="../collections/cleaning/index.php?collid=<?php echo htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
							<?php echo htmlspecialchars($LANG['DATA_CLEANING'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
						</a>
					</li>
					-->
				</ul>
			</section>
			<?php
		}
		if($cArr){
			?>
			<section class="fieldset-like">
				<h2>
					<span>
						<?php echo $LANG['COL_MANAGE']; ?>
					</span>
				</h2>
				<ul>
					<?php
					foreach($cArr as $collId => $secArr){
						$cName = $secArr['collectionname'] . ' (' . $secArr['institutioncode'] . ($secArr['collectioncode'] ? '-' . $secArr['collectioncode'] : '') . ')';
						echo '<li><a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($cName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
					}
					?>
				</ul>
			</section>
			<?php
		}
		if($oArr){
			?>
			<section class="fieldset-like">
				<h2><span><?php echo $LANG['OBS_MANAGEMENT'] ?></span></h2>
				<ul>
					<?php
					foreach($oArr as $collId => $secArr){
						$cName = $secArr['collectionname'] . ' (' . $secArr['institutioncode'] . ($secArr['collectioncode'] ? '-' . $secArr['collectioncode'] : '') . ')';
						echo '<li><a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($collId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($cName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
					}
					?>
				</ul>
			</section>
			<?php
		}
		$genAdminArr = array();
		if($genArr && isset($USER_RIGHTS['CollAdmin'])){
			$genAdminArr = array_intersect_key($genArr,array_flip($USER_RIGHTS['CollAdmin']));
			if($genAdminArr){
				?>
				<section class="fieldset-like">
					<h2><span><?php echo $LANG['GEN_OBS_ADMIN'] ?></span></h2>
					<ul>
						<?php
						foreach($genAdminArr as $id => $secArr){
							$cName = $secArr['collectionname'] . ' (' . $secArr['institutioncode'] . ($secArr['collectioncode'] ? '-' . $secArr['collectioncode'] : '') . ')';
							echo '<li><a href="../collections/misc/collprofiles.php?collid=' . htmlspecialchars($id, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&emode=1">' . htmlspecialchars($cName, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a></li>';
						}
						?>
					</ul>
				</section>
				<?php
			}
		}
		?>
		<section class="fieldset-like">
			<h2><span><?php echo $LANG['MISC_TOOLS'] ?></span></h2>
			<ul>
				<li><a href="../collections/datasets/index.php"><?php echo htmlspecialchars($LANG['DATASET_MANAGEMENT'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
				<?php
				if((count($cArr)+count($oArr)) > 1){
					?>
					<li><a href="../collections/georef/batchgeoreftool.php"><?php echo htmlspecialchars($LANG['CROSS_COL_GEOREF'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
					<?php
					if(isset($USER_RIGHTS['CollAdmin']) && count(array_diff($USER_RIGHTS['CollAdmin'],array_keys($genAdminArr))) > 1){
						?>
						<li><a href="../collections/cleaning/taxonomycleaner.php"><?php echo htmlspecialchars($LANG['CROSS_COL_TAXON'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a></li>
						<?php
					}
				}
				?>
			</ul>
		</section>
		<?php
	}
	?>
	</div>
</html>
