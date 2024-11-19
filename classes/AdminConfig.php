<?php
include_once($SERVER_ROOT.'/classes/Manager.php');

class AdminConfig extends Manager{

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}


	//Misc support functions


	//Misc data retrival functions


	//System transfer functions
	public function getSystemConvertionCode(){
		$statusCode = 0;
		if(!$this->collMetaArr['dynamicProperties'] || $this->dynPropArr) $statusCode = 1;
		else $statusCode = 2;
		return $statusCode;
	}

	public function transferDynamicProperties(){
		$status = false;
		if($this->collMetaArr['dynamicProperties']){
			$dynPropArr = json_decode($this->collMetaArr['dynamicProperties'],true);
			if($dynPropArr) $status = true;
			if(isset($dynPropArr['editorProps']['modules-panel'])){
				$this->addProperty('editorProperties', 'Module Activation', json_encode($dynPropArr['editorProps']['modules-panel']));
			}
			if(isset($dynPropArr['sesar'])){
				$this->addProperty('sesarTools', 'IGSN Profile', json_encode($dynPropArr['sesar']));
			}
			if(isset($dynPropArr['publicationProps'])){
				$this->addProperty('publicationProperties', 'Publication Properties', json_encode($dynPropArr['publicationProps']));
			}
			if(isset($dynPropArr['labelFormats'])){
				foreach($dynPropArr['labelFormats'] as $v){
					$this->addProperty('labelFormat', $v['title'], json_encode($v));
				}
			}
		}
		return $status;
	}




	//Setters and getters


}
?>