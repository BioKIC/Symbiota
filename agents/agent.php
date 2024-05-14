<?php

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/AgentManager.php');
include_once($SERVER_ROOT.'/classes/RdfUtility.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

// Find out what media types the client would like, in order.
$accept = RdfUtility::parseHTTPAcceptHeader($_SERVER['HTTP_ACCEPT']);
$force = array_key_exists("force",$_REQUEST)?$_REQUEST["force"]:"";
$agentid = preg_replace('[^0-9]','',array_key_exists("agentid",$_REQUEST)?$_REQUEST["agentid"]:"");
$uuid = array_key_exists("uuid",$_REQUEST)?$_REQUEST["uuid"]:"";
$findobjects = preg_replace('[^0-9]','',array_key_exists("findobjects",$_REQUEST)?$_REQUEST["findobjects"]:"");

$agent = new Agent();
$agentview = new AgentView();
if (strlen($agentid) > 0 ) {
  $agent->load($agentid);
  $agentview->setModel($agent);
} elseif (strlen($uuid)>0) {
  if (UuidFactory::is_valid($uuid)) {
     $agent->loadByGUID($uuid);
     $agentview->setModel($agent);
  }
}

$done = FALSE;
if ($force=='turtle') {
       deliverTurtle();
       $done = TRUE;
}
if ($force=='rdfxml') {
       deliverRdfXml();
       $done = TRUE;
}
reset($accept);
while (!$done && list($key, $mediarange) = each($accept)) {
    if ($mediarange=='text/turtle') {
       deliverTurtle();
       $done = TRUE;
    }
    if ($mediarange=='application/rdf+xml') {
       deliverRdfXml();
       $done = TRUE;
    }
}
if (!$done) {
  Header("Content-Type: text/html; charset=".$CHARSET);
  $spDisplay = " Agent: ". $agent->getMinimalName();
  pageheader($agent);
  $am = new AgentManager();
  if ($am->isAgentEditor()) {
     echo "<div id='commandDiv'><span class='link' id='editLink'>Edit</span>&nbsp;<span class='link' id='viewLink'>View</span>&nbsp;<span class='link' id='createLink'>New</span></div>";
     echo "
     <script type='text/javascript'>
        $('#editLink').click(function () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=edit&table=Agent&agentid=".$agent->getagentid()."',
               dataType : 'html',
               success: function(data){
                  $('#agentDetailDiv".$agent->getagentid()."').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
        $('#viewLink').click(function () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=show&table=Agent&agentid=".$agent->getagentid()."',
               dataType : 'html',
               success: function(data){
                  $('#agentDetailDiv".$agent->getagentid()."').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
        $('#createLink').click(function () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=create&table=Agent',
               dataType : 'html',
               success: function(data){
                  $('#agentDetailDiv".$agent->getagentid()."').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
     </script>
     ";
  }
  echo "<div id='agentDetailDiv".$agent->getagentid()."'>";
  echo $agentview->getDetailsView();
  echo "</div>";
  if ($findobjects==1) {
    echo $am->getPrettyListOfCollectionObjectsForCollector($agent->getagentid());
  }
  footer();
}

/**
 * Return the requested agent as RDF in a turtle serialization.
 */
function deliverTurtle() {
   global $agent, $agentview, $charset;
   Header("Content-Type: text/turtle; charset=".$CHARSET);
   echo $agentview->getAsTurtle();
}

function deliverRdfXml() {
   global $agent, $agentview, $charset;
   Header("Content-Type: application/rdf+xml; charset=".$CHARSET);
   echo $agentview->getAsRdfXml();
}

function pageheader($agent) {
   global $SERVER_ROOT, $DEFAULT_TITLE, $spDisplay, $CLIENT_ROOT, $agent_indexCrumbs, $charset;
echo '<!DOCTYPE HTML>
<html lang="en">
<head>
	<title>'.$DEFAULT_TITLE.' - '.$spDisplay. '</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<meta name="keywords" content='. $spDisplay .' />
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui_accordian.css" type="text/css" rel="stylesheet" />
   <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>';
    // include_once($SERVER_ROOT.'/includes/googleanalytics.php');
echo '<script type="text/javascript">
		var currentLevel = ' . ($descrDisplayLevel?$descrDisplayLevel:"1"). ';
		var levelArr = new Array('. ($descr?"'".implode("','",array_keys($descr))."'":"") . ');
	</script>
</head>
<body>';
   $displayLeftMenu = FALSE;
   include($SERVER_ROOT.'/includes/header.php');
   if(!isset($agent_indexCrumbs)){
      $agent_indexCrumbs = array();
      array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/index.php'>Home</a>");
      array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/agents/index.php'>Agents</a>");
   }
   if (isset($agent)) {
      $name = $agent->getMinimalName();
      $queryname = $agent->getMinimalName(false);
   }
   if (strlen($name)>0) {
      array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/agents/index.php?name=$queryname'>Search</a>");
      array_push($agent_indexCrumbs,$name);
   }
   echo "<div class='navpath'>";
   $last = array_pop($agent_indexCrumbs);
   echo implode($agent_indexCrumbs, " &gt;&gt;");
   echo " &gt;&gt;<strong>$last</strong>";
   array_push($agent_indexCrumbs,$last);
   echo "</div>";

}



function footer() {
   global $SERVER_ROOT,$CLIENT_ROOT;
  include($SERVER_ROOT.'/includes/footer.php');
  echo "</body>\n</html>";
}
?>