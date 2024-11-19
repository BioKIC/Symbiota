<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class ConfigUtil {

	/*
	 * INPUT: omcollection PK (collid) - required
	 *        module name - required
	 * OUTPUT: boolean (true if module is activated, false if not)
	 */
	public static function collectionModuleIsActivated($collid, $moduleName, $conn = null){
		$bool = null;
		if($collid && is_numeric($collid)){
			$connShouldBeClosed = false;
			if(!$conn){
				$connShouldBeClosed = true;
				$conn = MySQLiConnectionFactory::getCon('readonly');
			}
			//Get dynamicProperties from omcollections
			$dynProp = '';
			$sql = 'SELECT dynamicProperties FROM omcollections WHERE collid = ?';
			if($stmt = $conn->prepare($sql)){
				$stmt->bind_param('i', $collid);
				$stmt->execute();
				$rs = $stmt->get_result();
				if($r = $rs->fetch_object()){
					$dynProp = $r->dynamicProperties;
				}
				$rs->free();
				$stmt->close();
			}
			if($connShouldBeClosed){
				if($conn !== false) $conn->close();
			}
			//Determine property set within omcollections table
			if($dynProp){
				$propertiesArr = json_decode($dynProp, true);
				if(isset($propertiesArr['editorProps'])){
					$propArr = $propertiesArr['editorProps'];
					if(isset($propArr['modules-panel'])){
						foreach($propArr['modules-panel'] as $module){
							if(isset($module[$moduleName]['status'])){
								if($module[$moduleName]['status']){
									return true;
								}
								else{
									return false;
								}
							}
						}
					}
				}
			}
			//Property has not been explicitly set at collection level, thus use the global value set within symbini file
			if($moduleName == 'paleo'){
				if(isset($PALEO_ACTIVATED) && $PALEO_ACTIVATED) return true;
			}
			if($moduleName == 'matSample'){
				if(isset($MATSAMPLE_ACTIVATED) && $MATSAMPLE_ACTIVATED) return true;
			}
		}
		return false;
	}
}
?>