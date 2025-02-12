<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SchemaManager.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$username = isset($_POST['username']) ? $_POST['username'] : '';
$schemaCode = isset($_POST['schemaCode']) ? $_POST['schemaCode'] : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

$host = MySQLiConnectionFactory::$SERVERS[0]['host'];
$database = MySQLiConnectionFactory::$SERVERS[0]['database'];
$port = MySQLiConnectionFactory::$SERVERS[0]['port'];

$schemaManager = new SchemaManager();
$verHistory = $schemaManager->getVersionHistory();
$curentVersion = $schemaManager->getCurrentVersion();
$isNewInstall = false;
if(!$curentVersion) $isNewInstall = true;
elseif(isset($verHistory['3.0'])){
	if(strpos($verHistory['3.0'], date('Y-m-d')) === 0) $isNewInstall = true;
}
elseif(isset($verHistory['1.0'])){
	if(strpos($verHistory['1.0'], date('Y-m-d')) === 0) $isNewInstall = true;
}

if(!$IS_ADMIN && !$isNewInstall) header('Location: ../profile/index.php?refurl=../admin/schemamanager.php');
?>
<html lang="en">
	<head>
		<title>Database Schema Manager</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<style type="text/css">
			label{ font-weight:bold; }
			fieldset{ padding: 15px }
			fieldset legend{ font-weight:bold; }
			.info-div{ margin:5px 5px 20px 5px; }
			.form-section{ margin: 5px 10px; }
			button{ margin: 15px; }
		</style>
	</head>
	<body>
		<?php
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div role="main" id="innertext">
			<h1>Database Schema Manager</h1>
			<?php
			if($IS_ADMIN || $isNewInstall){
				if($action){
					?>
					<fieldset>
						<legend>Action Panel</legend>
						<?php
						if($action == 'installSchema'){
							$schemaManager->setTargetSchema($schemaCode);
							$schemaManager->setHost($host);
							$schemaManager->setDatabase($database);
							$schemaManager->setPort($port);
							$schemaManager->setUsername($username);
							$schemaManager->installPatch();
							$verHistory = $schemaManager->getVersionHistory();
							$curentVersion = $schemaManager->getCurrentVersion();
						}
						?>
					</fieldset>
					<?php
				}
				?>
				<div style="margin:15px;">
					<label>Current version: </label>
					<?= $curentVersion ? $curentVersion : 'no schema detected'; ?>
				</div>
				<?php
				if($verHistory){
					?>
					<div style="margin:15px">
						<table class="styledtable" style="width:300px;">
							<tr><th>Version</th><th>Date Applied</th></tr>
							<?php
							foreach($verHistory as $ver => $date){
								echo '<tr><td>'.$ver.'</td><td>'.$date.'</td></tr>';
							}
							?>
						</table>
					</div>
					<?php
				}
				if(!$curentVersion && $schemaManager->getErrorMessage() == 'ERROR_NO_CONNECTION'){
					echo '<h2>ERROR: unable to establish connection to database</h2>';
				}
				else{
					?>
					<fieldset style="width:800px">
						<legend>Database Schema Assistant</legend>
						<div class="info-div">
							Enter login for database user who has full DDL privileges (e.g. create/alter tables, routines, indexes, etc.).<br>
							We recommend creating a backup of the database before applying any database patches.
						</div>
						<?php
						if($curentVersion != '3.2'){
							if(!is_writable($SERVER_ROOT . '/content/logs/install/')){
								?>
								<div class="info-div">
									<span style="color: orange">WARNING</span>: The log directory (e.g. /content/logs/install/) is not writable by web user.
									We strongly recommend that you adjust directory permissions as defined within the installation before running installation/update scripts.
								</div>
								<?php
							}
						}
						?>
						<form name="databaseMaintenanceForm" action="schemamanager.php" method="post">
							<div class="form-section">
								<label>Schema: </label>
								<select name="schemaCode">
									<?php
									if($curentVersion){
										$schemaPatchArr = array('1.1', '1.2', '3.0', '3.1', '3.2');
										foreach($schemaPatchArr as $schemaOption){
											if($schemaOption > $curentVersion) echo '<option value="' . $schemaOption . '">Schema Patch ' . $schemaOption . '</option>';
										}
										if($curentVersion == '3.2') echo '<option value="">Schema is Current - nothing to do</option>';
									}
									else{
										echo '<option value="baseInstall">New Install (ver. 3.0)</option>';
									}
									?>
								</select>
							</div>
							<div class="form-section">
								<label for="username">Username: </label>
								<input id="username" name="username" type="text" value="<?= htmlspecialchars($username, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) ?>" required autocomplete="off">
							</div>
							<div class="form-section">
								<label for="password">Password: </label>
								<input id="password" name="password" type="password" value="" required autocomplete="off">
							</div>
							<div class="form-section">
								<label>Host:</label>
								<?= $host ?>
							</div>
							<div class="form-section">
								<label>Database:</label>
								<?= $database ?>
							</div>
							<div class="form-section">
								<label>Port:</label>
								<?= $port; ?>
							</div>
							<div class="form-section">
								<button name="action" type="submit" value="installSchema">Install</button>
							</div>
						</form>
					</fieldset>
					<?php
				}
			}
			else{
				echo '<div>Not Authorized</div>';
			}
			?>
		</div>
		<?php
		include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
