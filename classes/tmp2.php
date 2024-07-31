			if ($statement = $this->conn->prepare($sql)) {
                $statement->bind_param("iii", $this->taxAuthID, $objId, $objId);
				$statement->execute();
				$result = $statement->get_result();
			    $i = 0;
                while($r = $result->fetch_object()){
                    $rankName = (isset($taxonUnitArr[$r->rankid]) ? $taxonUnitArr[$r->rankid] : 'Unknown');
                    $label = '2-'.$r->rankid.'-'.$rankName.'-'.$r->sciname;
                    $sciNameParts = $this->splitScinameByProvided($r->sciname, $r->cultivarEpithet, $r->tradeName, $r->author);
                    $sciName = $sciNameParts['base'];
                    if($r->rankid >= 180) $sciName = '<i>' . $sciName . '</i>';
                    $sciName .= $displayAuthor ? " " . $r->author : "";
                    if(isset($sciNameParts['cultivarEpithet'])) $sciName .= " '" . $sciNameParts['cultivarEpithet'] . "'";
                    if(isset($sciNameParts['tradeName'])) $sciName .= " " . $sciNameParts['tradeName'];
                    if($r->tid == $targetId) $sciName = '<b>' . $sciName . '</b>';
                    $sciName = "<span style='font-size:75%;'>" . $rankName . ":</span> " . $sciName;
                    if($r->tid == $objId){
                        $retArr['id'] = $r->tid;
                        $retArr['label'] = $label;
                        $retArr['name'] = $sciName;
                        $retArr['url'] = $urlPrefix.$r->tid;
                        $retArr['children'] = Array();
                    <!-- }
                    else{
                        $childArr[$i]['id'] = $r->tid;
                        $childArr[$i]['label'] = $label;
                        $childArr[$i]['name'] = $sciName;
                        $childArr[$i]['url'] = $urlPrefix.$r->tid;
                        $sql3 = 'SELECT tid FROM taxaenumtree WHERE taxauthid = ? AND parenttid = ? LIMIT 1 ';
                        //echo 'sql3: '.$sql3.'<br/>';
                        $rs3 = $this->conn->query($sql3);
                        if($row3 = $rs3->fetch_object()){
                            $childArr[$i]['children'] = true;
                    if ($statement = $this->conn->prepare($sql)) {
                        $statement->bind_param("sss", $this->taxAuthID, $objId, $objId);
                        $statement->execute();
                        $result = $statement->get_result();
                        $i = 0;
                        while($r = $result->fetch_object()){
                            $rankName = (isset($taxonUnitArr[$r->rankid])?$taxonUnitArr[$r->rankid]:'Unknown');
                            $label = '2-'.$r->rankid.'-'.$rankName.'-'.$r->sciname;
                            $sciName = $r->sciname;
                            if($r->rankid >= 180) $sciName = '<i>'.$sciName.'</i>';
                            if($r->tid == $targetId) $sciName = '<b>'.$sciName.'</b>';
                            $sciName = "<span style='font-size:75%;'>".$rankName.":</span> ".$sciName.($displayAuthor?" ".$r->author:"");
                            if($r->tid == $objId){
                                $retArr['id'] = $r->tid;
                                $retArr['label'] = $label;
                                $retArr['name'] = $sciName;
                                $retArr['url'] = $urlPrefix.$r->tid;
                                $retArr['children'] = Array(); -->
