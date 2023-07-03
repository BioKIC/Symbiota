<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SchemaManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$username = isset($_POST['username']) ? filter_var($_POST['username'], FILTER_SANITIZE_STRING) : '';
$schemaCode = isset($_POST['schemaCode']) ? filter_var($_POST['schemaCode'], FILTER_SANITIZE_STRING) : '';
$action = isset($_POST['action']) ? $_POST['action'] : '';

$host = filter_var(MySQLiConnectionFactory::$SERVERS[0]['host'], FILTER_SANITIZE_STRING);
$database = filter_var(MySQLiConnectionFactory::$SERVERS[0]['database'], FILTER_SANITIZE_STRING);
$port = filter_var(MySQLiConnectionFactory::$SERVERS[0]['port'], FILTER_SANITIZE_NUMBER_INT);

$schemaManager = new SchemaManager();
$verHistory = $schemaManager->getVersionHistory();
$curentVersion = $schemaManager->getCurrentVersion();

//if(!$IS_ADMIN && $curentVersion) header('Location: ../profile/index.php?refurl=../admin/schemamanager.php');
$IS_ADMIN = true;
?>
<html>
	<head>
		<title>Database Schema Manager</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<style type="text/css">
			label{ font-weight:bold; }
			fieldset{ padding: 15px }
			fieldset legend{ font-weight:bold; }
			.info-div{ margin:10px 5px; }
			.form-section{ margin: 5px 10px; }
			button{ margin: 15px; }
		</style>
	</head>
	<body>
		<?php
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div id="innertext">
			<h1>Database Schema Manager</h1>
			<?php
			if($IS_ADMIN || !$curentVersion){
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
						}
						?>
					</fieldset>
					<?php
				}
				?>
				<div style="margin:15px;">
					<label>Current version: </label>
					<?php echo $curentVersion?$curentVersion:'no schema detected'; ?>
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
				?>
				<fieldset style="width:800px">
					<legend>Database Schema Assistant</legend>
					<div class="info-div">Enter login criteria for database user that has full DDL privileges (e.g. create/alter tables, routines, indexes, etc.).<br>
					We recommend creating a backup of the database before applying any database patches.</div>
					<form name="databaseMaintenanceForm" action="schemamanager.php" method="post">
						<div class="form-section">
							<label>Schema: </label>
							<select name="schemaCode">
								<option value="baseInstall" <?php echo !$curentVersion || $curentVersion < 1 ? 'selected' : ''; ?>>New Install (ver. 3.0)</option>
								<option value="1.1" <?php echo $curentVersion == 1.0 ? 'selected' : ''; ?>>Schema Patch 1.1</option>
								<option value="1.2" <?php echo $curentVersion == 1.1 ? 'selected' : ''; ?>>Schema Patch 1.2</option>
								<option value="3.0" <?php echo $curentVersion == 1.2 ? 'selected' : ''; ?>>Schema Patch 3.0</option>
								<option value="" <?php echo $curentVersion == 3.0 ? 'selected' : ''; ?>>Schema is Current - nothing to do</option>
							</select>
						</div>
						<div class="form-section">
							<label>Username:</label>
							<input name="username" type="text" value="<?php echo $username; ?>" required autocomplete="off">
						</div>
						<div class="form-section">
							<label>Password: </label>
							<input name="password" type="password" value="" required autocomplete="off">
						</div>
						<div class="form-section">
							<label>Host:</label>
							<?php echo $host; ?>
						</div>
						<div class="form-section">
							<label>Database:</label>
							<?php echo $database; ?>
						</div>
						<div class="form-section">
							<label>Port:</label>
							<?php echo $port; ?>
						</div>
						<div class="form-section">
							<button name="action" type="submit" value="installSchema">Install</button>
						</div>
					</form>
				</fieldset>
				<?php
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
