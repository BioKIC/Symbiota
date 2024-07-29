<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ActionManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceActionManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$aManager = new ActionManager();

$actionrequestid = null;
if (isset($_REQUEST['actionrequestid'])) { $actionrequestid = preg_replace('/[^0-9]/','',$_REQUEST['actionrequestid']); }

if (isset($_POST['formsubmit'])) {
   if ($aManager->saveChanges($_POST)) {
      $message = "Saved Changes";
   } else {
      $message = $aManager->getErrorMessage();
   }
   $actionrequestid = null; // go back to list
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
		<title><?php echo $DEFAULT_TITLE; ?> Action Requests</title>
		<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		include_once($SERVER_ROOT.'/includes/googleanalytics.php');
		?>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
        <!--  Supporting visualsearch search box widget -->
        <script type="text/javascript" src="../js/underscore-1.4.3.js"></script>
        <script type="text/javascript" src="../js/backbone-0.9.10.js"></script>

        <script src="../js/visualsearch.js" type="text/javascript"></script>
        <!--[if (!IE)|(gte IE 8)]><!-->
           <link href="../css/visualsearch-datauri.css" media="screen" rel="stylesheet" type="text/css"/>
        <!--<![endif]-->
        <!--[if lte IE 7]><!-->
           <link href="../css/visualsearch.css" media="screen" rel="stylesheet" type="text/css"/>
        <!--<![endif]-->

        		<script language="javascript" type="text/javascript">
        			$('html').hide();
        			$(document).ready(function() {
        				$('html').show();
        			});


        	    	$(document).ready(function() {
        				if(!navigator.cookieEnabled){
        					alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
				}

				$("#tabs").tabs();
			});

			function toggle(target){
				var ele = document.getElementById(target);
				if(ele){
					if(ele.style.display=="none"){
						ele.style.display="block";
			  		}
				 	else {
				 		ele.style.display="none";
				 	}
				}
				else{
					var divObjs = document.getElementsByTagName("div");
				  	for (i = 0; i < divObjs.length; i++) {
				  		var divObj = divObjs[i];
				  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
							if(divObj.style.display=="none"){
								divObj.style.display="block";
							}
						 	else {
						 		divObj.style.display="none";
						 	}
						}
					}
				}
			}

		</script>
	</head>
	<body>

	<?php
	$displayLeftMenu = (isset($collections_indexMenu)?$collections_indexMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($collections_indexCrumbs)){
		if($collections_indexCrumbs){
			echo "<div class='navpath'>";
			echo $collections_indexCrumbs;
			echo " <b>Collections</b>";
			echo "</div>";
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt;&gt; ";
		echo "<b>Collections</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Action Requests</h1>
		<div id="tabs" style="margin:0px;">
	        <?php
	        if($REQUEST_TRACKING_IS_ACTIVE != 1){
                // request tracking module is not enabled
                echo "<h2>Action Request management is not enabled in this symbiota instance</h2>";
	        } elseif($actionrequestid==null){
                // begin if list action requests
				?>
                <ul><li>Action Requests</li></ul>
				<div id="actionrequestlist">
                   <div class="visual_search"></div>

                   <script type="text/javascript" charset="utf-8">
                     $(document).ready(function() {
                       var visualSearch = VS.init({
                         container : $('.visual_search'),
                         query     : 'state: New',
                         callbacks : {
                           search       : ( function(query, searchCollection) {
                              var $focused = $(':focus');
                              $focused.blur();
                              $.post('rpc/requestsearch.php', { 'query' : visualSearch.searchBox.value() }, function(result) {
                                   $("#results").html(result);
                                   });
                              var $focused = $(':focus');
                              $focused.blur();
                           } ),
                           facetMatches : ( function(callback) {
                              callback([
                              'state', 'priority', 'requesttype', 'resolution'
                              ]);
                            } ),
                            valueMatches : ( function(facet, searchTerm, callback) {
                               switch (facet) {
                                 case 'priority':
                                   callback([
                                     { value: '1', label: 'P1-high' },
                                     { value: '2',   label: 'P2' },
                                     { value: '3',   label: 'P3-normal' },
                                     { value: '4', label: 'P4' },
                                     { value: '5', label: 'P5-low' }
                                   ]);
                                   break;
                                 case 'state':
                                   callback(['New', 'Assigned', 'Resolved', 'Reopened']);
                                   break;
                                 case 'resolution':
                                   callback(['', 'Fixed', 'WorksForMe', 'WontFix', 'Duplicate']);
                                   break;
                                 case 'requesttype':
                                   callback(['Image', 'ReplaceImage', 'ReproductiveState']);
                                   break;
                                 }
                           } )
                         } // end callbacks
                       }); // end VS.init
                     });
                   </script>
                        <div id='results'>
						<?php
                        $actionArr = $aManager->queryActionRequestsObjArr(null,null,'New',null,null,null);
                        echo $aManager->getErrorMessage();
                        foreach ($actionArr as $action) {
                           echo "<a href='index.php?actionrequestid=$action->actionrequestid'>Request for $action->requesttype</a> on ".$action->getLinkToRow()." by  $action->requestor on  $action->requestdate  $action->requestremarks, Priority: P$action->priority, State:$action->state $action->resolution $action->statesetdate $action->resolutionremarks $action->fullfillor </br>\n";
                        }
						?>
                        </div>
						<div style="clear:both;">&nbsp;</div>
				</div>
	        <?php
               // end if list action requests
	        } else {
               // begin if edit action request
			?>
              <form action='index.php' method='POST'>
                <ul><li>Edit Action Request</li></ul>
				<div id="actionrequestdetails">
						<?php
                        echo "<input type='hidden' name='formsubmit' value='saveedits' />";
						$action = $aManager->getActionRequestsObj($actionrequestid);
                        echo $aManager->getErrorMessage();
                        if ($action!=null) {
                           echo "<strong>Request:</strong> $action->requesttype</br>\n";
                           echo "<strong>Request applies to:</strong> ".$action->getHumanReadableTableName()." ".$action->getLinkToRow()."</br>\n";
                           echo "<input type='hidden' name='actionrequestid' value='$action->actionrequestid' />";
                           echo "<strong>Requested by:</strong> $action->requestor</br>\n";
                           echo "<strong>On:</strong> $action->requestdate</br>\n";
                           echo "<strong>Request details:</strong> $action->requestremarks</br>\n";
                           echo "<strong>Priority: (1=high,3=normal,5=low) </strong><select name=priority>";
                           $sp1=$sp2=$sp3=$sp3=$sp4=$sp5='';
                           if ($action->priority==1) { $sp1='SELECTED'; }
                           if ($action->priority==2) { $sp2='SELECTED'; }
                           if ($action->priority==3) { $sp3='SELECTED'; }
                           if ($action->priority==4) { $sp4='SELECTED'; }
                           if ($action->priority==5) { $sp5='SELECTED'; }
                           echo "  <option value='1' $sp1 >P1</option>";
                           echo "  <option value='2' $sp2 >P2</option>";
                           echo "  <option value='3' $sp3 >P3</option>";
                           echo "  <option value='4' $sp4 >P4</option>";
                           echo "  <option value='5' $sp5 >P5</option>";
                           echo "</select><br/>\n";
                           echo "<strong>Handled by:</strong> $action->fullfillor<br/>\n";
                           echo "<strong>State: </strong><select name='state'>";
                           $s1=$s2=$s3=$s3=$s4=$s5='';
                           if ($action->state=='New') { $s1='SELECTED'; }
                           if ($action->state=='Assigned') { $s2='SELECTED'; }
                           if ($action->state=='Resolved') { $s3='SELECTED'; }
                           if ($action->state=='Reopened') { $s4='SELECTED'; }
                           echo "  <option value='New'      $s1 >New</option>";
                           echo "  <option value='Assigned' $s2 >Assigned</option>";
                           echo "  <option value='Resolved' $s3 >Resolved</option>";
                           echo "  <option value='Reopened' $s4 >Reopened</option>";
                           echo "</select><br/>\n";
                           echo "<strong>Resolution (if Resolved): </strong><select name='resolution'>";
                           $r1=$r2=$r3=$r3=$r4=$r5='';
                           if ($action->resolution=='') { $r1='SELECTED'; }
                           if ($action->resolution=='Fixed') { $r2='SELECTED'; }
                           if ($action->resolution=='WorksForMe') { $r3='SELECTED'; }
                           if ($action->resolution=='WontFix') { $r4='SELECTED'; }
                           if ($action->resolution=='Duplicate') { $r5='SELECTED'; }
                           echo "  <option value='' $r1 >Open</option>";
                           echo "  <option value='Fixed'      $r2 >Fixed</option>";
                           echo "  <option value='WorksForMe' $r3 >WorksForMe</option>";
                           echo "  <option value='WontFix' $r4 >WontFix</option>";
                           echo "  <option value='Duplicate' $r4 >Duplicate</option>";
                           echo "</select><br/>\n";
                           echo "<strong>On Date: </strong>$action->statesetdate<br/>";
                           echo "<strong>Handling notes: </strong><textarea rows='4' cols='79' name='resolutionremarks'>$action->resolutionremarks</textarea><br/>";
                           echo "<input type='submit' name='submit' value='Save Changes' />";
                        }
						?>
			            <div>
			               <a href='index.php'>Return to List of Action Requests</a>
			            </div>
						<div style="clear:both;">&nbsp;</div>
				</div>
              </form>
	        <?php
	        }  // end if edit request
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
	</body>
</html>
