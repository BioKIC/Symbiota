<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceIndividual.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
include_once($SERVER_ROOT.'/classes/RdfUtility.php');
include_once($SERVER_ROOT.'/content/lang/collections/individual/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/content/lang/collections/fieldterms/materialSampleVars.'.$LANG_TAG.'.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$occid = array_key_exists('occid', $_REQUEST) ? filter_var($_REQUEST['occid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$collid = array_key_exists('collid', $_REQUEST) ? filter_var($_REQUEST['collid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$pk = array_key_exists('pk', $_REQUEST) ? filter_var($_REQUEST['pk'], FILTER_SANITIZE_STRING):'';
$guid = array_key_exists('guid', $_REQUEST) ? filter_var($_REQUEST['guid'], FILTER_SANITIZE_STRING) : '';
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? filter_var($_REQUEST['tabindex'], FILTER_SANITIZE_NUMBER_INT) : 0;
$clid = array_key_exists('clid', $_REQUEST) ? filter_var($_REQUEST['clid'], FILTER_SANITIZE_NUMBER_INT) : 0;
$format = isset($_GET['format']) ? filter_var($_GET['format'], FILTER_SANITIZE_STRING) : '';
$submit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$indManager = new OccurrenceIndividual($submit?'write':'readonly');
if($occid) $indManager->setOccid($occid);
elseif($guid) $occid = $indManager->setGuid($guid);
elseif($collid && $pk){
	$indManager->setCollid($collid);
	$indManager->setDbpk($pk);
}

$indManager->setDisplayFormat($format);
$indManager->setOccurData();
if(!$occid) $occid = $indManager->getOccid();
if(!$collid) $collid = $indManager->getCollid();

$isSecuredReader = false;
$isEditor = false;
if($SYMB_UID){
	//Check editing status
	if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
		$isEditor = true;
	}
	elseif((array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor']))){
		$isEditor = true;
	}
	elseif(isset($occArr['observeruid']) && $occArr['observeruid'] == $SYMB_UID){
		$isEditor = true;
	}
	elseif($indManager->isTaxonomicEditor()){
		$isEditor = true;
	}
	//Check locality security
	if($isEditor || array_key_exists('RareSppAdmin',$USER_RIGHTS) || array_key_exists('RareSppReadAll',$USER_RIGHTS)){
		$isSecuredReader = true;
	}
	elseif(isset($USER_RIGHTS['RareSppReader']) && in_array($collid,$USER_RIGHTS['RareSppReader'])){
		$isSecuredReader = true;
	}
	elseif(isset($USER_RIGHTS['CollAdmin'])){
		$isSecuredReader = true;
	}
	elseif(isset($USER_RIGHTS['CollEditor']) && in_array($collid,$USER_RIGHTS['CollEditor'])){
		$isSecuredReader = true;
	}
}
$indManager->applyProtections($isSecuredReader);
$occArr = $indManager->getOccData();
$collMetadata = $indManager->getMetadata();
$genticArr = $indManager->getGeneticArr();

$statusStr = '';
//  If other than HTML was requested, return just that content.
if(isset($_SERVER['HTTP_ACCEPT'])){
	$accept = RdfUtility::parseHTTPAcceptHeader($_SERVER['HTTP_ACCEPT']);
	foreach($accept as $key => $mediarange){
		if($mediarange=='text/turtle' || $format == 'turtle') {
			Header("Content-Type: text/turtle; charset=".$CHARSET);
			$dwcManager = new DwcArchiverCore();
			$dwcManager->setCustomWhereSql(" o.occid = $occid ");
			echo $dwcManager->getAsTurtle();
			die;
		}
		elseif($mediarange=='application/rdf+xml' || $format == 'rdf') {
			Header("Content-Type: application/rdf+xml; charset=".$CHARSET);
			$dwcManager = new DwcArchiverCore();
			$dwcManager->setCustomWhereSql(" o.occid = $occid ");
			echo $dwcManager->getAsRdfXml();
			die;
		}
		elseif($mediarange=='application/json' || $format == 'json') {
			Header("Content-Type: application/json; charset=".$CHARSET);
			$dwcManager = new DwcArchiverCore();
			$dwcManager->setCustomWhereSql(" o.occid = $occid ");
			echo $dwcManager->getAsJson();
			die;
		}
	}
}

if($SYMB_UID){
	//Form action submitted
	if(array_key_exists('delvouch',$_GET)){
		if(!$indManager->deleteVoucher($_GET['delvouch'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	if(array_key_exists('commentstr',$_POST)){
		if(!$indManager->addComment($_POST['commentstr'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif($submit == 'deleteComment'){
		if(!$indManager->deleteComment($_POST['comid'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif(array_key_exists('repcomid',$_GET)){
		if($indManager->reportComment($_GET['repcomid'])){
			$statusStr = (isset($LANG['FLAGGEDCOMMENT'])?$LANG['FLAGGEDCOMMENT']:'Comment reported as inappropriate. Comment will remain unavailable to public until reviewed by an administrator.');
		}
		else{
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif(array_key_exists('publiccomid',$_GET)){
		if(!$indManager->makeCommentPublic($_GET['publiccomid'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif($submit == 'Add Voucher'){
		if(!$indManager->linkVoucher($_POST)){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	if($isEditor){
		if($submit == 'restoreRecord'){
			if($indManager->restoreRecord($occid)){
				$occArr = $indManager->getOccData();
				$collMetadata = $indManager->getMetadata();
			}
			else $statusStr = $indManager->getErrorMessage();
		}
	}
}

$displayMap = false;
if($occArr && is_numeric($occArr['decimallatitude']) && is_numeric($occArr['decimallongitude'])) $displayMap = true;
$dupClusterArr = $indManager->getDuplicateArr();
$commentArr = $indManager->getCommentArr($isEditor);
$traitArr = $indManager->getTraitArr();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE.' '.(isset($LANG['DETAILEDCOLREC'])?$LANG['DETAILEDCOLREC']:'Detailed Collection Record Information'); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<meta name="description" content="<?php echo 'Occurrence author: '.($occArr?$occArr['recordedby'].','.$occArr['recordnumber']:''); ?>" />
	<meta name="keywords" content="<?php echo (!empty($occArr['occurrenceid'])?$occArr['occurrenceid']:'').', '.(!empty($occArr['recordid'])?$occArr['recordid']:''); ?>" />
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<link href="<?php echo $CSS_BASE_PATH; ?>/symbiota/collections/individual/index.css?ver=1" type="text/css" rel="stylesheet" >
	<link href="<?php echo $CSS_BASE_PATH; ?>/symbiota/collections/individual/popup.css" type="text/css" rel="stylesheet" >
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
		var map;
		var mapInit = false;

		$(document).ready(function() {
			$('#tabs-div').tabs({
				beforeActivate: function(event, ui) {
					if(document.getElementById("map_canvas") && ui.newTab.index() == 1 && !mapInit){
						mapInit = true;
						initializeMap();
					}
					return true;
				},
				active: tabIndex
			});
		});

		function refreshRecord(occid){
			$.ajax({
				method: "GET",
				url: "<?php echo $CLIENT_ROOT; ?>/api/v2/occurrence/"+occid+"/reharvest"
			})
			.done(function( response ) {
				if(response.status == 200){
					$("#dataStatus").val(response.dataStatus);
					$("#fieldsModified").val(JSON.stringify(response.fieldsModified));
					$("#sourceDateLastModified").val(response.sourceDateLastModified);
					alert("Record reharvested. Page will reload to refresh contents...");
					$("#refreshForm").submit();
				}
				else{
					alert("ERROR updating record: "+response.error);
				}
			});
		}

		function displayAllMaterialSamples(){
			$(".mat-sample-div").show();
			$("#mat-sample-more-div").hide();
		}

		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none") objDiv.style.display = "block";
				else objDiv.style.display = "none";
			}
			else{
				var divObjs = document.getElementsByTagName("div");
				for (i = 0; i < divObjs.length; i++) {
					var obj = divObjs[i];
					if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
						if(obj.style.display=="none") obj.style.display="inline";
						else obj.style.display="none";
					}
				}
			}
		}

		function verifyVoucherForm(f){
			var clTarget = f.elements["clid"].value;
			if(clTarget == "0"){
				window.alert("Please select a checklist");
				return false;
			}
			return true;
		}

		function verifyCommentForm(f){
			if(f.commentstr.value.replace(/^\s+|\s+$/g,"")) return true;
			alert("Please enter a comment");
			return false;
		}

		function openIndividual(target) {
			occWindow=open("index.php?occid="+target,"occdisplay","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
			if (occWindow.opener == null) occWindow.opener = self;
		}

		<?php
		if($displayMap){
			?>
			function initializeMap(){
				var mLatLng = new google.maps.LatLng(<?php echo $occArr['decimallatitude'].",".$occArr['decimallongitude']; ?>);
				var dmOptions = {
					zoom: 8,
					center: mLatLng,
					marker: mLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				};
				map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
				//Add marker
				var marker = new google.maps.Marker({
					position: mLatLng,
					map: map
				});
			}
			<?php
		}
		?>
	</script>
</head>
<body>
	<div id="fb-root"></div>
	<script>
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));

		window.twttr=(function(d,s,id){
			var js,fjs=d.getElementsByTagName(s)[0],t=window.twttr||{};
			if(d.getElementById(id))return;js=d.createElement(s);
			js.id=id;js.src="https://platform.twitter.com/widgets.js";
			fjs.parentNode.insertBefore(js,fjs);t._e=[];
			t.ready=function(f){t._e.push(f);};
			return t;
		}(document,"script","twitter-wjs"));
	</script>
	<!-- This is inner text! -->
	<div id="popup-innertext">
		<?php
		if($statusStr){
			$statusColor = 'green';
			if(strpos($statusStr, 'ERROR')) $statusColor = 'red';
			?>
			<hr />
			<div style="padding:15px;">
				<span style="color:<?php echo $statusColor; ?>;"><?php echo $statusStr; ?></span>
			</div>
			<hr />
			<?php
		}
		if($occArr){
			?>
			<div id="tabs-div">
				<ul>
					<li><a href="#occurtab"><span><?php echo (isset($LANG['DETAILS'])?$LANG['DETAILS']:'Details'); ?></span></a></li>
					<?php
					if($displayMap) echo '<li><a href="#maptab"><span>'.(isset($LANG['MAP'])?$LANG['MAP']:'Map').'</span></a></li>';
					if($genticArr) echo '<li><a href="#genetictab"><span>'.(isset($LANG['GENETIC'])?$LANG['GENETIC']:'Genetic').'</span></a></li>';
					if($dupClusterArr) echo '<li><a href="#dupestab-div"><span>'.(isset($LANG['DUPLICATES'])?$LANG['DUPLICATES']:'Duplicates').'</span></a></li>';
					?>
					<li><a href="#commenttab"><span><?php echo ($commentArr?count($commentArr).' ':''); echo (isset($LANG['COMMENTS'])?$LANG['COMMENTS']:'Comments'); ?></span></a></li>
					<li><a href="linkedresources.php?occid=<?php echo $occid.'&tid='.$occArr['tidinterpreted'].'&clid='.$clid.'&collid='.$collid; ?>"><span><?php echo $LANG['LINKED_RESOURCES']; ?></span></a></li>
					<?php
					if($traitArr) echo '<li><a href="#traittab"><span>'.(isset($LANG['TRAITS'])?$LANG['TRAITS']:'Traits').'</span></a></li>';
					if($isEditor) echo '<li><a href="#edittab"><span>'.$LANG['EDIT_HISTORY'].'</span></a></li>';
					?>
				</ul>
				<div id="occurtab">
					<div id="media-div">
						<div>
							<a class="twitter-share-button" href="https://twitter.com/share" data-url="<?php echo $_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/collections/individual/index.php?occid='.$occid.'&clid='.$clid; ?>"><?php echo (isset($LANG['TWEET'])?$LANG['TWEET']:'Tweet'); ?></a>
						</div>
						<div>
							<div class="fb-share-button" data-href="" data-layout="button_count"></div>
						</div>
					</div>
					<?php
					$iconUrl = (substr($collMetadata["icon"],0,6)=='images'?'../../':'').$collMetadata['icon'];
					if($iconUrl){
						?>
						<div id="collicon-div">
							<img src="<?php echo $iconUrl; ?>" />
						</div>
						<?php
					}
					$instCode = $collMetadata['institutioncode'];
					if($collMetadata['collectioncode']) $instCode .= ':'.$collMetadata['collectioncode'];
					?>
					<div class="title1-div">
						<?php echo $collMetadata['collectionname'].' ('.$instCode.')'; ?>
					</div>
					<div  id="occur-div">
						<?php
						if(array_key_exists('loan',$occArr)){
							?>
							<div id="loan-div" title="<?php echo 'Loan #'.$occArr['loan']['identifier']; ?>">
								<?php echo $LANG['ON_LOAN']; ?>
								<?php echo $occArr['loan']['code']; ?>
							</div>
							<?php
						}
						if(array_key_exists('relation',$occArr)){
							?>
							<fieldset id="association-div">
								<legend><?php echo (isset($LANG['RELATED_OCCUR'])?$LANG['RELATED_OCCUR']:'Related Occurrences'); ?></legend>
								<?php
								$displayLimit = 5;
								$cnt = 0;
								foreach($occArr['relation'] as $id => $assocArr){
									if($cnt == $displayLimit){
										echo '<div class="relation-hidden"><a href="#" onclick="$(\'.relation-hidden\').toggle();return false;">show all records</a></div>';
										echo '<div class="relation-hidden" style="display:none">';
									}
									echo '<div>';
									echo $assocArr['relationship'];
									if($assocArr['subtype']) echo ' ('.$assocArr['subtype'].')';
									echo ': ';
									$relID = $assocArr['identifier'];
									$relUrl = $assocArr['resourceurl'];
									if(!$relUrl && $assocArr['occidassoc']) $relUrl = $GLOBALS['CLIENT_ROOT'].'/collections/individual/index.php?occid='.$assocArr['occidassoc'];
									if($relUrl) $relID = '<a href="'.$relUrl.'">'.$relID.'</a>';
									if($relID) echo $relID;
									elseif($assocArr['sciname']) echo $assocArr['sciname'];
									echo '</div>';
									$cnt++;
								}
								if(count($occArr['relation']) > $displayLimit) echo '</div>';
								?>
							</fieldset>
							<?php
						}
						if($occArr['catalognumber']){
							?>
							<div id="cat-div">
								<?php
								echo '<label>'.(isset($LANG['CATALOG_NUMBER'])?$LANG['CATALOG_NUMBER']:'Catalog #').': </label>';
								echo $occArr['catalognumber'];
								?>
							</div>
							<?php
						}
						if($occArr['occurrenceid']){
							?>
							<div id="occurrenceid-div">
								<?php
								echo '<label>'.$LANG['OCCURRENCE_ID'].': </label>';
								$resolvableGuid = false;
								if(substr($occArr['occurrenceid'],0,4) == 'http') $resolvableGuid = true;
								if($resolvableGuid) echo '<a href="'.$occArr['occurrenceid'].'" target="_blank">';
								echo $occArr['occurrenceid'];
								if($resolvableGuid) echo '</a>';
								?>
							</div>
							<?php
						}
						if($occArr['othercatalognumbers']){
							$char = substr($occArr['othercatalognumbers'],0,1);
							if($char == '{' || $char == '['){
								$otherCatArr = json_decode($occArr['othercatalognumbers'],true);
								foreach($otherCatArr as $catTag => $catValueArr){
									if(!$catTag) $catTag = $LANG['OTHER_CATALOG_NUMBERS'];
									echo '<div class="assoccatnum-div"><label>'.$catTag.':</label> '.implode('; ', $catValueArr).'</div>';
								}
							}
							else{
								?>
								<div class="assoccatnum-div">
									<?php
									echo '<label>'.$LANG['OTHER_CATALOG_NUMBERS'].': </label>';
									echo $occArr['othercatalognumbers'];
									?>
								</div>
								<?php
							}
						}
						if($occArr['sciname']){
							?>
							<div class="sciname-div">
								<?php
								echo '<label>'.$LANG['TAXON'].':</label> ';
								echo '<i>'.$occArr['sciname'].'</i> '.$occArr['scientificnameauthorship'];
								if(isset($occArr['taxonsecure'])){
									echo '<span class="notice-span"> '.$LANG['ID_PROTECTED'].'</span>';
								}
								if($occArr['tidinterpreted']){
									//echo ' <a href="../../taxa/index.php?taxon='.$occArr['tidinterpreted'].'" title="Open Species Profile Page"><img src="" /></a>';
								}
								?>
							</div>
							<?php
							if($occArr['identificationqualifier']){
								echo '<div id="idqualifier-div"><label>'.$LANG['ID_QUALIFIER'].':</label> '.$occArr['identificationqualifier'].'</div>';
							}
						}
						if($occArr['family']) echo '<label>'.$LANG['FAMILY'].':</label> '.$occArr['family'];
						if($occArr['identifiedby']){
							?>
							<div class="identby-div">
								<?php
								echo '<label>'.(isset($LANG['DETERMINER'])?$LANG['DETERMINER']:'Determiner').': </label>'.$indManager->activateOrcidID($occArr['identifiedby']);
								if($occArr['dateidentified']) echo ' ('.$occArr['dateidentified'].')';
								?>
							</div>
							<?php
						}
						if($occArr['taxonremarks']){
							?>
							<div class="taxonremarks-div">
								<?php
								echo '<label>'.$LANG['TAXON_REMARKS'].': </label>';
								echo $occArr['taxonremarks'];
								?>
							</div>
							<?php
						}
						if($occArr['identificationreferences']){ ?>
							<div class="identref-div">
								<?php
								echo '<label>'.$LANG['ID_REFERENCES'].': </label>';
								echo $occArr['identificationreferences'];
								?>
							</div>
							<?php
						}
						if($occArr['identificationremarks']){
							?>
							<div class="identremarks-div">
								<?php
								echo '<label>'.$LANG['ID_REMARKS'].': </label>';
								echo $occArr['identificationremarks'];
								?>
							</div>
							<?php
						}
						if(array_key_exists('dets',$occArr) && (count($occArr['dets']) > 1 || $occArr['dets'][key($occArr['dets'])]['iscurrent'] == 0)){
							?>
							<div id="determination-div">
								<div class="det-toogle-div">
									<a href="#" onclick="toggle('det-toogle-div');return false"><img src="../../images/plus_sm.png"></a>
									<?php echo $LANG['SHOW_DET_HISTORY']; ?>
								</div>
								<div class="det-toogle-div" style="display:none;">
									<div>
										<a href="#" onclick="toggle('det-toogle-div');return false"><img src="../../images/minus_sm.png"></a>
										<?php echo $LANG['HIDE_DET_HISTORY']; ?>
									</div>
									<fieldset>
										<legend><?php echo $LANG['DET_HISTORY']; ?></legend>
										<?php
										$firstIsOut = false;
										$dArr = $occArr['dets'];
										foreach($dArr as $detArr){
											if($firstIsOut) echo '<hr />';
												$firstIsOut = true;
											?>
											<div style="margin:10px;">
												<?php
												if($detArr['qualifier']) echo $detArr['qualifier'];
												echo ' <label><i>'.$detArr['sciname'].'</i></label> '.$detArr['author'];
												?>
												<div class="identby-div">
													<?php
													echo '<label>'.(isset($LANG['DETERMINER'])?$LANG['DETERMINER']:'Determiner').': </label>';
													echo $detArr['identifiedby'];
													?>
												</div>
												<div class="identdate-div">
													<?php
													echo '<label>'.$LANG['DATE'].': </label>';
													echo $detArr['date'];
													?>
												</div>
												<?php
												if($detArr['ref']){ ?>
													<div class="identref-div">
														<?php
														echo '<label>'.$LANG['ID_REFERENCES'].': </label>';
														echo $detArr['ref'];
														?>
													</div>
													<?php
												}
												if($detArr['notes']){
													?>
													<div class="identremarks-div">
														<?php
														echo '<label>'.$LANG['ID_REMARKS'].': </label>';
														echo $detArr['notes'];
														?>
													</div>
													<?php
												}
												?>
											</div>
											<?php
										}
										?>
									</fieldset>
								</div>
							</div>
							<?php
						}
						if($occArr['typestatus']){ ?>
							<div id="typestatus-div">
								<?php
								echo '<label>'.$LANG['TYPE_STATUS'].': </label>';
								echo $occArr['typestatus'];
								?>
							</div>
							<?php
						}
						if($occArr['eventid']){
							?>
							<div id="eventid-div">
								<label><?php echo (isset($LANG['EVENTID'])?$LANG['EVENTID']:'Event ID'); ?>: </label>
								<?php
								echo $occArr['eventid'];
								?>
							</div>
							<?php
						}
						if($occArr['recordedby']){
							$recByLabel = (isset($LANG['OBSERVER'])?$LANG['OBSERVER']:'Observer');
							if($collMetadata['colltype'] == 'Preserved Specimens') $recByLabel = (isset($LANG['COLLECTOR'])?$LANG['COLLECTOR']:'Collector');
							?>
							<div id="recordedby-div">
								<label><?php echo $recByLabel; ?>: </label>
								<?php
								$recByStr = $indManager->activateOrcidID($occArr['recordedby']);
								echo $recByStr;
								?>
							</div>
							<?php
							if($occArr['recordnumber']){
								?>
								<div id="recordnumber-div">
									<label><?php echo (isset($LANG['NUMBER'])?$LANG['NUMBER']:'Number'); ?>: </label>
									<?php echo $occArr['recordnumber']; ?>
								</div>
								<?php
							}
						}
						if($occArr['eventdate']){
							echo '<div id="eventdate-div">';
							echo '<label>'.$LANG['DATE'].':</label> '.$occArr['eventdate'];
							if($occArr['eventdate2'] && $occArr['eventdate2'] != $occArr['eventdate']){
								echo ' - '.$occArr['eventdate2'];
							}
							elseif($occArr['eventdateend'] && $occArr['eventdateend'] != $occArr['eventdate']){
								echo ' - '.$occArr['eventdateend'];
							}
							echo '</div>';
						}
						if($occArr['verbatimeventdate']){
							echo '<div id="verbeventid-div"><label>'.$LANG['VERBATIM_DATE'].':</label> '.$occArr['verbatimeventdate'].'</div>';
						}
						if($occArr['associatedcollectors']){
							?>
							<div id="assoccollectors-div">
								<?php
								echo '<label>'.$LANG['ADDITIONAL_COLLECTORS'].': </label>';
								echo $occArr['associatedcollectors'];
								?>
							</div>
							<?php
						}
						$localityArr = array();
						if($occArr['country']) $localityArr[] = $occArr['country'];
						if($occArr['stateprovince']) $localityArr[] = $occArr['stateprovince'];
						if($occArr['county']) $localityArr[] = $occArr['county'];
						if($occArr['municipality']) $localityArr[] = $occArr['municipality'];
						?>
						<div id="locality-div">
							<?php
							echo '<label>'.(isset($LANG['LOCALITY'])?$LANG['LOCALITY']:'Locality').':</label> ';
							if(!isset($occArr['localsecure'])){
								$locStr = $occArr['locality'];
								if($occArr['locationid']) $locStr .= ' ['.(isset($LANG['LOCATION_ID'])?$LANG['LOCATION_ID']:'Location ID').': '.$occArr['locationid'].']';
								$localityArr[] = $locStr;
							}
							echo implode(', ', $localityArr);
							if($occArr['localitysecurity'] == 1){
								echo '<div style="margin-left:10px"><span class="notice-span">'.$LANG['LOCALITY_PROTECTED'].':<span> ';
								if($occArr['localitysecurityreason'] && substr($occArr['localitysecurityreason'],0,1) != '<') echo $occArr['localitysecurityreason'];
								else echo $LANG['PROTECTED_REASON'];
								if(!isset($occArr['localsecure'])) echo '<br/>'.(isset($LANG['ACCESS_GRANTED'])?$LANG['ACCESS_GRANTED']:'Current user has been granted access');
								echo '</div>';
							}
							?>
						</div>
						<?php
						if($occArr['decimallatitude']){
							?>
							<div id="latlng-div">
								<?php
								echo $occArr['decimallatitude'].'&nbsp;&nbsp;'.$occArr['decimallongitude'];
								if($occArr['coordinateuncertaintyinmeters']) echo ' +-'.$occArr['coordinateuncertaintyinmeters'].'m.';
								if($occArr['geodeticdatum']) echo '&nbsp;&nbsp;'.$occArr['geodeticdatum'];
								?>
							</div>
							<?php
						}
						if($occArr['verbatimcoordinates']){
							?>
							<div id="verbcoord-div">
								<?php
								echo '<label>'.$LANG['VERBATIM_COORDINATES'].': </label>';
								echo $occArr['verbatimcoordinates'];
								?>
							</div>
							<?php
						}
						if($occArr['locationremarks']){
							?>
							<div id="locremarks-div">
								<?php
								echo '<label>'.$LANG['LOCATION_REMARKS'].': </label>';
								echo $occArr['locationremarks'];
								?>
							</div>
							<?php
						}
						if($occArr['georeferenceremarks']){
							?>
							<div id="georefremarks-div">
								<?php
								echo '<label>'.$LANG['GEOREF_REMARKS'].': </label>';
								echo $occArr['georeferenceremarks'];
								?>
							</div>
							<?php
						}
						if($occArr['minimumelevationinmeters'] || $occArr['verbatimelevation']){
							?>
							<div id="elev-div">
								<?php
								echo '<label>' . $LANG['ELEVATION'] . ': </label>';
								echo $occArr['minimumelevationinmeters'];
								if($occArr['maximumelevationinmeters']) echo '-' . $occArr['maximumelevationinmeters'];
								echo ' '.$LANG['METERS'];
								if($occArr['verbatimelevation']){
									?>
									<span style="margin-left:20px">
										<label><?php echo $LANG['VERBATIM_ELEVATION']; ?>: </label>
										<?php echo $occArr['verbatimelevation']; ?>
									</span>
									<?php
								}
								else{
									echo ' ('.round($occArr['minimumelevationinmeters']*3.28).($occArr['maximumelevationinmeters']?'-'.round($occArr['maximumelevationinmeters']*3.28):'') . $LANG['FT'] . ')';
								}
								?>
							</div>
							<?php
						}
						if($occArr['minimumdepthinmeters'] || $occArr['verbatimdepth']){
							?>
							<div id="depth-div">
								<?php
								echo '<label>'.$LANG['DEPTH'].': </label>';
								echo $occArr['minimumdepthinmeters'];
								if($occArr['maximumdepthinmeters']) echo '-'.$occArr['maximumdepthinmeters'];
								echo ' '.$LANG['METERS'];
								if($occArr['verbatimdepth']){
									?>
									<span style="margin-left:20px">
										<?php
										echo '<label>'.$LANG['VERBATIM_DEPTH'].': </label>';
										echo $occArr['verbatimdepth'];
										?>
									</span>
									<?php
								}
								?>
							</div>
							<?php
						}
						if($occArr['informationwithheld']){
							?>
							<div id="infowithheld-div">
								<?php
								echo '<label>'.$LANG['INFO_WITHHELD'].': </label>';
								echo $occArr['informationwithheld'];
								?>
							</div>
							<?php
						}
						if($occArr['habitat']){
							?>
							<div id="habitat-div">
								<?php
								echo '<label>'.$LANG['HABITAT'].': </label>';
								echo $occArr['habitat'];
								?>
							</div>
							<?php
						}
						if($occArr['substrate']){
							?>
							<div id="substrate-div">
								<?php
								echo '<label>'.$LANG['SUBSTRATE'].': </label>';
								echo $occArr['substrate'];
								?>
							</div>
							<?php
						}
						if($occArr['associatedtaxa']){
							?>
							<div id="assoctaxa-div">
								<?php
								echo '<label>'.$LANG['ASSOCIATED_TAXA'].': </label>';
								echo $occArr['associatedtaxa'];
								?>
							</div>
							<?php
						}
						if($occArr['verbatimattributes']){
							?>
							<div id="attr-div">
								<?php
								echo '<label>'.$LANG['DESCRIPTION'].': </label>';
								echo $occArr['verbatimattributes'];
								?>
							</div>
							<?php
						}
						if($occArr['dynamicproperties']){
							?>
							<div id="dynprop-div">
								<?php
								echo '<label>'.$LANG['DYNAMIC_PROPERTIES'].': </label>';
								echo $occArr['dynamicproperties'];
								?>
							</div>
							<?php
						}
						if($occArr['reproductivecondition']){
							?>
							<div id="reproductive-div">
								<label><?php echo $LANG['REPRODUCTIVE_CONDITION']; ?>:</label>
								<?php echo $occArr['reproductivecondition']; ?>
							</div>
							<?php
						}
						if($occArr['lifestage']){
							?>
							<div id="lifestage-div">
								<?php
								echo '<label>'.$LANG['LIFE_STAGE'].': </label>';
								echo $occArr['lifestage'];
								?>
							</div>
							<?php
						}
						if($occArr['sex']){
							?>
							<div id="sex-div">
								<label><?php echo $LANG['SEX']; ?>:</label>
								<?php echo $occArr['sex']; ?>
							</div>
							<?php
						}
						if($occArr['individualcount']){
							?>
							<div id="indcnt-div">
								<label><?php echo $LANG['INDIVIDUAL_COUNT']; ?>:</label>
								<?php echo $occArr['individualcount']; ?>
							</div>
							<?php
						}
						if($occArr['samplingprotocol']){
							?>
							<div id="sampleprotocol-div">
								<label><?php echo $LANG['SAMPLE_PROTOCOL']; ?>:</label>
								<?php echo $occArr['samplingprotocol']; ?>
							</div>
							<?php
						}
						if($occArr['preparations']){
							?>
							<div id="preparations-div">
								<label><?php echo $LANG['PREPARATIONS']; ?>:</label>
								<?php echo $occArr['preparations']; ?>
							</div>
							<?php
						}
						$noteStr = '';
						if($occArr['occurrenceremarks']) $noteStr .= "; ".$occArr['occurrenceremarks'];
						if($occArr['establishmentmeans']) $noteStr .= "; ".$occArr['establishmentmeans'];
						if($occArr['cultivationstatus']) $noteStr .= "; Cultivated or Captive";
						if($noteStr){
							?>
							<div id="notes-div">
								<label><?php echo $LANG['NOTES']; ?>:</label>
								<?php echo substr($noteStr,2); ?>
							</div>
							<?php
						}
						if($occArr['disposition']){
							?>
							<div id="disposition-div">
								<label><?php echo $LANG['DISPOSITION']; ?>: </label>
								<?php echo $occArr['disposition']; ?>
							</div>
							<?php
						}
						if(isset($occArr['paleoid'])){
							?>
							<div id="paleo-div">
								<label><?php echo $LANG['PALEO_TERMS']; ?>: </label>
								<?php
								$paleoStr1 = '';
								if($occArr['eon']) $paleoStr1 .= '; '.$occArr['eon'];
								if($occArr['era']) $paleoStr1 .= '; '.$occArr['era'];
								if($occArr['period']) $paleoStr1 .= '; '.$occArr['period'];
								if($occArr['epoch']) $paleoStr1 .= '; '.$occArr['epoch'];
								if($occArr['stage']) $paleoStr1 .= '; '.$occArr['stage'];
								if($occArr['earlyinterval']) $paleoStr1 .= '; '.$occArr['earlyinterval'];
								if($occArr['lateinterval']) $paleoStr1 .= ' to '.$occArr['lateinterval'];
								if($paleoStr1) echo trim($paleoStr1,'; ');
								?>
								<div style="margin-left:10px">
									<?php
									if($occArr['absoluteage']) echo '<div class="paleofield-div"><label>'.$LANG['ABSOLUTE_AGE'].':</label> '.$occArr['absoluteage'].'</div>';
									if($occArr['storageage']) echo '<div class="paleofield-div"><label>'.$LANG['STORAGE_AGE'].':</label> '.$occArr['storageage'].'</div>';
									if($occArr['localstage']) echo '<div class="paleofield-div"><label>'.$LANG['LOCAL_STAGE'].':</label> '.$occArr['localstage'].'</div>';
									if($occArr['biota']) echo '<div class="paleofield-div"><label>'.$LANG['BIOTA'].':</label> '.$occArr['biota'].'</div>';
									if($occArr['biostratigraphy']) echo '<div class="paleofield-div"><label>'.$LANG['BIO_STRAT'].':</label> '.$occArr['biostratigraphy'].'</div>';
									if($occArr['lithogroup']) echo '<div class="paleofield-div"><label>'.(isset($LANG['GROUP'])?$LANG['GROUP']:'Group').':</label> '.$occArr['lithogroup'].'</div>';
									if($occArr['formation']) echo '<div class="paleofield-div"><label>'.$LANG['FORMATION'].':</label> '.$occArr['formation'].'</div>';
									if($occArr['taxonenvironment']) echo '<div class="paleofield-div"><label>'.$LANG['TAXON_ENVIR'].':</label> '.$occArr['taxonenvironment'].'</div>';
									if($occArr['member']) echo '<div class="paleofield-div"><label>'.$LANG['MEMBER'].':</label> '.$occArr['member'].'</div>';
									if($occArr['bed']) echo '<div class="paleofield-div"><label>'.$LANG['BED'].':</label> '.$occArr['bed'].'</div>';
									if($occArr['lithology']) echo '<div class="paleofield-div"><label>'.$LANG['LITHOLOGY'].':</label> '.$occArr['lithology'].'</div>';
									if($occArr['stratremarks']) echo '<div class="paleofield-div"><label>'.$LANG['STRAT_REMARKS'].':</label> '.$occArr['stratremarks'].'</div>';
									if($occArr['element']) echo '<div class="paleofield-div"><label>'.$LANG['ELEMENT'].':</label> '.$occArr['element'].'</div>';
									if($occArr['slideproperties']) echo '<div class="paleofield-div"><label>'.$LANG['SLIDE_PROPS'].':</label> '.$occArr['slideproperties'].'</div>';
									if($occArr['geologicalcontextid']) echo '<div class="paleofield-div"><label>'.$LANG['CONTEXT_ID'].':</label> '.$occArr['geologicalcontextid'].'</div>';
									?>
								</div>
							</div>
							<?php
						}
						if(isset($occArr['exs'])){
							?>
							<div id="exsiccati-div">
								<label><?php echo $LANG['EXCICCATI_SERIES']; ?>:</label>
								<?php
								echo '<a href="../exsiccati/index.php?omenid='.$occArr['exs']['omenid'].'" target="_blank">';
								echo $occArr['exs']['title'].'&nbsp;#'.$occArr['exs']['exsnumber'];
								echo '</a>';
								?>
							</div>
							<?php
						}
						if(array_key_exists('matSample',$occArr)){
							$matSampleArr = $occArr['matSample'];
							$msCnt = 0;
							$msKey = 0;
							echo '<fieldset><legend>'.$LANG['MATERIAL_SAMPLES'].'</legend>';
							do{
								if($msKey = key($matSampleArr)){
									echo '<div class="mat-sample-div" style="'.($msCnt?'display:none':'').'">';
									foreach($matSampleArr[$msKey] as $msLabelKey => $msValue){
										if($msValue && isset($MS_LABEL_ARR[$msLabelKey])) echo '<div><label>'.$MS_LABEL_ARR[$msLabelKey].'</label>: '.$msValue.'</div>';
									}
									echo '<hr>';
									echo '</div>';
									if(!$msCnt && count($matSampleArr) > 1){
										echo '<div id="mat-sample-more-div" >';
										echo '<a href="#" onclick="displayAllMaterialSamples();return false;">';
										echo $LANG['DISPLAY_ALL_MATERIAL_SAMPLES'];
										echo '</a></div>';
									}
								}
								$msCnt++;
							}while(next($matSampleArr));
							echo '</fieldset>';
						}
						if(array_key_exists('imgs',$occArr)){
							$iArr = $occArr['imgs'];
							?>
							<fieldset>
								<legend><?php echo $LANG['SPECIMEN_IMAGES']; ?></legend>
								<?php
								foreach($iArr as $imgArr){
									$thumbUrl = $imgArr['tnurl'];
									if(!$thumbUrl || substr($thumbUrl,0,7)=='process'){
										if($image = exif_thumbnail($imgArr['lgurl'])){
											$thumbUrl = 'data:image/jpeg;base64,'.base64_encode($image);
										}
										elseif($imgArr['url'] && substr($imgArr['url'],0,7)!='process') $thumbUrl = $imgArr['url'];
										else $thumbUrl = $imgArr['lgurl'];
									}
									?>
									<div class="thumbnail-div">
										<a href='<?php echo $imgArr['url']; ?>' target="_blank">
											<img border="1" src="<?php echo $thumbUrl; ?>" title="<?php echo $imgArr['caption']; ?>" style="max-width:170;" />
										</a>
										<?php
										if($imgArr['caption']) echo '<div><i>'.$imgArr['caption'].'</i></div>';
										if($imgArr['photographer']) echo '<div>'.(isset($LANG['AUTHOR'])?$LANG['AUTHOR']:'Author').': '.$imgArr['photographer'].'</div>';
										if($imgArr['url'] && substr($thumbUrl,0,7)!='process' && $imgArr['url'] != $imgArr['lgurl']) echo '<div><a href="'.$imgArr['url'].'" target="_blank">'.$LANG['OPEN_MEDIUM'].'</a></div>';
										if($imgArr['lgurl']) echo '<div><a href="'.$imgArr['lgurl'].'" target="_blank">'.$LANG['OPEN_LARGE'].'</a></div>';
										if($imgArr['sourceurl']) echo '<div><a href="'.$imgArr['sourceurl'].'" target="_blank">'.$LANG['OPEN_SOURCE'].'</a></div>';
										?>
									</div>
									<?php
								}
								?>
							</fieldset>
							<?php
						}
						//Rights
						$rightsStr = $collMetadata['rights'];
						if($collMetadata['rights']){
							$rightsHeading = '';
							if(isset($RIGHTS_TERMS)) $rightsHeading = array_search($rightsStr,$RIGHTS_TERMS);
							if(substr($collMetadata['rights'],0,4) == 'http'){
								$rightsStr = '<a href="'.$rightsStr.'" target="_blank">'.($rightsHeading?$rightsHeading:$rightsStr).'</a>';
							}
							$rightsStr = '<div style="margin-top:2px;"><label>'.$LANG['USAGE_RIGHTS'].':</label> '.$rightsStr.'</div>';
						}
						if($collMetadata['rightsholder']){
							$rightsStr .= '<div style="margin-top:2px;"><label>'.$LANG['RIGHTS_HOLDER'].':</label> '.$collMetadata['rightsholder'].'</div>';
						}
						if($collMetadata['accessrights']){
							$rightsStr .= '<div style="margin-top:2px;"><label>'.$LANG['ACCESS_RIGHTS'].':</label> '.$collMetadata['accessrights'].'</div>';
						}
						?>
						<div id="rights-div">
							<?php
							if($rightsStr) echo $rightsStr;
							else echo '<a href="../../includes/usagepolicy.php">'.$LANG['USAGE_POLICY'].'</a>';
							?>
						</div>
						<div style="margin:3px 0px;"><?php echo '<label>'.$LANG['RECORD_ID'].': </label>'.$occArr['recordid']; ?></div>
						<?php
						if(isset($occArr['source'])){
							$recordType = (isset($occArr['source']['type'])?$occArr['source']['type']:'');
							$displayTitle = $LANG['SOURCE_RECORD'];
							if(isset($occArr['source']['title'])) $displayTitle = $occArr['source']['title'];
							$displayStr = $occArr['source']['url'];
							if(isset($occArr['source']['displayStr'])) $displayStr = $occArr['source']['displayStr'];
							elseif(isset($occArr['source']['sourceID'])) $displayStr = '#'.$occArr['source']['sourceID'];
							if($recordType == 'symbiota') echo '<fieldset><legend>Externally Managed Snapshot Record</legend>';
							echo '<div><label>'.$displayTitle.':</label> <a href="'.$occArr['source']['url'].'" target="_blank">'.$displayStr.'</a></div>';
							echo '<div style="float:left;">';
							if(isset($occArr['source']['sourceName'])){
								echo '<div><label>'.$LANG['DATA_SOURCE'].':</label> '.$occArr['source']['sourceName'].'</div>';
								if($recordType == 'symbiota') echo '<div><label>Source management:</label> Live managed record within a Symbiota portal</div>';
							}
							if(array_key_exists('fieldsModified',$_POST)){
								echo '<div><label>'.$LANG['REFRESH_DATE'].':</label> '.(isset($occArr['source']['refreshTimestamp'])?$occArr['source']['refreshTimestamp']:'').'</div>';
								//Input from refersh event
								$dataStatus = filter_var($_POST['dataStatus'], FILTER_SANITIZE_STRING);
								$fieldsModified = filter_var($_POST['fieldsModified'], FILTER_SANITIZE_STRING);
								$sourceDateLastModified = filter_var($_POST['sourceDateLastModified'], FILTER_SANITIZE_STRING);
								echo '<div><label>'.$LANG['UPDATE_STATUS'].':</label> '.$dataStatus.'</div>';
								echo '<div><label>'.$LANG['FIELDS_MODIFIED'].':</label> '.$fieldsModified.'</div>';
								echo '<div><label>'.$LANG['SOURCE_DATE_LAST_MODIFIED'].':</label> '.$sourceDateLastModified.'</div>';
							}
							echo '</div>';
							if($SYMB_UID && $recordType == 'symbiota'){
								?>
								<div style="float:left;margin-left:30px;">
									<button name="formsubmit" type="submit" onclick="refreshRecord(<?php echo $occid; ?>)"><?php echo $LANG['REFRESH_RECORD']; ?></button>
								</div>
								<form id="refreshForm" action="index.php" method="post">
									<input id="dataStatus" name="dataStatus" type="hidden" value="" >
									<input id="fieldsModified" name="fieldsModified" type="hidden" value="" >
									<input id="sourceDateLastModified" name="sourceDateLastModified" type="hidden" value="" >
									<input name="occid" type="hidden" value="<?php echo $occid; ?>" >
									<input name="clid" type="hidden" value="<?php echo $clid; ?>" >
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" >
								</form>
								<?php
							}
							if($recordType == 'symbiota') echo '</fieldset>';
						}
						?>
						<div id="contact-div">
							<?php
							if($collMetadata['contact']){
								echo $LANG['ADDITIONAL_INFO'].': '.$collMetadata['contact'];
								if($collMetadata['email']){
									$emailSubject = $DEFAULT_TITLE.' occurrence: '.$occArr['catalognumber'].' ('.$occArr['othercatalognumbers'].')';
									$refPath = $indManager->getDomain().$CLIENT_ROOT.'/collections/individual/index.php?occid='.$occArr['occid'];
									$emailBody = $LANG['SPECIMEN_REFERENCED'].': '.$refPath;
									$emailRef = 'subject='.$emailSubject.'&cc='.$ADMIN_EMAIL.'&body='.$emailBody;
									echo ' (<a href="mailto:'.$collMetadata['email'].'?'.$emailRef.'">'.$collMetadata['email'].'</a>)';
								}
							}
							?>
						</div>
						<?php
						if($isEditor || ($collMetadata['publicedits'])){
							?>
							<div id="openeditor-div" style="margin-bottom:10px;">
								<?php
								if($SYMB_UID){
									echo $LANG['SEE_ERROR'].' ';
									?>
									<a href="../editor/occurrenceeditor.php?occid=<?php echo $occArr['occid'];?>">
										<?php echo $LANG['OCCURRENCE_EDITOR']; ?>.
									</a>
									<?php
								}
								else{
									echo $LANG['SEE_AN_ERROR']; ?>
									<a href="../../profile/index.php?refurl=../collections/individual/index.php?occid=<?php echo $occid; ?>">
										<?php echo $LANG['LOGIN']; ?>
									</a> <?php echo $LANG['TO_EDIT_DATA'];
								}
								?>
							</div>
							<?php
						}
						if(array_key_exists('ref',$occArr)){
							?>
							<fieldset>
								<legend><?php echo $LANG['ASSOCIATED_REFS']; ?></legend>
								<?php
								foreach($occArr['ref'] as $refid => $refArr){
									echo '<div class="occur-ref">';
									if($refArr['url']) echo '<a href="'.$refArr['url'].'" target="_blank">';
									echo $refArr['display'];
									if($refArr['url']) echo '</a>';
									echo '</div>';
								}
								?>
							</fieldset>
							<?php
						}
						?>
					</div>
				</div>
				<?php
				if($displayMap){
					?>
					<div id="maptab">
						<div id='map_canvas' style='width:100%;height:600px;'></div>
					</div>
					<?php
				}
				if($genticArr){
					?>
					<div id="genetictab">
						<?php
						foreach($genticArr as $gArr){
							?>
							<div style="margin:15px;">
								<div style="font-weight:bold;margin-bottom:5px;"><?php echo $gArr['name']; ?></div>
								<div style="margin-left:15px;"><label><?php echo $LANG['IDENTIFIER']; ?>:</label> <?php echo $gArr['id']; ?></div>
								<div style="margin-left:15px;"><label><?php echo $LANG['LOCUS']; ?>:</label> <?php echo $gArr['locus']; ?></div>
								<div style="margin-left:15px;">
									<label>URL:</label>
									<a href="<?php echo $gArr['resourceurl']; ?>" target="_blank"><?php echo $gArr['resourceurl']; ?></a>
								</div>
								<div style="margin-left:15px;"><label><?php echo $LANG['NOTES']; ?>:</label> <?php echo $gArr['notes']; ?></div>
							</div>
							<?php
						}
						?>
					</div>
					<?php
				}
				if($dupClusterArr){
					?>
					<div id="dupestab-div">
						<div class="title2-div" style="margin-bottom:10px;text-decoration:underline"><?php echo $LANG['CURRENT_RECORD']; ?></div>
						<?php
						echo '<div class="title2-div>'.$collMetadata['collectionname'].' ('.$collMetadata['institutioncode'].($collMetadata['collectioncode']?':'.$collMetadata['collectioncode']:'').')</div>';
						echo '<div style="margin:5px 15px">';
						if($occArr['recordedby']) echo '<div>'.$occArr['recordedby'].' '.$occArr['recordnumber'].'<span style="margin-left:40px;">'.$occArr['eventdate'].'</span></div>';
						if($occArr['catalognumber']) echo '<div><label>'.$LANG['CATALOG_NUMBER'].':</label> '.$occArr['catalognumber'].'</div>';
						if($occArr['occurrenceid']) echo '<div><label>'.$LANG['GUID'].':</label> '.$occArr['occurrenceid'].'</div>';
						echo '<div><label>'.$LANG['LATEST_ID'].':</label> ';
						if(!isset($occArr['taxonsecure'])) echo '<i>'.$occArr['sciname'].'</i> '.$occArr['scientificnameauthorship'];
						else echo $LANG['SPECIES_PROTECTED'];
						echo '</div>';
						if($occArr['identifiedby']) echo '<div><label>'.$LANG['IDENTIFIED_BY'].':</label> '.$occArr['identifiedby'].'<span stlye="margin-left:30px;">'.$occArr['dateidentified'].'</span></div>';
						echo '</div>';
						//Grab other records
						foreach($dupClusterArr as $dupeType => $dupeArr){
							echo '<fieldset>';
							echo '<legend>'.($dupeType == 'dupe' ? $LANG['DUPES'] : $LANG['EXSICCATAE']).'</legend>';
							foreach($dupeArr as $dupOccid => $dupArr){
								if($dupOccid != $occid){
									echo '<div style="clear:both;margin:15px;">';
									echo '<div style="float:left;margin:5px 15px">';
									echo '<div class="title2-div">'.$dupArr['collname'].' ('.$dupArr['instcode'].($dupArr['collcode']?':'.$dupArr['collcode']:'').')</div>';
									if($dupArr['recordedby']) echo '<div>'.$dupArr['recordedby'].' '.$dupArr['recordnumber'].'<span style="margin-left:40px;">'.$dupArr['eventdate'].'</span></div>';
									if($dupArr['catalognumber']) echo '<div><label>'.$LANG['CATALOG_NUMBER'].':</label> '.$dupArr['catalognumber'].'</div>';
									if($dupArr['occurrenceid']) echo '<div><label>'.$LANG['GUID'].':</label> '.$dupArr['occurrenceid'].'</div>';
									echo '<div><label>'.$LANG['LATEST_ID'].':</label> ';
									if(!isset($occArr['taxonsecure'])) echo '<i>'.$dupArr['sciname'].'</i> '.$dupArr['author'];
									else echo $LANG['SPECIES_PROTECTED'];
									echo '</div>';
									if($dupArr['identifiedby']) echo '<div><label>'.$LANG['IDENTIFIED_BY'].':</label> '.$dupArr['identifiedby'].'<span stlye="margin-left:30px;">'.$dupArr['dateidentified'].'</span></div>';
									echo '<div><a href="#" onclick="openIndividual('.$dupOccid.');return false;">'.$LANG['SHOW_FULL_DETAILS'].'</a></div>';
									echo '</div>';
									if(!isset($occArr['taxonsecure']) && !isset($occArr['localsecure'])){
										if($dupArr['url']){
											$url = $dupArr['url'];
											if($IMAGE_DOMAIN) if(substr($url,0,1) == '/') $url = $IMAGE_DOMAIN.$url;
											echo '<div style="float:right;margin:10px;"><img src="'.$url.'" style="width:70px;border:1px solid grey" /></div>';
										}
									}
									echo '<div style="margin:10px 0px;clear:both"><hr/></div>';
									echo '</div>';
								}
							}
							echo '</fieldset>';
						}
						?>
					</div>
					<?php
				}
				?>
				<div id="commenttab">
					<?php
					$commentTabIndex = 1;
					if($displayMap) $commentTabIndex++;
					if($genticArr) $commentTabIndex++;
					if($dupClusterArr) $commentTabIndex++;
					if($commentArr){
						echo '<div><label>'.count($commentArr).' '.$LANG['COMMENTS'].'</label></div>';
						echo '<hr style="color:gray;"/>';
						foreach($commentArr as $comId => $comArr){
							?>
							<div style="margin:15px;">
								<?php
								echo '<div>';
								echo '<b>'.$comArr['username'].'</b> <span style="color:gray;">posted '.$comArr['initialtimestamp'].'</span>';
								echo '</div>';
								if($comArr['reviewstatus'] == 0 || $comArr['reviewstatus'] == 2) echo '<div style="color:red;">'.$LANG['COMMENT_NOT_PUBLIC'].'</div>';
								echo '<div style="margin:10px;">'.$comArr['comment'].'</div>';
								if($comArr['reviewstatus']){
									if($SYMB_UID){
									    echo '<div><a href="index.php?repcomid='.$comId.'&occid='.$occid.'&tabindex='.$commentTabIndex.'">';
										echo $LANG['REPORT'];
										echo '</a></div>';
									}
								}
								else{
								    echo '<div><a href="index.php?publiccomid='.$comId.'&occid='.$occid.'&tabindex='.$commentTabIndex.'">';
									echo $LANG['MAKE_COMMENT_PUBLIC'];
									echo '</a></div>';
								}
								if($isEditor || ($SYMB_UID && $comArr['username'] == $PARAMS_ARR['un'])){
									?>
									<div style="margin:20px;">
										<form name="delcommentform" action="index.php" method="post" onsubmit="return confirm('<?php echo $LANG['CONFIRM_DELETE']; ?>?')">
											<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
											<input name="comid" type="hidden" value="<?php echo $comId; ?>" />
											<input name="tabindex" type="hidden" value="<?php echo $commentTabIndex; ?>" />
											<button name="formsubmit" type="submit" value="deleteComment"><?php echo $LANG['DELETE_COMMENT']; ?></button>
										</form>
									</div>
									<?php
								}
								?>
							</div>
							<hr style="color:gray;"/>
							<?php
						}
					}
					else echo '<div class="title2-div" style="margin:20px;">'.$LANG['NO_COMMENTS'].'</div>';
					?>
					<fieldset>
						<legend><?php echo $LANG['NEW_COMMENT']; ?></legend>
						<?php
						if($SYMB_UID){
							?>
							<form name="commentform" action="index.php" method="post" onsubmit="return verifyCommentForm(this);">
								<textarea name="commentstr" rows="8" style="width:98%;"></textarea>
								<div style="margin:15px;">
									<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
									<input name="tabindex" type="hidden" value="<?php echo $commentTabIndex; ?>" />
									<button type="submit" name="formsubmit" value="submitComment"><?php echo $LANG['SUBMIT_COMMENT']; ?></button>
								</div>
								<div>
									<?php echo $LANG['MESSAGE_WARNING']; ?>
								</div>
							</form>
							<?php
						}
						else{
							echo '<div style="margin:10px;">';
							echo '<a href="../../profile/index.php?refurl=../collections/individual/index.php?tabindex=2&occid='.$occid.'">';
							echo $LANG['LOGIN'];
							echo '</a> ';
							echo $LANG['TO_LEAVE_COMMENT'];
							echo '</div>';
						}
						?>
					</fieldset>
				</div>
				<?php
				if($traitArr){
				    ?>
					<div id="traittab">
						<?php
						foreach($traitArr as $traitID => $tArr){
							if(!$tArr['depStateID']){
								echo '<div class="trait-div">';
								$indManager->echoTraitUnit($traitArr[$traitID]);
								$indManager->echoTraitDiv($traitArr,$traitID);
								echo '</div>';
							}
						}
						?>
					</div>
					<?php
				}
				if($isEditor){
					?>
					<div id="edittab">
						<div style="padding:15px;">
							<?php
							/*
							 if($USER_RIGHTS && array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])){
								?>
								<div style="float:right;" title="Manage Edits">
									<a href="../editor/editreviewer.php?collid=<?php echo $collid.'&occid='.$occid; ?>"><img src="../../images/edit.png" style="border:0px;width:14px;" /></a>
								</div>
								<?php
							}
							*/
							echo '<div style="margin:20px 0px 30px 0px;">';
							echo '<label>'.$LANG['ENTERED_BY'].':</label> '.($occArr['recordenteredby']?$occArr['recordenteredby']:'not recorded').'<br/>';
							echo '<label>'.$LANG['DATE_ENTERED'].':</label> '.($occArr['dateentered']?$occArr['dateentered']:'not recorded').'<br/>';
							echo '<label>'.$LANG['DATE_MODIFIED'].':</label> '.($occArr['datelastmodified']?$occArr['datelastmodified']:'not recorded').'<br/>';
							if($occArr['modified'] && $occArr['modified'] != $occArr['datelastmodified']) echo '<label>'.$LANG['SOURCE_DATE_MODIFIED'].':</label> '.$occArr['modified'];
							echo '</div>';
							//Display edits
							$editArr = $indManager->getEditArr();
							$externalEdits = $indManager->getExternalEditArr();
							if($editArr || $externalEdits){
								if($editArr){
									?>
									<fieldset style="padding:15px;margin:10px 0px;">
										<legend><?php echo $LANG['INTERNAL_EDITS']; ?></legend>
										<?php
										foreach($editArr as $ts => $tsArr){
											?>
											<div>
												<b><?php echo $LANG['EDITOR']; ?>:</b> <?php echo $tsArr['editor']; ?>
												<span style="margin-left:30px;"><b><?php echo $LANG['DATE']; ?>:</b> <?php echo $ts; ?></span>
											</div>
											<?php
											foreach($tsArr['edits'] as $appliedStatus => $appliedArr){
												?>
												<div>
													<span><b><?php echo $LANG['APPLIED_STATUS']; ?>:</b> <?php echo ($appliedStatus?$LANG['APPLIED']:$LANG['NOT_APPLIED']); ?></span>
												</div>
												<?php
												foreach($appliedArr as $vArr){
													echo '<div style="margin:10px 15px;">';
													echo '<b>'.$LANG['FIELD'].':</b> '.$vArr['fieldname'].'<br/>';
													echo '<b>'.$LANG['OLD_VALUE'].($vArr['current'] == 2?' ('.$LANG['CURRENT'].')':'').':</b> '.$vArr['old'].'<br/>';
													echo '<b>'.$LANG['NEW_VALUE'].($vArr['current'] == 1?' ('.$LANG['CURRENT'].')':'').':</b> '.$vArr['new'].'<br/>';
													echo '</div>';
												}
											}
											echo '<div style="margin:5px 0px;">&nbsp;</div>';
											echo '<div style=""><hr></div>';
										}
										?>
									</fieldset>
									<?php
								}
								if($externalEdits){
									?>
									<fieldset>
										<legend><?php echo $LANG['EXTERNAL_EDITS'].':'; ?></legend>
										<?php
										foreach($externalEdits as $orid => $eArr){
											foreach($eArr as $appliedStatus => $eArr2){
												$reviewStr = 'OPEN';
												if($eArr2['reviewstatus'] == 2) $reviewStr = 'PENDING';
												elseif($eArr2['reviewstatus'] == 3) $reviewStr = 'CLOSED';
												?>
												<div>
													<label><?php echo $LANG['EDITOR'].':'; ?></label> <?php echo $eArr2['editor']; ?>
													<span style="margin-left:30px;"><label><?php echo (isset($LANG['DATE'])?$LANG['DATE']:'Date'); ?>:</label> <?php echo $eArr2['ts']; ?></span>
													<span style="margin-left:30px;"><label><?php echo (isset($LANG['SOURCE'])?$LANG['SOURCE']:'Source'); ?>:</label> <?php echo $eArr2['source']; ?></span>
												</div>
												<div>
													<span><label><?php echo (isset($LANG['APPLIEDSTATUS'])?$LANG['APPLIEDSTATUS']:'Applied Status'); ?>:</label> <?php echo ($appliedStatus?'applied':'not applied'); ?></span>
													<span style="margin-left:30px;"><label><?php echo (isset($LANG['REVIEWSTATUS'])?$LANG['REVIEWSTATUS']:'Review Status'); ?>:</label> <?php echo $reviewStr; ?></span>
												</div>
												<?php
												$edArr = $eArr2['edits'];
												foreach($edArr as $fieldName => $vArr){
													echo '<div style="margin:15px;">';
													echo '<label>'.$LANG['FIELD'].':</label> '.$fieldName.'<br/>';
													echo '<label>'.$LANG['OLD_VALUE'].':</label> '.$vArr['old'].'<br/>';
													echo '<label>'.$LANG['NEW_VALUE'].':</label> '.$vArr['new'].'<br/>';
													echo '</div>';
												}
												echo '<div style="margin:15px 0px;"><hr/></div>';
											}
										}
										?>
									</fieldset>
									<?php
								}
							}
							else{
								echo '<div style="margin:25px 0px;font-weight:bold">'.$LANG['NOT_EDITED'].'</div>';
							}
							echo '<div style="margin:15px">'.$LANG['EDIT_NOTE'].'</div>';
							//Display Access Stats
							$accessStats = $indManager->getAccessStats();
							if($accessStats){
								echo '<div style="margin-top:30px"><b>Access Stats</b></div>';
								echo '<table class="styledtable" style="font-size:100%;width:300px;">';
								echo '<tr><th>'.$LANG['YEAR'].'</th><th>'.$LANG['ACCESS_TYPE'].'</th><th>'.$LANG['COUNT'].'</th></tr>';
								foreach($accessStats as $accessDate => $arr1){
									foreach($arr1 as $accessType => $accessCnt){
										echo '<tr><td>'.$accessDate.'</td><td>'.$accessType.'</td><td>'.$accessCnt.'</td></tr>';
									}
								}
								echo '</table>';
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
		else{
			?>
			<h2><?php echo (isset($LANG['UNABLETOLOCATE'])?$LANG['UNABLETOLOCATE']:'Unable to locate occurrence record'); ?></h2>
			<div style="margin:20px">
				<div><?php echo (isset($LANG['CHECKING'])?$LANG['CHECKING']:'Checking archive'); ?>...</div>
				<div style="margin:10px">
					<?php
					ob_flush();
					flush();
					$rawArchArr = $indManager->checkArchive($guid);
					if($rawArchArr && $rawArchArr['obj']){
						$archArr = $rawArchArr['obj'];
						if($isEditor){
							?>
							<div style="float:right">
								<form name="restoreForm" action="index.php" method="post">
									<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
									<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<button name="formsubmit" type="submit" value="restoreRecord"><?php echo $LANG['RESTORE_RECORD']; ?></button>
								</form>
							</div>
							<?php
						}
						if(isset($archArr['dateDeleted'])) echo '<div style="margin-bottom:10px"><label>'.$LANG['RECORD_DELETED'].':</label> '.$archArr['dateDeleted'].'</div>';
						if($rawArchArr['notes']) echo '<div style="margin-left:15px"><label>'.$LANG['NOTES'].': </label>'.$rawArchArr['notes'].'</div>';
						echo '<table class="styledtable"><tr><th>'.$LANG['FIELD'].'</th><th>'.$LANG['VALUE'].'</th></tr>';
						foreach($archArr as $f => $v){
							if(!is_array($v)){
								echo '<tr><td style="width:175px;">'.$f.'</td><td>';
								if(is_array($v)) echo implode(', ',$v);
								else echo $v;
								echo '</td></tr>';
							}
						}
						$extArr = array('dets'=>'identifications','imgs'=>'Images','assoc'=>'Occurrence<br/>Associations','exsiccati'=>'Exsiccati','paleo'=>'Paleontological<br/>Terms','matSample'=>'Material<br/>Sample');
						foreach($extArr as $extName => $extDisplay){
							if(isset($archArr[$extName]) && $archArr[$extName]){
								echo '<tr><td>'.$extDisplay.'</td><td>';
								foreach($archArr[$extName] as $extKey => $extValue){
									if(is_array($extValue)){
										echo '<label>'.$LANG['RECORD_ID'].': '.$extKey.'</label><br/>';
										foreach($extValue as $f => $v){
											echo $f.': '.$v.'<br/>';
										}
										echo '<br/>';
									}
									else echo $extKey.': '.$extValue.'<br/>';
								}
								echo '</td></tr>';
							}
						}
						echo '</table>';
					}
					else echo $LANG['UNABLE_TO_LOCATE'];
					?>
				</div>
			</div>
			<?php
		}
		?>
	</div>
</body>
</html>
