<?php
include_once($SERVER_ROOT.'/content/lang/collections/misc/collprofiles.'.$LANG_TAG.'.php');

$statDisplay = array_key_exists('stat',$_REQUEST)?$_REQUEST['stat']:'';

if($statDisplay == 'geography'){
	$countryDist = array_key_exists('country',$_REQUEST) ? $collManager->cleanOutStr($_REQUEST['country']) : '';
	$stateDist = array_key_exists('state',$_REQUEST) ? $collManager->cleanOutStr($_REQUEST['state']) : '';
	$distArr = $collManager->getGeographyStats($countryDist, $stateDist);
	if($distArr){
		?>
		<fieldset id="geographystats" style="margin:20px;width:90%;">
			<legend>
				<b>
					<?php
					echo (isset($LANG['GEO_DIST'])?$LANG['GEO_DIST']:'Geographic Distribution');
					if($stateDist) echo ' - '.$stateDist;
					elseif($countryDist) echo ' - '.$countryDist;
					?>
				</b>
			</legend>
			<div style="margin:15px;"><?php echo (isset($LANG['CLICK_ON_SPEC_REC'])?$LANG['CLICK_ON_SPEC_REC']:'Click on the specimen record counts within the parenthesis to return the records for that term'); ?></div>
			<ul>
				<?php
				foreach($distArr as $term => $cnt){
					$countryTerm = ($countryDist?$countryDist:$term);
					$stateTerm = ($countryDist?($stateDist?$stateDist:$term):'');
					$countyTerm = ($countryDist && $stateDist?$term:'');
					echo '<li>';
					if(!$stateDist) echo '<a href="collprofiles.php?collid=' .htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&stat=geography&country=' . htmlspecialchars($countryTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&state=' . htmlspecialchars($stateTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '#geographystats">';
					echo $term;
					if(!$stateDist) echo '</a>';
					echo ' (<a href="../list.php?db=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&reset=1&country=' . htmlspecialchars($countryTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&state=' . htmlspecialchars($stateTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&county=' . htmlspecialchars($countyTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($cnt, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>)';
					echo '</li>';
				}
				?>
			</ul>
		</fieldset>
		<?php
	}
}
elseif($statDisplay == 'taxonomy'){
	$famDist = array_key_exists('family', $_REQUEST) ? $collManager->cleanOutStr($_REQUEST['family']) : '';
	$taxArr = $collManager->getTaxonomyStats($famDist);
	?>
	<fieldset id="taxonomystats" style="margin:20px;width:90%;">
		<legend><b><?php echo (isset($LANG['TAXON_DIST'])?$LANG['TAXON_DIST']:'Taxon Distribution'); ?></b></legend>
		<div style="margin:15px;float:left;">
			<?php echo (isset($LANG['TAXON_DIST'])?$LANG['TAXON_DIST']:'Click on the specimen record counts within the parenthesis to return the records for that family'); ?>
		</div>
		<div style="clear:both;">
			<ul>
				<?php
				foreach($taxArr as $name => $cnt){
					echo '<li>';
					if(!$famDist) echo '<a href="collprofiles.php?collid=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&stat=taxonomy&family=' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '#taxonomystats">';
					echo $name;
					if(!$famDist) echo '</a>';
					echo ' (<a href="../list.php?db=' . htmlspecialchars($collid, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&taxontype=' . htmlspecialchars(($famDist?2:3), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&reset=1&taxa=' . htmlspecialchars($name, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($cnt, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>)';
					echo '</li>';
				}
				?>
			</ul>
		</div>
	</fieldset>
	<?php
}