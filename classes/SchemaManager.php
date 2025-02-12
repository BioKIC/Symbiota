<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class SchemaManager extends Manager{

	private $host;
	private $database;
	private $port;
	private $username;
	private $versionHistory = array();
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
						$sql = '';
						$sqlIsValid = true;
						foreach($stmtArr as $fragment){
							if(substr($fragment, 0, 1) == '#'){
								//line is comment
								if(strpos($fragment, 'Skip if 3.0 install') !== false){
									if(!array_key_exists('1.0', $this->versionHistory)){
										$this->logOrEcho('Statement skipped: issue only exists within older versions of database', 1);
										continue 2;
									}
								}
								elseif(strpos($fragment, 'Skip if 1.0 install') !== false){
									if(array_key_exists('1.0', $this->versionHistory)){
										$this->logOrEcho('Statement skipped: issue only exists within 3.0 original installations', 1);
										continue 2;
									}
								}
								trim($fragment, '# ');
								$this->logOrEcho($fragment, 1);
							}
							elseif(!$stmtType){
								if(preg_match('/`([a-zA-Z]+)`/', $fragment, $m)){
									$targetTable = $m[1];
								}
								$stmtType = 'undefined';
								if(strpos($fragment, 'ALTER TABLE') === 0){
									$stmtType = 'ALTER TABLE';
									$this->setActiveTable($targetTable);
									$sqlIsValid = false;
								}
								elseif(strpos($fragment, '/*!') === 0) $stmtType = 'Conditional statement';
								elseif(preg_match('/^([A-Z0-9_=\s]+)/', $fragment, $m)){
									$stmtType = $m[1];
								}
								$this->logOrEcho('Statement prefix: ' . $stmtType . ($targetTable ? ' '.$targetTable : ''), 1);
								$sql = $fragment;
							}
							else{
								if($stmtType == 'ALTER TABLE') $fragment = $this->validateAlterTableFragment($fragment, 'w');
								if($fragment){
									$sql .= ' ' . $fragment;
									$sqlIsValid = true;
								}
							}
						}
						$sql = trim($sql, ',');
						if($sql && $sqlIsValid){
							//$this->logOrEcho('Statement: ' . $sql, 1);
							try{
								if($this->conn->query($sql)){
									$this->logOrEcho('Success!', 1);
								}
							}
							catch(Exception $e){
								$sql = trim($sql,', ') . ';';
								if(!$this->amendmentFH) $this->amendmentFH = fopen($this->amendmentPath, 'w');
								fwrite($this->amendmentFH, '# ERROR: '.$this->conn->error."\n\n");
								fwrite($this->amendmentFH, $sql . "\n\n");
								$this->logOrEcho('ERROR: ' . $this->conn->error, 2);
								//$this->logOrEcho('SQL: ' . $sql, 2);
								//break;
							}
						}
						//Deal with warnings
						$warningTargetArr = array(
							'columnExists' => 'Columns not added because they exist',
							'columnMissing' => 'Columns not changed because they are missing (may need to be reapplied)',
							'columnModified' => 'Statements modified and applied',
							'indexMissing' => 'Indexes not deleted because they do not exist',
							'indexExists' => 'Indexes not applied because they exist',
							'constraintMissing' => 'Foreign constraints not deleted because they do not exist',
							'constraintExists' => 'Foreign constraints not applied because they exist'
						);

						foreach($warningTargetArr as $type => $message){
							if(isset($this->warningArr[$type])){
								if($type == 'columnMissing'){
									//Add these warnings to amendment file since they may need to be reapplied
									if(!$this->amendmentFH) $this->amendmentFH = fopen($this->amendmentPath, 'w');
									$this->logOrEcho('WARNING: ' . $message . ':', 2);
									$failedSql = '';
									foreach($this->warningArr[$type] as $frag){
										$failedSql .= $frag;
									}
									$failedSql = trim($failedSql, ', ') . ';';
									fwrite($this->amendmentFH, '# ' . $targetTable . "\n");
									fwrite($this->amendmentFH, $failedSql . "\n\n");
								}
								else{
									//These statements don't need to be applied, thus just add to log file
									$this->logOrEcho('NOTICE: ' . $message, 2);
									foreach($this->warningArr[$type] as $adjustStr){
										$this->logOrEcho($adjustStr, 3);
									}
								}
							}
						}
						if($this->warningArr){

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
		elseif($this->targetSchema <= 3){
			$filename .= '/config/schema/1.0/patches/db_schema_patch-'.$this->targetSchema.'.sql';
		}
		else{
			$filename .= '/config/schema/3.0/patches/db_schema_patch-'.$this->targetSchema.'.sql';
		}
		if(file_exists($filename)){
			$this->logOrEcho('Evaluating DB schema file: ' . $filename);
			if($fileHandler = fopen($filename, 'r')){
				$sqlArr = array();
				$cnt = 0;
				$index = 0;
				$delimiter = ';';
				while(!feof($fileHandler)) {
					$line = trim(fgets($fileHandler));
					$cnt++;
					if($line){
						if(substr($line, 0, 2) == '--') continue;
						if(!$index) $index = $cnt;
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
		if($targetTable){
			unset($this->activeTableArr);
			$this->activeTableArr = array();
			$this->setTableColumns($targetTable);
			$this->setTableIndexes($targetTable);
			$this->setTableForeignKeys($targetTable);
		}
	}

	private function setTableColumns($targetTable){
		$status = false;
		$sql = 'SHOW COLUMNS FROM ' . $targetTable;
		try{
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$fieldName = strtolower($r->Field);
				$type = $r->Type;
				if(preg_match('/^([a-z]+)/', $type, $m)){
					$this->activeTableArr['columns'][$fieldName]['type'] = $m[1];
				}
				if(preg_match('#\(([\d]*?)\)#', $type, $n)){
					$this->activeTableArr['columns'][$fieldName]['length'] = $n[1];
				}
				$status = true;
			}
			$rs->free();
		}
		catch(Exception $e){
			//$this->logOrEcho('ERROR: '.$this->conn->error, 2);
			//$this->logOrEcho($sql, 2);
		}
		return $status;
	}

	private function setTableIndexes($targetTable){
		$status = false;
		$sql = 'SHOW INDEXES FROM ' . $targetTable;
		try{
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$indexName = strtolower($r->Key_name);
				if($indexName != 'primary') $this->activeTableArr['indexes'][$indexName][] = strtolower($r->Column_name);
				$status = true;
			}
			$rs->free();
		}
		catch(Exception $e){
			//$this->logOrEcho('ERROR: '.$this->conn->error, 2);
			//$this->logOrEcho($sql, 2);
		}
		return $status;
	}

	private function setTableForeignKeys($targetTable){
		$status = false;
		$sql = 'SHOW CREATE TABLE ' . $targetTable;
		try{
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_assoc()){
				$createTable = strtolower($r['Create Table']);
				if(preg_match_all('/constraint `([a-zA-Z0-9_]+)` foreign key/i', $createTable, $m)){
					if(!empty($m[1])){
						foreach($m[1] as $fkName){
							$this->activeTableArr['FKs'][$fkName] = $fkName;
						}
					}
				}
				$status = true;
			}
			$rs->free();
		}
		catch(Exception $e){
			//$this->logOrEcho('ERROR: '.$this->conn->error, 2);
			//$this->logOrEcho($sql, 2);
		}
		return $status;
	}

	private function validateAlterTableFragment($fragment){
		if($this->activeTableArr){
			if(strpos($fragment, 'ADD COLUMN') !== false){
				if(preg_match('/^ADD COLUMN `([A-Za-z]+)`/', $fragment, $m)){
					$colName = strtolower($m[1]);
					if(array_key_exists($colName, $this->activeTableArr['columns'])){
						$this->warningArr['columnExists'][$colName] = $fragment;
						return false;
					}
				}
			}
			elseif(strpos($fragment, 'CHANGE COLUMN') !== false){
				if(preg_match('/^CHANGE COLUMN `([A-Za-z]+)` .+ VARCHAR\((\d+)\)/', $fragment, $m)){
					$colName = strtolower($m[1]);
					if(!array_key_exists($colName, $this->activeTableArr['columns'])){
						$this->warningArr['columnMissing'][$colName] = $fragment;
						return false;
					}
					$expectColumnLength = $m[2];
					if(isset($this->activeTableArr['columns'][$colName]['length'])){
						$trueColumnLength = $this->activeTableArr['columns'][$colName]['length'];
						if($expectColumnLength < $trueColumnLength){
							$this->warningArr['columnModified'][$colName] = 'Field length expanded from ' . $expectColumnLength . ' to ' . $trueColumnLength;
							$fragment = str_replace('VARCHAR(' . $expectColumnLength . ')', 'VARCHAR(' . $trueColumnLength . ')', $fragment);
						}
					}
				}
			}
			elseif(strpos($fragment, 'DROP INDEX') !== false){
				if(preg_match('/^DROP INDEX `([A-Za-z0-9_]+)`/', $fragment, $m)){
					$indexName = strtolower($m[1]);
					if(!isset($this->activeTableArr['indexes']) || !array_key_exists($indexName, $this->activeTableArr['indexes'])){
						$this->warningArr['indexMissing'][$indexName] = $fragment;
						return false;
					}
				}
			}
			elseif(strpos($fragment, 'ADD INDEX') !== false || strpos($fragment, 'ADD UNIQUE INDEX') !== false){
				if(preg_match('/INDEX `([A-Za-z0-9_]+)`/', $fragment, $m)){
					$indexName = strtolower($m[1]);
					if(isset($this->activeTableArr['indexes']) && array_key_exists($indexName, $this->activeTableArr['indexes'])){
						$this->warningArr['indexExists'][$indexName] = $fragment;
						return false;
					}
				}
			}
			elseif(strpos($fragment, 'DROP FOREIGN KEY') !== false){
				if(preg_match('/^DROP FOREIGN KEY `([A-Za-z0-9_]+)`/', $fragment, $m)){
					$keyName = strtolower($m[1]);
					if(!isset($this->activeTableArr['FKs']) || !array_key_exists($keyName, $this->activeTableArr['FKs'])){
						$this->warningArr['constraintMissing'][$keyName] = $fragment;
						return false;
					}
				}
			}
			elseif(strpos($fragment, 'ADD CONSTRAINT') !== false){
				if(preg_match('/ADD CONSTRAINT `([A-Za-z0-9_]+)`/', $fragment, $m)){
					$constraintName = strtolower($m[1]);
					if(isset($this->activeTableArr['FKs']) && array_key_exists($constraintName, $this->activeTableArr['FKs'])){
						$this->warningArr['constraintExists'][$constraintName] = $fragment;
						return false;
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
		$password = $_POST['password'];
		try{
			$this->conn = new mysqli($this->host, $this->username, $password, $this->database, $this->port);
			if($this->conn->connect_error){
				$this->logOrEcho('Connection error: ' . $this->conn->connect_error);
				return false;
			}
		}
		catch(Exception $e){
			$this->logOrEcho('Connection error: ' . $this->conn->connect_error);
			return false;
		}
		return true;
	}

	//Misc data retrival functions
	public function getVersionHistory(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
		if(!$this->conn && isset($_POST['password']) && $_POST['password']){
			$password = $_POST['password'];
			$this->conn = new mysqli($this->host, $this->username, $password, $this->database, $this->port);
		}
		if(!$this->conn){
			$this->errorMessage = 'ERROR_NO_CONNECTION';
			return false;
		}
		//Check to see if a base schema exists
		if($rs = $this->conn->query('SHOW TABLES')){
			if(!$rs->num_rows) return false;
			$rs->free();
		}
		//Get version history
		$sql = 'SELECT versionNumber, dateApplied FROM schemaversion ORDER BY id';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$this->versionHistory[$r->versionNumber] = $r->dateApplied;
				$this->currentVersion = $r->versionNumber;
			}
			$rs->free();
		}
		return $this->versionHistory;
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