			
            if ($statement = $this->conn->prepare($sql)) {
                $statement->bind_param("i", $this->taxAuthID);
				$statement->execute();
				$result = $statement->get_result();
                while($row = $result->fetch_object()){
                    $lowestRank = $row->RankId;
                }
                $result->free();
                $statement->close();
            }
			<!-- $sql1 = 'SELECT DISTINCT t.tid, t.sciname, t.cultivarEpithet, t.tradeName, t.author, t.rankid FROM taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid WHERE ts.taxauthid = ? AND t.RankId = ?';
			//echo "<div>".$sql1."</div>";
            if($statement1 = $this->conn->prepare($sql1)){
                $i = 0;
                $statement1->bind_param("ii", $this->taxAuthID, $lowestRank);
				$statement1->execute();
				$result1 = $statement1->get_result();
                <!-- $result1 = $this->conn->query($sql1); -->
                <!-- while($row1 = $result1->fetch_object()){
                    $rankName = (isset($taxonUnitArr[$row1->rankid]) ? $taxonUnitArr[$row1->rankid] : 'Unknown');
                    $label = '2-' . $row1->rankid . '-' . $rankName . '-' . $row1->sciname;
                    $sciName = $row1->sciname;
                    if($row1->tid == $targetId) $sciName = '<b>' . $sciName . '</b>';
                    $sciName = "<span style='font-size:75%;'>" . $rankName . ":</span> " . $sciName . ($displayAuthor ? " " . $row1->author : "");
                    $childArr[$i]['id'] = $row1->tid;
                    $childArr[$i]['label'] = $label;
                    $childArr[$i]['name'] = $sciName;
                    $childArr[$i]['url'] = $urlPrefix.$row1->tid;
                    $sql3 = 'SELECT tid FROM taxaenumtree WHERE taxauthid = ? AND parenttid = ? LIMIT 1 ';
                    //echo "<div>".$sql3."</div>";
                    if ($statement3 = $this->conn->prepare($sql3)) {
						$statement3->bind_param("ii", $this->taxAuthID, $row1->tid);
						$statement3->execute();
						$result3 = $statement3->get_result();
                        if($row3 = $result3->fetch_object()){
                            $childArr[$i]['children'] = true;
                        }
                    }
                } -->
            } -->