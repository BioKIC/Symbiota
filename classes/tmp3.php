			if ($statementSyns = $this->conn->prepare($sqlSyns)) {
				$statementSyns->bind_param("ss", $this->taxAuthID, $objId);
				$statementSyns->execute();
				$resultSyns = $statementSyns->get_result();
				while($row = $resultSyns->fetch_object()){
					$rankName = (isset($taxonUnitArr[$row->rankid])?$taxonUnitArr[$row->rankid]:'Unknown');
					$label = '1-'.$row->rankid.'-'.$rankName.'-'.$row->sciname;
					$sciNameParts = $this->splitScinameByProvided($row->sciname, $row->cultivarEpithet, $row->tradeName, $row->author);
                    $sciName = $sciNameParts['base'];
                    if($row->rankid >= 180) $sciName = '[<i>'.$sciName.'</i>]';
                    $sciName .= $displayAuthor ? " " . $row->author : "";
                    if(isset($sciNameParts['cultivarEpithet'])) $sciName .= " '" . $sciNameParts['cultivarEpithet'] . "'";
                    if(isset($sciNameParts['tradeName'])) $sciName .= " " . $sciNameParts['tradeName'];
                    if($row->tid == $targetId) $sciName = '<b>'.$sciName.'</b>';
					$childArr[$i]['id'] = $row->tid;
					$childArr[$i]['label'] = $label;
					$childArr[$i]['name'] = $sciName;
					$childArr[$i]['url'] = $urlPrefix.$row->tid;
					$i++;
				}
				$resultSyns->free();
				$statementSyns->close();
