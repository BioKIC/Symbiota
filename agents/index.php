<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/AgentManager.php');

Header("Content-Type: text/html; charset=".$CHARSET);

$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$defaultLang;

$spDisplay = "Agent Search";

$name = array_key_exists("name",$_REQUEST)?$_REQUEST["name"]:"";

pageheader($name);
if (strlen($name)>0) {
   echo searchform($name);
   echo search($name);
} else {
   echo searchform();
   echo browse();
}
footer();

function search($term) {
  $am = new AgentManager();
  $result  =  "<div id='loadedWithPage'><table id='innertable'>\n";
  $result .= $am->agentNameSearch($term);
  $result .= "</table></div>\n";
  return $result;
}

function browse() {
  $am = new AgentManager();
  $result  =  "<div id='loadedWithPage'>\n";
  $result .= "<h3>Collector and other agent records</h3>";
  $result .= $am->getNameStats();
  $result .= "<ul><li>Individuals</li>";
  $result .= $am->getLastNameLinks();
  $result .= "</ul><ul><li>Teams</li>";
  $result .= $am->getTeamLinks();
  $result .= "</ul><ul><li>Organizations</li>";
  $result .= $am->getOrganizationLinks();
  $result .= "</ul>";
  $result .= "</div>\n";
  return $result;
}

function searchform($name="") {
   global $CLIENT_ROOT;
   $result  = "<div id='formDiv'>";
   $result .= "<form method='GET' id='queryForm' style='display:inline;' >\n";
   $result .= "<label for='name'>Name</label>";
   $result .= "<input type='text' id='name' name='name' value='$name'>";
   $result .= "<input type='submit'>";
   $result .= "</form>\n";
   $result .= "<span id='plinkSpan'></span></div>\n";
   $result .= '<script type="text/javascript">
   var frm = $("#queryForm");
   frm.submit(function(event) {
      $("#statusDiv").html("Searching...");
      $.ajax({
         url: "'.$CLIENT_ROOT.'/agents/rpc/handler.php",
         data: frm.serialize(),
         type: "GET",
         dataType : "html",
         success: function( data ) {
            $("#responseDiv").html(data);
            $("#loadedWithPage").html("");
            var permalink = "&nbsp;<a href=\'' . htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '/agents/index.php?" + frm.serialize() +"\'>Permalink</a>";
            $("#plinkSpan").html(permalink);
         },
         error: function( xhr, status, errorThrown ) {
            $("#statusDiv").html("Error. " + errorThrown);
            console.log( "Error: " + errorThrown );
            console.log( "Status: " + status );
            console.dir( xhr );
         },
         complete: function( xhr, status ) {
             $("#statusDiv").html("");
         }
      });
      event.preventDefault();
   });
</script>
';
   $result .= "<div id='responseDiv'></div>\n";
   $result .= "<div id='statusDiv'></div>\n";

   return $result;
}

function pageheader($name) {
   global $SERVER_ROOT, $DEFAULT_TITLE, $spDisplay, $CLIENT_ROOT, $agents_indexMenu, $agents_indexCrumbs;
echo '<!DOCTYPE HTML>
<html lang="en">
<head>
	<title>'.$DEFAULT_TITLE.' - '.$spDisplay. '</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<meta name="keywords" content='. $spDisplay .' />
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>';
    // include_once($SERVER_ROOT.'/includes/googleanalytics.php');
echo '<script type="text/javascript">
		var currentLevel = ' . ($descrDisplayLevel?$descrDisplayLevel:"1"). ';
		var levelArr = new Array('. ($descr?"'".implode("','",array_keys($descr))."'":"") . ');
	</script>
</head>
<body>';
   $displayLeftMenu = (isset($agents_indexMenu)?$agents_indexMenu:false);
   include($SERVER_ROOT.'/includes/header.php');
   if(!isset($agent_indexCrumbs)){
      $agent_indexCrumbs = array();
      array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/index.php'>Home</a>");
      array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/agents/index.php'>Agents</a>");
   }
   if (strlen($name)>0) {
      array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/agents/index.php?name=$name'>Search</a>");
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