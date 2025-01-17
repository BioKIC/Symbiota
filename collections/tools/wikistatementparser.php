<?php
if(!file_exists('./config/symbini.php')) {
	include_once('../../config/symbini.php');

	ob_start();
	include_once('../../includes/head.php');
	$head = ob_get_clean();

	$html = <<<HTML
	<!DOCTYPE html>
	<html lang="en">
		<head>
			<title>Script Error</title>
			$head
		</head>
		<body style="width: 100vw; height: 100vh;display: flex">      
			<div style="text-align: center; margin: auto; background-color: --primary">
				<h1 class="page-heading">Command Line Only Script</h1>
				<a href="$CLIENT_ROOT">Back to Home</a>
			</div> 
		</body>
	</html>
	HTML;

	echo $html;
} else {
	include_once('./config/symbini.php');
	include_once('./config/dbconnection.php');

	function get_taxon_names() {
		$conn = MySQLiConnectionFactory::getCon('readonly');
		$taxons = $conn->query(
			'SELECT ts.family, t.tid, t.sciname, t.rankid, s.tid as synTid, IF(t.sciname
			!= s.sciname ,s.sciname, null) as Synonym FROM taxa t INNER JOIN  
			taxstatus ts ON t.tid = ts.tidaccepted INNER JOIN taxa s ON ts.tid = 
			s.tid INNER JOIN taxstatus tst ON t.tid = tst.tid WHERE t.rankid >= 180 
			AND (ts.taxonomicStatus IS NULL OR ts.taxonomicStatus != 
			"unresolved") AND tst.tid = tst.tidaccepted AND ts.family 
			IN("Amblystegiaceae","Andreaeaceae","Andreaeobryaceae","Anomodo
			ntaceae","Archidiaceae","Aulacomniaceae","Bartramiaceae","Brachytheciaceae",
			"Bruchiaceae","Bryaceae","Bryoxiphiaceae","Calliergonacea
			e","Calymperaceae","Catoscopiaceae","Climaciaceae","Cryphaeaceae",
			"Daltoniaceae","Dicranaceae","Diphysciaceae","Disceliaceae","Ditricha
			ceae","Encalyptaceae","Entodontaceae","Ephemeraceae","Erpodiaceae
			","Fabroniaceae","Fissidentaceae","Fontinalaceae","Funariaceae","Giga
			spermaceae","Grimmiaceae","Hedwigiaceae","Helodiaceae","Hookeria
			ceae","Hylocomiaceae","Hypnaceae","Hypopterygiaceae","Lembophyll
			aceae","Leptodontaceae","Leskeaceae","Leucobryaceae","Leucodontac
			eae","Leucophanaceae","Meesiaceae","Meteoriaceae","Mielichhoferiac
			eae","Mniaceae","Myriniaceae","Neckeraceae","Oedipodiaceae","Ortho
			dontiaceae","Orthotrichaceae","Pilotrichaceae","Plagiotheciaceae","Ple
			uroziopsaceae","Polytrichaceae","Pottiaceae","Pseudoditrichaceae","Pt
			erigynandraceae","Pterobryaceae","Ptychomitriaceae","Racopilaceae",
			"Rhachitheciaceae","Rhizogoniaceae","Rhytidiaceae","Roellobryaceae",
			"Rutenbergiaceae","Schistostegaceae","Scouleriaceae","Seligeriaceae"
			,"Sematophyllaceae","Sphagnaceae","Splachnaceae","Splachnobryace
			ae","Stereophyllaceae","Takakiaceae","Tetraphidaceae","Theliaceae","
			Thuidiaceae","Timmiaceae") ORDER BY ts.family, t.sciname, t.rankid, 
			s.sciname'
		);

		$conn->close();

		$taxon_map = [];

		while($row = $taxons->fetch_assoc()) {
			if(!isset($row['tid'])) continue;

			if(!isset($taxon_map[$row['tid']])) {
				$taxon_map[$row['tid']] = [
					'tid' => $row['tid'],
					'sciname' => $row['sciname'],
					'synonyms' => [],
				];
			}


			if($row['Synonym'] != null) {
				array_push($taxon_map[$row['tid']]['synonyms'], [ 'sciname' => $row['Synonym'], 'tid' => $row['synTid'] ]);
			}
		}

		return $taxon_map;
	}

	function getWikiApi($host, $api) {
		return json_decode(file_get_contents($host . "/w/api.php?format=json" . $api));
	}

	function extractWikiSentences($host) {
		return function ($count, $title) use ($host) {
			$response = getWikiApi($host, "&action=query&prop=extracts&exsentences=" . $count . "&titles=" . urlencode($title));

			if($response && isset($response->query) && isset($response->query->pages)) {
				foreach ($response->query->pages as $page_id => $page) {
					if($page && isset($page->extract)) return $page->extract . "<a href=\"". $host . "/wiki/" . $page->title ."\"> See more...</a>";
				}
			}

			return false;
		};
	}

	function parseWikiText($host) {
		return function ($title) use ($host) {
			$response = getWikiApi($host, "&action=parse&prop=text&disabletoc&page=" . urlencode($title));

			if($response && isset($response->parse) && isset($response->parse->text) && isset($response->parse->text->{'*'})) {
				return $response->parse->text->{'*'};
			}
		};
	}

	function parseWikiSections($host) {
		return function ($title) use ($host) {
			$sections = getWikiApi($host, "&action=parse&prop=sections&page=" . urlencode($title));

			if($sections && isset($sections->parse) && isset($sections->parse->sections)) {

				return $sections->parse->sections;
			}

			return false;
		};
	}

	function getSectionText($html, $section_name) {
		$dom = new DomDocument();
		@ $dom->loadHTML($html);
		$section = $dom->getElementById($section_name);
		$text = '';

		if($section != null && isset($section->parentNode) && isset($section->parentNode->parentNode)) {
			foreach($section->parentNode->parentNode->getElementsByTagName('p') as $tag ) {
				$text = $text . $tag->textContent . "\n";
			}
		}
		return ['header' => $section_name, 'statement'=> $text];
	}

	function getInnerHtml($domNode) {
		$innerHTML = "";
		foreach ($domNode->childNodes as $child) {
			$innerHTML .= $domNode->ownerDocument->saveHTML($child);
		}

		return $innerHTML;
	}

	function trim_explode($delim, $str) {
		$parts = explode($delim, $str);
		$result = [];

		foreach($parts as $part) {
			$result[] = trim($part);
		}

		return $result;
	}

	function getElementsbyClass($domNode, $class) {
		$classNodes = [];

		if(isset($domNode->childNodes)) {
			foreach ($domNode->childNodes as $child) {
				$classNodes = array_merge($classNodes, getElementsbyClass($child, $class));
			}
		}

		if(isset($domNode->attributes)) {
			foreach($domNode->attributes as $attrib) {
				if($attrib->name == "class" && $attrib->value == $class) {
					array_push($classNodes, $domNode);
				}
			}
		}

		return $classNodes;
	}

	function getNorthAmericanFloraStatements($taxon_name) {
		$base_url = "http://floranorthamerica.org";
		$res = parseWikiText($base_url)($taxon_name);

		if(empty($res)) return false;

		$dom = new DomDocument();
		@ $dom->loadHTML($res);

		$statements = [];

		//Get and Cleans intro Bold sections into statements
		foreach($dom->getElementsByTagName('span') as $element) {
			foreach($element->attributes as $attrib) {
				if($attrib->name == "class") {
					if(in_array($attrib->value, ['statement'])) {
						$innerHTML = ""; 
						foreach ($element->childNodes as $child) {
							$innerHTML .= $element->ownerDocument->saveHTML($child);
						}
						foreach (explode("<b>", $innerHTML) as $statement) {
							if($statement != "") {
								$parts = explode("</b>", $statement);
								array_push($statements, ['header' => trim($parts[0]), 'statement' => trim($parts[1])]);
							}
						}
					}
				}
			}
		}

		//Parse Other Non Bold Sections
		foreach (getElementsbyClass($dom, 'treatment-info') as $info) {
			$parts = explode('<div', getInnerHtml($info));
			if(!empty($parts)) {
				$raw_statements = explode('<br>', $parts[0]);
				if(!empty($raw_statements)) {
					foreach($raw_statements as $statement) {
						$statement_parts = trim_explode(':', $statement);

						if(!empty($statement_parts) && count($statement_parts) > 1) {
							array_push($statements, ['header' => $statement_parts[0], 'statement' => $statement_parts[1]]);
						}
					}
				}
			}
		}

		//Add Remaning Sections
		array_push($statements, getSectionText($res, "Distribution"));
		array_push($statements, getSectionText($res, "Discussion"));

		//source
		$author = getElementsbyClass($dom, 'treatment-id-authorName');
		if(count($author) > 0) {
			$author = trim($author[0]->textContent);
		}

		$volume = getElementsbyClass($dom, 'treatment-id-volume');
		if(count($volume) > 0) {
			$volume = str_replace('.', '', trim($volume[0]->textContent));
		}

		return ['source' => $author . ' in FNA ' . $volume, 'sourceUrl'=> $base_url . '/' . str_replace(' ', '_', $taxon_name),'statements' => $statements];
	}

	function getWikipediaStatements($taxon_name) {
		$host = 'https://www.wikipedia.org';
		return [
			'source' => 'wikipedia', 
			'sourceUrl' => $host . '/wiki/' . str_replace(' ', '_', $taxon_name), 
			'statements' => [
				['header' => 'General Info', 'statement' => extractWikiSentences($host)(10, $taxon_name)]
			]
		];
	}

	function getStatements($taxon_name) {
		$statements = getNorthAmericanFloraStatements($taxon_name);
		if(!$statements) $statements = getWikipediaStatements($taxon_name);

		return $statements;
	}

	function insertStatements($tdProfileID, $tid, $caption, $statements) {

		$conn = MySQLiConnectionFactory::getCon("write");

		$insert_taxon_description = $conn->prepare("INSERT INTO taxadescrblock (tdProfileID, tid, caption, source, sourceurl, language, langid, uid) VALUES (?, ?, ?, ?, ?, 'English', 1, ?)");
		$insert_taxon_description->bind_param("iisssi", $tdProfileID, $tid, $caption, $statements['source'], $statements['sourceUrl'], $GLOBALS['SYMB_UID']);
		$insert_taxon_description->execute();


		$tdbid = intval($conn->query("SELECT LAST_INSERT_ID() as id")->fetch_assoc()["id"]);

		foreach($statements['statements'] as $statement) {
			$insert_taxon_statements = $conn->prepare("INSERT INTO taxadescrstmts (tdbid, heading, statement) VALUES (?, ?, ?)");
			$insert_taxon_statements->bind_param("iss", $tdbid, $statement['header'], $statement['statement']);
			$insert_taxon_statements->execute();
		}
	}

	function progress_bar($done, $total, $info="", $width=50) {
		$perc = round(($done * 100) / $total);
		$bar = round(($width * $perc) / 100);
		return sprintf("%s%%[%s>%s]%s\r", $perc, str_repeat("=", $bar), str_repeat(" ", $width-$bar), $info);
	}

	if(isset($GLOBALS['SYMB_UID'])) {
		$taxon_names = get_taxon_names(); 
		$count = 0;

		$maxCount = count($taxon_names);

		foreach($taxon_names as $tid => $taxon) {
			//Try To Parse Main Sciname from FNA
			$statements = getNorthAmericanFloraStatements($taxon['sciname']);

			//If main sciname didn't get a hit then check all synonyms
			if(!$statements) {
				foreach($taxon['synonyms'] as $synonym) {
					$statements = getNorthAmericanFloraStatements($synonym['sciname']);
					if($statements)  {
						insertStatements(1, $synonym['tid'], "FNA Description", $statements);
						break;
					}
				}
			} else {
				insertStatements(1, $tid, "FNA Description", $statements);
			}

			$count++;

			//If no synonym hits check wikipedia for main name 
			// Does This Need to change? 
			if(!$statements) $statements = getWikipediaStatements($taxon['sciname']);

			//If no main name wikipedia hits check synonyms 
			// Make sure taxadescrprofile has the Id as Wikipedia
			if(!$statements) {
				foreach($taxon['synonyms'] as $synonym) {
					$statements = getWikipediaStatements($synonym);
					if($statements) {
						insertStatements(2 , $tid, "Wikipedia Description", $statements, MySQLiConnectionFactory::getCon("write"));
						break;
					};
				}
			} else {
				insertStatements(2 , $tid, "Wikipedia Description", $statements, MySQLiConnectionFactory::getCon("write"));
			}

			echo progress_bar($count, $maxCount);
		};
	} else {
		echo "Cannot run script because there is no user id available";
	}
}

?>

