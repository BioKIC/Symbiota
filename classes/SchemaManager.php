<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class SchemaManager extends Manager{

	private $host;
	private $database;
	private $port;
	private $username;
	private $adminConn;
	private $currentVersion;
	private $targetSchema;
	private $activeTableArr;

	private $logPath;
	private $amendmentPath;
	private $amendmentFH = false;

	public function __construct(){
		//parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
		if($this->amendmentFH) fclose($this->amendmentFH);
	}

	public function installPatch(){
		if($this->targetSchema){
			set_time_limit(7200);
			$basePath = $GLOBALS['SERVER_ROOT'] . '/content/logs/install/';
			$t = time();
			if($this->targetSchema == 'baseInstall'){
				$this->logPath = $basePath . 'baseSchema_' . $t . '.log';
				$this->amendmentPath = $basePath . 'baseSchema_' . $t . '_failed.sql';
			}
			else{
				$this->logPath = $basePath . 'db_schema_patch-' . $this->targetSchema. '_' . $t . '.log';
				$this->amendmentPath = $basePath . 'db_schema_patch-' . $this->targetSchema. '_' . $t . '_failed.sql';
			}
			$this->setVerboseMode(3);
			$this->setLogFH($this->logPath);
			if($this->setDatabaseConnection()){
				$this->logOrEcho('Connection to database established ('.date('Y-m-d H:i:s').')');
				if($sqlArr = $this->readSchemaFile()){
					$this->logOrEcho('DB schema file analyzed: '. count($sqlArr) . ' statements to apply');
					$cnt = 0;
					foreach($sqlArr as $lineCnt => $stmtArr){
						$cnt++;
						$this->logOrEcho('Statement #' . ($cnt) . ' - line '.$lineCnt.' (' . date('Y-m-d H:i:s') . ')');
						$stmtType = '';
						$targetTable = '';
						$sql ='';
						foreach($stmtArr as $fragment){
							if(substr($fragment, 0, 1) == '#'){
								//is comment
								trim($fragment, '#');
								$this->logOrEcho($fragment, 1);
							}
							elseif(!$stmtType){
								if(preg_match('/`([a-z]+)`/', $fragment, $m)){
									$targetTable = $m[1];
								}
								$stmtType = 'undefined';
								if(strpos($fragment, 'ALTER TABLE') === 0){
									$stmtType = 'ALTER TABLE';
									$this->setActiveTable($targetTable);
								}
								elseif(strpos($fragment, '/*!') === 0) $stmtType = 'Conditional statement';
								elseif(preg_match('/^([A-Z\s]+)/', $fragment, $m)){
									$stmtType = $m[1];
								}
								$this->logOrEcho('Type: ' . $stmtType . ($targetTable ? ' '.$targetTable : ''), 1);
								$sql = $fragment;
							}
							else{
								if($stmtType == 'ALTER TABLE') $fragment = $this->validateAlterTableFragment($fragment, 'w');
								if($fragment) $sql .= ' ' . $fragment;
							}
						}
						$sql = trim($sql, ',');
						if($sql){
							//$this->logOrEcho('Statement: ' . $sql, 1);
							if($this->conn->query($sql)){
								$this->logOrEcho('Success!', 1);
								if(isset($this->warningArr['updated'])){
									$this->logOrEcho('Following adjustments applied:', 2);
									foreach($this->warningArr['updated'] as $colName => $adjustStr){
										$this->logOrEcho($colName . ': ' . $adjustStr, 3);
									}
									unset($this->warningArr['updated']);
								}
								if($this->warningArr){
									//Add these warnings to amendment file since they should be reapplied
									if(!$this->amendmentFH) $this->amendmentFH = fopen($this->amendmentPath, 'w');
									$this->logOrEcho('Following fragments excluded due to errors:', 2);
									$failedSql = '';
									foreach($this->warningArr as $errCode => $errArr){
										foreach($errArr as $colName => $frag){
											if($errCode == 'exists') $this->logOrEcho($colName.' already exists ', 3);
											elseif($errCode == 'missing') $this->logOrEcho($colName.' does not exists ', 3);
											$failedSql .= $frag;
										}
									}
									$failedSql = trim($failedSql, ', ') . ';';
									fwrite($this->amendmentFH, '# '.$targetTable."\n");
									fwrite($this->amendmentFH, $failedSql . "\n\n");
								}
							}
							else{
								$sql = trim($sql,', ') . ';';
								if(!$this->amendmentFH) $this->amendmentFH = fopen($this->amendmentPath, 'w');
								fwrite($this->amendmentFH, '# ERROR: '.$this->conn->error."\n\n");
								fwrite($this->amendmentFH, $sql . "\n\n");
								$this->logOrEcho('ERROR: ' . $this->conn->error, 2);
								$this->logOrEcho('SQL: ' . $sql, 2);
								//break;
							}
						}
						//Reset for next statement
						unset($this->warningArr);
						$this->warningArr = array();
						flush();
						ob_flush();
					}
					$this->logOrEcho('Finished: schema applied');
					$logUrl = str_replace($GLOBALS['SERVER_ROOT'], $GLOBALS['CLIENT_ROOT'], $this->logPath);
					$this->logOrEcho('Log file: <a href="' . $logUrl . '" target="_blank">' . $logUrl . '</a>');
					$amendmentUrl = str_replace($GLOBALS['SERVER_ROOT'], $GLOBALS['CLIENT_ROOT'], $this->amendmentPath);
					if($this->amendmentFH) $this->logOrEcho('Amendment (failed statements needing to be applied): ' . $amendmentUrl);
				}
			}
		}
	}

	private function readSchemaFile(){
		$sqlArr = false;
		if(!$this->targetSchema){
			$this->logOrEcho('No valid schema patch selected');
			return false;
		}
		$filename = $GLOBALS['SERVER_ROOT'];
		if($this->targetSchema == 'baseInstall'){
			$filename .= '/config/schema/3.0/db_schema-3.0.sql';
		}
		else{
			$filename .= '/config/schema/1.0/patches/db_schema_patch-'.$this->targetSchema.'.sql';
		}
		if(file_exists($filename)){
			if($fileHandler = fopen($filename, 'r')){
				$sqlArr = array();
				$cnt = 1;
				$index = 0;
				$delimiter = ';';
				while(!feof($fileHandler)) {
					$line = trim(fgets($fileHandler));
					if($line){
						if(!$index) $index = $cnt;
						if(substr($line, 0, 2) == '--') continue;
						if(substr($line, 0, 9) == 'DELIMITER'){
							$delimiter = trim(substr($line, 9));
						}
						else{
							if($line){
								$preservedIndex = $index;
								if(substr($line, -(strlen($delimiter))) == $delimiter){
									$line = substr($line, 0, -(strlen($delimiter)));
									$index = 0;
								}
								$sqlArr[$preservedIndex][] = $line;
							}
						}
					}
					$cnt++;
				}
				fclose($fileHandler);
			}
		}
		else{
			$this->logOrEcho('ABORT: db schema patch does not exist: ' . $filename);
			return false;
		}
		return $sqlArr;
	}

	private function setActiveTable($targetTable){
		unset($this->activeTableArr);
		if($targetTable){
			$this->activeTableArr = array();
			$sql = 'SHOW COLUMNS FROM ' . $targetTable;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$fieldName = strtolower($r->Field);
					$type = $r->Type;
					if(preg_match('/^([a-z]+)/', $type, $m)){
						$this->activeTableArr[$fieldName]['type'] = $m[1];
					}
					if(preg_match('#\(([\d]*?)\)#', $type, $n)){
						$this->activeTableArr[$fieldName]['length'] = $n[1];
					}
				}
				$rs->free();
			}
			else{
				$this->logOrEcho('ERROR: '.$this->conn->error, 2);
				$this->logOrEcho($sql, 2);
			}
		}
	}

	private function validateAlterTableFragment($fragment){
		if($this->activeTableArr){
			if(strpos($fragment, 'ADD COLUMN') !== false){
				if(preg_match('/^ADD COLUMN `([A-Za-z]+)`/', $fragment, $m)){
					$colName = strtolower($m[1]);
					if(array_key_exists($colName, $this->activeTableArr)){
						$this->warningArr['exists'][$colName] = $fragment;
						return false;
					}
				}
			}
			elseif(strpos($fragment, 'CHANGE COLUMN') !== false){
				if(preg_match('/^CHANGE COLUMN `([A-Za-z]+)` .+ VARCHAR\((\d+)\)/', $fragment, $m)){
					$colName = strtolower($m[1]);
					if(!array_key_exists($colName, $this->activeTableArr)){
						$this->warningArr['missing'][$colName] = $fragment;
						return false;
					}
					$colWidth = $m[2];
					if(isset($this->activeTableArr[$colName]['length']) && $colWidth < $this->activeTableArr[$colName]['length']){
						$this->warningArr['updated'][$colName] = 'Field length expanded from '.$colWidth.' to '.$this->activeTableArr[$colName]['length'];
						$fragment = str_replace('VARCHAR('.$colWidth.')', 'VARCHAR(' . $this->activeTableArr[$colName]['length'] . ')', $fragment);
					}
				}
			}
		}
		return $fragment;
	}

	//Misc support functions
	private function setDatabaseConnection(){
		if(!$this->host || !$this->username || !$this->database || !$this->port || !isset($_POST['password']) || !$_POST['password']){
			$this->logOrEcho('One or more connection variables not set');
			return false;
		}
		$password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
		$this->conn = new mysqli($this->host, $this->username, $password, $this->database, $this->port);
		if($this->conn->connect_error){
			$this->logOrEcho('Connection error: ' . $this->conn->connect_error);
			return false;
		}
		return true;
	}

	//Misc data retrival functions
	public function getVersionHistory(){
		$versionHistory = false;
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
		if(!$this->conn && isset($_POST['password']) && $_POST['password']){
			$password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
			$this->conn = new mysqli($this->host, $this->username, $password, $this->database, $this->port);
		}
		if(!$this->conn) return false;
		$sql = 'SELECT versionNumber, dateApplied FROM schemaversion ORDER BY id';
		if($rs = $this->conn->query($sql)){
			$versionHistory = array();
			while($r = $rs->fetch_object()){
				$versionHistory[$r->versionNumber] = $r->dateApplied;
				$this->currentVersion = $r->versionNumber;
			}
			$rs->free();
		}
		return $versionHistory;
	}

	//Setters and getters
	public function setHost($h){
		$this->host = $h;
	}

	public function setDatabase($db){
		$this->database = $db;
	}

	public function setPort($p){
		if(is_numeric($p)) $this->port = $p;
	}

	public function setUsername($u){
		$this->username = $u;
	}

	public function getCurrentVersion(){
		return $this->currentVersion;
	}

	public function setTargetSchema($schema){
		$this->targetSchema = $schema;
	}
}
?>