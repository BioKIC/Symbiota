Copy code below into the site's home page (index.php) or any other page of interest.
Modify variable values to target taxa from ta a specific checklist.
The target checklist can be a state or regional list, 
or even a private list that created specifically for feature.

<?php
//---------------------------GAME SETTINGS---------------------------------------
//If more than one game will be active, assign unique numerical ids for each game.
//If only one game will be active, leave set to 1. 
$oodID = 1; 

//Enter checklist id (clid) of the checklist you wish to use, if you would like to use more than one checklist,
//separate their ids with a comma ex. "1,2,3,4"
$ootdGameChecklist = "1";

//Change to modify title
$ootdGameTitle = "Organism of the Day "; 

//Replace "plant" with the type of organism, eg: plant, animal, insect, fungi, etc.
//This setting will appear in "Name that ______"
$ootdGameType = "plant"; 
//---------------------------DO NOT CHANGE BELOW HERE-----------------------------

include_once($SERVER_ROOT.'/classes/GamesManager.php');
$gameManager = new GamesManager();
$gameInfo = $gameManager->setOOTD($oodID,$ootdGameChecklist);
?>
<div style="float:right;margin-right:10px;width:290px;text-align:center;">
	<div style="font-size:130%;font-weight:bold;">
		<?php echo $ootdGameTitle; ?>
	</div>
	<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/games/ootd/index.php?oodid=<?php echo htmlspecialchars($oodID, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&cl=' . htmlspecialchars($ootdGameChecklist, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&title=' . htmlspecialchars($ootdGameTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&type=' . htmlspecialchars($ootdGameType, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
		<img src="<?php echo $gameInfo['images'][0]; ?>" style="width:250px;border:0px;" />
	</a><br/>
	<b>What is this <?php echo $ootdGameType; ?>?</b><br/>
	<a href="<?php echo htmlspecialchars($CLIENT_ROOT, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/games/ootd/index.php?oodid=<?php echo htmlspecialchars($oodID, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&cl=' . htmlspecialchars($ootdGameChecklist, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&title=' . htmlspecialchars($ootdGameTitle, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '&type=' . htmlspecialchars($ootdGameType\, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
		Click here to test your knowledge
	</a>
</div>
