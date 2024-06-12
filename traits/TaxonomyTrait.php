<?php
trait TaxonomyTrait {
    function splitSciname(){
		$scinameBase = $this->sciName;
		$returnObj = [];
		if(!empty($this->tradeName)){
			$scinameBase = str_replace($this->tradeName, '', $scinameBase);
		}

		if(!empty($this->cultivarEpithet)){
			$scinameBase = str_replace("'". $this->cultivarEpithet . "'", '', trim($scinameBase)); // @TODO could possibly replace off-target if cultivarEpithet matches some parent taxon exactly. We think extremely unlikely edge case, so ignoring for now.
		}
		$returnObj['base'] = trim($scinameBase ?? '');
		$returnObj['cultivarEpithet'] = $this->cultivarEpithet; // assumes quotes not stored in db
		$returnObj['tradeName'] = $this->tradeName;
		$returnObj['author'] = $this->author;

		return $returnObj;
	}

	function splitScinameByProvided($sciName, $cultivarEpithet = '', $tradeName = '', $author = ''){
		
		$returnObj = [];
		if(empty($sciName)) return $returnObj;
		
		$scinameBase = $sciName;
		if(!empty($tradeName)){
			$scinameBase = str_replace($tradeName, '', $scinameBase);
			$returnObj['tradeName'] = $tradeName;
		}

		if(!empty($cultivarEpithet)){
			$scinameBase = str_replace("'". $cultivarEpithet . "'", '', trim($scinameBase)); // @TODO could possibly replace off-target if cultivarEpithet matches some parent taxon exactly. We think extremely unlikely edge case, so ignoring for now.
			$returnObj['cultivarEpithet'] = $cultivarEpithet; // assumes quotes not stored in db
		}

		if(!empty($author)){
			$returnObj['author'] = $author;
		}

		$returnObj['base'] = trim($scinameBase ?? '');
		
		return $returnObj;

	}
}
?>