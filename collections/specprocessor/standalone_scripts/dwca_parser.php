<?php
/*
 * This tool
 *
 *
 */

# http://collections.nmnh.si.edu/ipt/
# http://collections.nmnh.si.edu/ipt/resource?r=nmnhdwca
# If link to archive is not being displayed, simply replace "resource" with archive
# Target Fields: id, type, rights, institutionCode, collectionCode, basisOfRecord, occurrenceID, catalogNumber, occurrenceRemarks, recordNumber, recordedBy, individualID, individualCount, sex, lifeStage, preparations, otherCatalogNumbers, associatedMedia, associatedOccurrences, associatedSequences, occurrenceDetails, startDayOfYear, endDayOfYear, year, month, day, verbatimEventDate, habitat, fieldNumber, fieldNotes, higherGeography, continent, waterBody, islandGroup, island, country, stateProvince, county, locality, verbatimElevation, minimumElevationInMeters, maximumElevationInMeters, verbatimDepth, minimumDepthInMeters, maximumDepthInMeters, verbatimLatitude, verbatimLongitude, verbatimCoordinateSystem, decimalLatitude, decimalLongitude, geodeticDatum, coordinateUncertaintyInMeters, georeferenceProtocol, georeferenceRemarks, earliestEraOrLowestErathem, latestEraOrHighestErathem, earliestPeriodOrLowestSystem, latestPeriodOrHighestSystem, earliestEpochOrLowestSeries, latestEpochOrHighestSeries, earliestAgeOrLowestStage, latestAgeOrHighestStage, group, formation, member, identifiedBy, identificationQualifier, typeStatus, scientificName, higherClassification, kingdom, phylum, class, order, family, genus, subgenus, specificEpithet, infraspecificEpithet, taxonRank, scientificNameAuthorship

//$fileIn = '/Apache24/htdocs/MiscCodeSnipets/dwca_parser/occurrence.txt';
//$fileInMedia = '/Apache24/htdocs/MiscCodeSnipets/dwca_parser/multimedia.txt';
//$fileIn = '/Users/egbot/Documents/Symbiota/Data/NMNH/dwca-nmnhdwca/occurrence.txt';
$fileIn = '/Users/egbot/Documents/code/MiscCodeSnipets/dwca_parser/MO/dwca-tropicosspecimens-v1.17/occurrence.txt';
$fileInMedia = '/Users/egbot/Documents/code/MiscCodeSnipets/dwca_parser/MO/dwca-tropicosspecimens-v1.17/multimedia.txt';
$fileOutName = 'mo_ecuador_output';

//$conditionArr = array('kingdom' => 'Fungi');
//$conditionArr = array('stateProvince' => array("Argentina","Belize","Bolivia","Brazil","Chile","Colombia","Costa Rica","Ecuador","El Salvador","Guatemala","Guyana","Honduras","Nicaragua","Panama","Paraguay","Peru","Suriname","Trinidad and Tobago","Uruguay","Venezuela"));
$conditionArr = array('country' => array("Ecuador"));
//$conditionArr = array('countryCode' => array("PA"));
//$conditionArr = array('family' => array("Acarosporaceae","Adelococcaceae","Agyriaceae","Amphisphaeriaceae","Anamylopsoraceae","Aphanopsidaceae","Arctomiaceae","Arthoniaceae","Arthopyreniaceae","Arthrorhaphidaceae","Ascodichaenaceae","Aspidotheliaceae","Asterothyriaceae","Atheliaceae","Baeomycetaceae","Biatorellaceae","Bionectriaceae","Botryosphaeriaceae","Brigantiaceae","Brigantiaeaceae","Bulgariaceae","Byssolomataceae","Caliciaceae","Calycidiaceae","Candelariaceae","Capnodiaceae","Carbonicolaceae","Catillariaceae","Celotheliaceae","Ceratostomataceae","Chaetosphaeriaceae","Chaetothyriaceae","Chionosphaeraceae","Chrysothricaceae","Cladoniaceae","Clavariaceae","Clavulinaceae","Coccocarpiaceae","Coccotremataceae","Coenogoniaceae","Collemataceae","Coniocybaceae","Cordycipitaceae","Corticiaceae","Coryneliaceae","Crocyniaceae","Cucurbitariaceae","Cyanophyta","Cyphellaceae","Dacampiaceae","Dactylosporaceae","Dermateaceae","Diatrypaceae","Didymellaceae","Didymosphaeriaceae","Dothideaceae","Dothioraceae","Ectolechiaceae","Elixiaceae","Epigloeaceae","Fuscideaceae","Gloeoheppiaceae","Gnomoniaceae","Gomphaceae","Gomphillaceae","Graphidaceae","Gyalectaceae","Gypsoplaceae","Haematommataceae","Helotiaceae","Heppiaceae","Herpotrichiellaceae","Hyaloscyphaceae","Hygrophoraceae","Hymeneliaceae","Hypocreaceae","Hyponectriaceae","Hysteriaceae","Icmadophilaceae","Lecanographaceae","Lecanoraceae","Lecideaceae","Leotiaceae","Leptopeltidaceae","Leptosphaeriaceae","Letrouitiaceae","Lichenotheliaceae","Lichinaceae","Lobariaceae","Malmideaceae","Massalongiaceae","Massariaceae","Mastodiaceae","Megalariaceae","Megalosporaceae","Megasporaceae","Melanommataceae","Melaspileaceae","Meruliaceae","Microascaceae","Microcaliciaceae","Microtheliopsidaceae","Microthyriaceae","Miltideaceae","Monoblastiaceae","Mycoblastaceae","Mycocaliciaceae","Mycoporaceae","Mycosphaerellaceae","Myriangiaceae","Mytilinidiaceae","Naetrocymbaceae","Nectriaceae","Nephromataceae","Nitschkiaceae","Obryzaceae","Ochrolechiaceae","Odontotremataceae","Opegraphaceae","Ophioparmaceae","Pannariaceae","Parmeliaceae","Parmulariaceae","Patellariaceae","Peltigeraceae","Peltulaceae","Peniophoraceae","Pertusariaceae","Phanerochaetaceae","Phlyctidaceae","Phyllachoraceae","Phyllobatheliaceae","Physalacriaceae","Physciaceae","Pilocarpaceae","Placynthiaceae","Platygloeaceae","Pleomassariaceae","Pleosporaceae","Polyporales","Porinaceae","Porpidiaceae","Protothelenellaceae","Pseudoperisporiaceae","Psoraceae","Pucciniaceae","Pyrenothricaceae","Pyrenotrichaceae","Pyrenulaceae","Pyronemataceae","Ramalinaceae","Requienellaceae","Rhizocarpaceae","Rhytismataceae","Rimulariaceae","Roccellaceae","Roccellographaceae","Ropalosporaceae","Sarrameanaceae","Schaereriaceae","Schizophyllaceae","Scoliciosporaceae","Septobasidiaceae","Serpulaceae","Solorinellaceae","Sphaerophoraceae","Sphinctrinaceae","Stereocaulaceae","Stictidaceae","Strigulaceae","Syzygosporaceae","Teloschistaceae","Tephromelataceae","Thelenellaceae","Thelephoraceae","Thelocarpaceae","Thelotremataceae","Thrombiaceae","Trapeliaceae","Tremellaceae","Triblidiaceae","Trichocomaceae","Tricholomataceae","Trichosphaeriaceae","Trypetheliaceae","Tubeufiaceae","Typhulaceae","Umbilicariaceae","Verrucariaceae","Vezdaeaceae","Xanthopyreniaceae","Xylariaceae"));
//$conditionArr = array('family' => array("Acrobolbaceae","Adelanthaceae","Allisoniaceae","Amblystegiaceae","Anastrophyllaceae","Andreaeaceae","Andreaeobryaceae","Aneuraceae","Anomodontaceae","Antheliaceae","Anthocerotaceae","Archidiaceae","Arnelliaceae","Aulacomniaceae","Aytoniaceae","Balantiopsaceae","Balantiopsidaceae","Bartramiaceae","Blasiaceae","Blepharidophyllaceae","Brachytheciaceae","Brevianthaceae","Bruchiaceae","Bryaceae","Bryobartramiaceae","Bryoxiphiaceae","Buxbaumiaceae","Callicostaceae","Calomniaceae","Calymperaceae","Calypogeiaceae","Carrpaceae","Catagoniaceae","Catoscopiaceae","Cephaloziaceae","Cephaloziellaceae","Chaetocoleaceae","Chaetophyllopsaceae","Chonecoleaceae","Cinclidotaceae","Cleveaceae","Climaciaceae","Conocephalaceae","Corsiniaceae","Cryphaeaceae","Cyathodiaceae","Cyrtopodaceae","Daltoniaceae","Delavayellaceae","Dendrocerotaceae","Dicksoniaceae","Dicnemonaceae","Dicranaceae","Diphysciaceae","Disceliaceae","Ditrichaceae","Echinodiaceae","Encalyptaceae","Entodontaceae","Ephemeraceae","Ephemeropsidaceae","Erpodiaceae","Escalloniaceae","Eustichiaceae","Exormothecaceae","Fabroniaceae","Fissidentaceae","Fontinalaceae","Fossombroniaceae","Frullaniaceae","Funariaceae","Geocalycaceae","Gigaspermaceae","Goebeliellaceae","Grimmiaceae","Gymnomitriaceae","Gyrothyraceae","Haplomitriaceae","Hedwigiaceae","Helicophyllaceae","Helodiaceae","Herbertaceae","Herzogiariaceae","Hookeriaceae","Hydropogonaceae","Hylocomiaceae","Hymenophyllaceae","Hymenophytaceae","Hypnaceae","Hypnodendraceae","Hypopterygiaceae","Isoetaceae","Isotachidaceae","Jackiellaceae","Jubulaceae","Jungermanniaceae","Leiosporocerotaceae","Lejeuneaceae","Lembophyllaceae","Lepidolaenaceae","Lepidoziaceae","Leptodontaceae","Leptostomaceae","Lepyrodontaceae","Leskeaceae","Leucobryaceae","Leucodontaceae","Leucomiaceae","Lophocoleaceae","Lunulariaceae","Lycopodiaceae","Makinoaceae","Marchantiaceae","Mastigophoraceae","Meesiaceae","Meteoriaceae","Metzgeriaceae","Metzgeriopsaceae","Microtheciellaceae","Mitteniaceae","Mniaceae","Monocarpaceae","Monocleaceae","Monosoleniaceae","Myliaceae","Myriniaceae","Myuriaceae","Neckeraceae","Neotrichocoleaceae","Notothyladaceae","Octoblepharaceae","Oedipodiaceae","Orthorrhynchiaceae","Orthotrichaceae","Oxymitraceae","Pallavicinaceae","Pallaviciniaceae","Pelliaceae","Perssoniellaceae","Phycolepidoziaceae","Phyllodrepaniaceae","Phyllogoniaceae","Phyllogoniceae","Phyllothalliaceae","Phymatocerotaceae","Pilotrichaceae","Plagiochilaceae","Plagiogyriaceae","Plagiotheciaceae","Pleurophascaceae","Pleuroziaceae","Pleuroziopsidaceae","Polytrichaceae","Porellaceae","Pottiaceae","Prionodontaceae","Pseudoditrichaceae","Pseudolepicoleaceae","Pterigynandraceae","Pterobryaceae","Ptilidiaceae","Ptychomitriaceae","Ptychomniaceae","Racopilaceae","Radulaceae","Regmatodontaceae","Rhabdoweisiaceae","Rhachitheciaceae","Rhacocarpaceae","Rhizogoniaceae","Rhytidiaceae","Ricciaceae","Riellaceae","Rigodiaceae","Rutenbergiaceae","Scapaniaceae","Schistochilaceae","Schistostegaceae","Schizaeaceae","Scorpidiaceae","Scouleriaceae","Selaginellaceae","Seligeriaceae","Sematophyllaceae","Serpotortellaceae","Solenostomataceae","Sorapillaceae","Sphaerocarpaceae","Sphagnaceae","Spiridentaceae","Splachnaceae","Splachnobryaceae","Stereophyllaceae","Takakiaceae","Targioniaceae","Tetraphidaceae","Thamnobryaceae","Theliaceae","Thuidiaceae","Timmiaceae","Trachypodaceae","Treubiaceae","Trichocoleaceae","Verdoorniaceae","Vetaformaceae","Wardiaceae"));

$occurrenceFileOut = $fileOutName.'_occurrence_'.date('Y-m-d').'.csv';
occurrenceTabToCsv($fileIn,$occurrenceFileOut,$conditionArr);
imageHarvest($fileInMedia,$occurrenceFileOut);

class DwcaParser{

	function occurrenceTabToCsv($fileIn,$fileOut,$conditionArr){
		if(!$conditionArr) exit("Condition variable cannot be NULL");
		if(!file_exists($fileIn)) exit("File Not Found");

		$fhOut = fopen($fileOut, "w");
		if(($fhIn = fopen($fileIn, 'r')) !== FALSE){
			$delimiter = "\t";
			$header = fgetcsv($fhIn, 0, $delimiter);
			fputcsv($fhOut, $header);
			$headerindex = array_flip($header);
			$cnt = 0;
			while(($data = fgetcsv($fhIn, 0, $delimiter)) !== FALSE){
				$transferRecord = true;
				foreach($conditionArr as $field => $cond){
					if(!$data[$headerindex[$field]]){
						$transferRecord = false;
					}
					elseif(is_array($cond)){
						if(!in_array($data[$headerindex[$field]], $cond)){
							$transferRecord = false;
						}
					}
					else{
						if($data[$headerindex[$field]] != $cond){
							$transferRecord = false;
						}
					}
				}
				//Transfer is conditions are meet
				if($transferRecord){
					fputcsv($fhOut, $data);
					$cnt++;
					//if($cnt > 100) break;
				}
			}
			fclose($fhIn);
			echo $cnt.' Records'."\n";
		}
		fclose($fhOut);
	}

	function imageHarvest($fileIn,$occurFile){
		if(file_exists($occurFile)){
			if(($fhIn = fopen($occurFile, 'r')) !== FALSE){
				//Skip header
				fgetcsv($fhIn);
				//get occurrence ids (may present a memory problem if occurrence file is really large)
				$occidIds = array();
				while(($data = fgetcsv($fhIn)) !== FALSE){
					$occidIds[] = $data[0];
				}
				fclose($fhIn);
				echo "Occurrence IDs harvested\n";
				//Look for images
				$cnt = 0;
				if(($imgIn = fopen($fileIn, 'r')) !== FALSE){
					$outFileName = str_replace('occurrence', 'multimedia', $occurFile);
					$imgOut = fopen($outFileName, "w");
					//Skip and transfer header
					$header = fgetcsv($imgIn, 0, "\t");
					//Need to fix issue of "source" being used twice for two different columns
					if($header[10] == 'source') $header[10] == 'source2';
					//Add header to ouput file
					fputcsv($imgOut, $header);
					//Add media records where the ID field has a match within the occurrence file
					while(($imgData = fgetcsv($imgIn, 0, "\t")) !== FALSE){
						if(in_array($imgData[0], $occidIds)){
							fputcsv($imgOut, $imgData);
							$cnt++;
						}
					}
					fclose($imgIn);
					fclose($imgOut);
				}
			}
		}
	}
}
?>