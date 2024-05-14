<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GamesManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:0;
$gameManager = new GamesManager();
$clArr = $gameManager->getChecklistArr($pid);

 ?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Games</title>
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<script type="text/javascript">
		function checkForm(f){
			if(f.clid.value == ""){
				alert("Select a checklist from pulldown");
				return false;
			}
			return true;
		}
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($games_indexMenu)?$games_indexMenu:"true");
	include($SERVER_ROOT.'/includes/header.php');
	if(isset($gamess_indexCrumbs)){
		?>
		<div class="navpath">
			<a href="../index.php">Home</a> &gt;
			<?php echo $games_indexCrumbs;?>
			<b><?php echo $DEFAULT_TITLE; ?> Games</b>
		</div>
		<?php
	}
	?>

	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading">Games</h1>

		<div style='margin:10px;'>
			Games are designed to provide a fun interface for exploring the species found
			within a checklist. Select a checklist from the pulldown below to open
			a game primed with those species.
			Note that all games are also accessable
			from the Checklist Explorer page by
			clicking on "Games" icon located to the right of the checklist title.
			Have fun and good luck!
		</div>

		<h2>Taxon Name Game</h2>
		<div style='margin:10px;'>
			Deduce the scientific name of the organisms found within a species list.
			A species is randomly selected from a checklist of your choice and you
			have to guess the letters found in the name before the apple is eatten,
			the flower is plucked, or the plant defoliates.
			<div style="margin:10px;">
				<form name="namegameform" action="namegame.php" method="post" onsubmit="return checkForm(this);">
					<select name="clid">
						<option value="">SELECT A SPECIES CHECKLIST</option>
						<option value="">---------------------------------</option>
						<?php
						foreach($clArr as $clid => $clName){
							?>
							<option value="<?php echo $clid; ?>"><?php echo $clName; ?></option>
							<?php
						}
						?>
					</select>
					<input type="submit" name="action" value="Start Name Game" />
				</form>
			</div>
		</div>

		<h2>Flash Card Quiz</h2>
		<div style='margin:10px 0px 50px 10px;'>
			What is this organism? In this game, you have to determine the name of the organism pictured
			in a set of images. There are typically several images of each species so before making your
			guess, make sure you have seen all the images by clicking on "Next Image".
			<div style="margin:10px;">
				<form name="flashcardform" action="flashcards.php" method="post" onsubmit="return checkForm(this);">
					<select name="clid">
						<option value="">SELECT A SPECIES CHECKLIST</option>
						<option value="">---------------------------------</option>
						<?php
						foreach($clArr as $clid => $clName){
							?>
							<option value="<?php echo $clid; ?>"><?php echo $clName; ?></option>
							<?php
						}
						?>
					</select>
					<input type="submit" name="action" value="Start Flash Card Game" />
				</form>
			</div>
		</div>
	</div>
	<!-- This ends inner text! -->
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>