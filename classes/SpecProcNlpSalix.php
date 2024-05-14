<?php
include_once($SERVER_ROOT.'/classes/SalixUtilities.php');
include_once($SERVER_ROOT.'/classes/OccurrenceUtilities.php');

/* This class extends the SpecProcNlp class and thus inherits all public and protected variables and functions
 * The general idea is to that the SpecProcNlp class will cover all shared Symbiota functions (e.g. batch processor, resolve state, resolve collector, test output, etc)
 */
/* The general approach of the algorithm is as follows:
	The label is pre-formatted and separated into lines, broken both at existing newlines and also at some semi-colons and periods.
	Some separated lines are re-joined if the previous one ends in words such as by, at, on, etc.
	Each field is processed one at a time, starting with Latitude/Longitude.  Order matters.
	For each field (in general):
		Lines are evaluated, ranked and sorted for probability of containing that field.
		Relevant content is focused on in the most probable line(s) (using various techniques), extracted and added to the Results array
		Content that was extracted is generally deleted from the line so that other fields won't mistakenly grab it.
*/


class SpecProcNlpSalix
	{
	private $conn;
	private $wordFreqArr = array();
	private $ResultKeys = array("catalogNumber","otherCatalogNumbers","family","scientificName","sciname","genus","specificEpithet","infraspecificEpithet","taxonRank","scientificNameAuthorship","recordedBy","recordNumber","associatedCollectors","eventDate","year","month","day","verbatimEventDate","identifiedBy","dateIdentified","habitat","","substrate","occurrenceRemarks","associatedTaxa","verbatimAttributes","country","stateProvince","county","municipality","locality","decimalLatitude","decimalLongitude","verbatimCoordinates","minimumElevationInMeters","maximumElevationInMeters","verbatimElevation","recordEnteredBy","dateEntered","ignore");
			//A list of all the potential return fields
	private $Results = array(); //The return array
	private $Assigned = array(); //Indicates to what line a field has been assigned.
	private $PregMonths ="(\b(jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec|january|february|march|april|june|july|august|september|october|november|december|enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre|janvier|febrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre|janeiro|febreso|marco|maio|junho|julho|setembro|outrubo|novembro|dezembro)\b\.?)";
	private $LabelLines=array(); //The main array of lines of the label.
	private $VirginLines=array(); //Lines unmodified by the program.  Used to pinpoint location of words in the line even after they have been removed.
	private $PregStart = array(); //An array of regular expressions indicating the start words for many fields.
	private $Family = ""; //Made global as a tentative family for other algorithms to consider
	private $LichenFamilies =  array(); //A list of all lichen families.  Mainly useful during debug, but helps to identify which specimens may have substrates.
	private $FamilySyn = array(); //List of family synonyms to help interpret old labels
	private $StatScore = array(); //The wordstats score of each line
	private $LineStart = array(); //One element for each line indicating if it starts with a start word.
	private $Label = "";

	function __construct()
		{
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		set_time_limit(7200);
		}

	function __destruct()
		{
		if(!($this->conn === false)) $this->conn->close();
		}

	//Parsing functions
	public function parse($RawLabel)
		{
		$this->Label = $RawLabel;
		if(strlen($this->Label) < 20)
			return $this->Results;  //Return empty array, since label is too short to parse.
		$dwcArr = array();


		//Set the keys for a few arrays to the names of the return fields
		$this->Results = array_fill_keys($this->ResultKeys,'');
		$this->Assigned = array_fill_keys($this->ResultKeys,-1);
		$this->PregStart = array_fill_keys($this->ResultKeys,'');
		$this->GetLichenNames(); //Get list of Lichen families to help find substrates
		$this->GetFamilySyn();



		//******************************************************************************************************************************
		// Do lots of preformatting on the input label text
		//******************************************************************************************************************************
		$this->Label = str_replace("\r\n\r\n","\r\n",$this->Label);
		if(strpos($this->Label,"°") > 0)
			{//The degree symbol is the main character above 122 that must be preserved.
			$this->Label = str_replace("°","17*17",$this->Label);
			}

		//Convert everything to English letters.  This may need to be modified for some users...
	    $this->Label = preg_replace("/[áàâãªä]/u","a",$this->Label);
		$this->Label = preg_replace("/[ÁÀÂÃÄ]/u","A",$this->Label);
		$this->Label = preg_replace("/[ÍÌÎÏ¡]/u","I",$this->Label);
		$this->Label = preg_replace("/[íìîï]/u","i",$this->Label);
		$this->Label = preg_replace("/[éèêë]/u","e",$this->Label);
		$this->Label = preg_replace("/[ÉÈÊË]/u","E",$this->Label);
		$this->Label = preg_replace("/[ôóòôõºö]/u","o",$this->Label);
		$this->Label = preg_replace("/[ÓÒÔÕÖ]/u","O",$this->Label);
		$this->Label = preg_replace("/[úùûüú]/u","u",$this->Label);
		$this->Label = preg_replace("/[ÚÙÛÜ]/u","U",$this->Label);
		$this->Label = preg_replace("/[’‘‹›‚]/u","'",$this->Label);
		$this->Label = preg_replace("/[“”„]/u",'"',$this->Label);
		$this->Label = preg_replace("/[«»]/u",'-',$this->Label);
		$this->Label = str_replace("–","-",$this->Label);
		$this->Label = str_replace(" "," ",$this->Label);
		$this->Label = str_replace("ç","c",$this->Label);
		$this->Label = str_replace("Ç","C",$this->Label);
		$this->Label = str_replace("ñ","n",$this->Label);
		$this->Label = str_replace("Ñ","N",$this->Label);
		$this->Label = str_replace("¿","-",$this->Label);
		$this->Label = str_replace("£","L",$this->Label);
		/* Was converting to hyphen.  May cause problems.
		for($i=0;$i<strlen($this->Label)-4;$i++)
			{ //Multi-byte characters still remaining after the above subsitutions are converted to hyphens here.
			if(ord($this->Label[$i]) >= 226)
				$this->Label = substr($this->Label,0,$i)."-".substr($this->Label,$i+3);// was +5
			else if(ord($this->Label[$i]) >= 224)
				$this->Label = substr($this->Label,0,$i)."-".substr($this->Label,$i+4);
			else if(ord($this->Label[$i]) >= 192)
				$this->Label = substr($this->Label,0,$i)."-".substr($this->Label,$i+3);
			}
		*/
		//Try just deleting rather than converting to hyphen.
		for($i=0;$i<strlen($this->Label)-4;$i++)
			{ //Multi-byte characters still remaining after the above subsitutions are converted to spaces here.
			if(ord($this->Label[$i]) >= 226)
				$this->Label = substr($this->Label,0,$i).substr($this->Label,$i+3);// was +5
			else if(ord($this->Label[$i]) >= 224)
				$this->Label = substr($this->Label,0,$i).substr($this->Label,$i+4);
			else if(ord($this->Label[$i]) >= 192)
				$this->Label = substr($this->Label,0,$i).substr($this->Label,$i+3);
			}

		//An attempt to convert to UTF-8, though it doesn't seem to work very well.  Hence the routines above.
		setlocale(LC_ALL,"en_US");
		if(mb_detect_encoding($this->Label,'UTF-8') == "UTF-8")
			{
			$this->Label = iconv("UTF-8","ISO-8859-1//TRANSLIT",$this->Label);
			$this->Label = str_replace("í","i",$this->Label);
			}
		$this->Label = str_replace("17*17","°",$this->Label);

		if(strlen($this->Label) < 10)
			return;

		//A few replacements to format the label making it easier to parse

		//Eliminate the "dupl" in determiner line
		$this->Label = preg_replace("(det(.)? dupl(.)?)i","Det.",$this->Label);
		//A fairly rare instance where both collector and determiner are same person on the same line. Split into two lines.
		$this->Label = preg_replace("([\n\r]{1,2}((leg|coll|col).? (&|and) (det).?)(.+)[\n\r]{1,2})i","\n$2. $5\n$4.$5",$this->Label);
		//Make sure space between NSEW cardinal directions and following digits, for Lat/Long
		$this->Label = preg_replace("(([NESW]\.?)(\d))","$1 $2",$this->Label);
		$this->Label = preg_replace("((\d)([NESW]))","$1 $2",$this->Label);
		//Make sure space between altitude and indicator (ft or m)
		$this->Label = preg_replace("((\d)((m|ft)(\s|.)))","$1 $2",$this->Label);
		//Make sure space between period and capital letter, such as an initial followed by a name.
		$this->Label = preg_replace("((\.)([A-Z]))","$1 $2",$this->Label);

		//Sometimes the double quote in Lat/Long gets OCRd as a single.  Look for likely instances and correct.
		$this->Label = preg_replace("(([0-9]{1,2}\'\s?[0-9]{1,2})(\')(\s?[NSEW]))","$1\"$3",$this->Label);
		//Sometimes the minute single quote mark gets OCRd as a 1 (one) or l (el).  Look for and correct.
		$this->Label = preg_replace("(\b([0-9]{1,2})1\s?([0-9]{1,2}\")(\s?[NSEW]\b))","$1'$2$3",$this->Label);

		//Sometimes year gets separated to next line from day/month.
		$Preg = "(([0-9]{1,2}\s+\b{$this->PregMonths})\.[\s]+(\b[0-9]{4,4}\b))i";
		$this->Label = preg_replace($Preg,"$1, $4",$this->Label);

		$Preg = "(\b([0-9]{1,2})([XVI]+)([0-9]{2,4})\b)";
		$this->Label = preg_replace($Preg,"$1 $2 $3",$this->Label);

		//echo $this->Label."<br>";;
		//Connect some lines when joined by a connecting preposition or hyphen
		$PrepList = "(of|to|by|at|in|over|under|on|from|along|beside|with|de|cerca|en|desde)";
		$this->Label = preg_replace("(\b".$PrepList."\s?[\r\n]{1,2})i","$1 ",$this->Label);//Prepositions at end of line
		$this->Label = preg_replace("(\s(&)\s?[\r\n]{1,2})i"," $1 ",$this->Label);//Ampersand at end of line
		$this->Label = preg_replace("([\r\n]{1,2}\b".$PrepList."\b)","$1",$this->Label);//Prepositions at start of line
		$this->Label = preg_replace("(\b([A-Z]?[a-z]{2,10})- ?[\n\r]{1,2}([a-z]{2,10}))","$1$2",$this->Label);//Hyphen at end of line breaking a word in two.

		//Remove double spaces and tabs
		$this->Label = str_replace("\t"," ",$this->Label);
		while(strstr($this->Label,"  ") !== false)
			$this->Label = str_replace("  "," ",$this->Label);

		//Remove (?) <>.  Perhaps should leave in...?  Would a collector ever write e.g. "Found > 500 m. elevation"? "Collected < 100 meters from stream"?
		$this->Label = str_replace("<","",$this->Label);
		$this->Label = str_replace(">","",$this->Label);
		$this->Label = str_replace("^"," ",$this->Label);
		$this->Label = str_replace("’","'",$this->Label);
		$this->Label = str_replace("°;","°",$this->Label);

		//Catch cases where zero or 1 mis-OCR'd as oh, I or el.
		$FromTo = array("l"=>"1","O"=>"0","I"=>"1");
		foreach($FromTo as $From=>$To)
			{
			$this->Label = preg_replace("(([-0-9/.])".$From."([0-9/.]))",'${1}'.$To.'${2}',$this->Label);
			//echo "(\b$From([0-9/.]))<br>";
			$this->Label = preg_replace("(\b$From([0-9/.]))","$To$1",$this->Label);
			//$this->Label = preg_replace("(\bI([0-9/.]))","1$1",$this->Label);
			$this->Label = preg_replace("(([a-z]{2,}\s)0(\.\s[A-Z][a-z]{2,20}))",'${1}'."O".'${2}',$this->Label);
			}
		$this->Label = str_replace("(M0)","(MO)",$this->Label);

		//Separate at semicolons -- Removed to see if it works better.
		//$this->Label = str_replace(";","\r\n",$this->Label);

		$this->InitStartWords();//Initialize the array of start words, which are hard coded in the function.

		//Separate lines at a start words followed by a colon.
		foreach($this->PregStart as $Start)
			{
			if($Start == "")
				continue;
			//Need to customize the start word regular expression a little.
			$Start = str_replace(")\b)i",")\b:)",$Start);
			$Start = str_replace("(^(","((",$Start);
			$this->Label = preg_replace($Start,"\n$1",$this->Label);
			}

		//A series of rare but easy to correct OCR errors. (A series of one so far..., but more are expected)
		$this->Label = str_ireplace("Jon!","Joni",$this->Label);

		//Remove empty lines
		$this->Label = str_replace("\r\n\r\n","\r\n",$this->Label);
		//$this->Label = str_replace("/","\r\n",$this->Label);

		//echo $this->Label."<br>";
		//Split lines at semicolons and periods.  But not at periods unless the preceding is
		//at least 4 lower case letters.  This preserves abbreviations.
		$this->LabelLines = preg_split("[(?<=[a-z]{4,4})\.|(?<=[a-z]);|\n]",$this->Label,-1,PREG_SPLIT_DELIM_CAPTURE);
		//echo "Line 185:".$this->Label."<br>";

		//Remove lines less than 3 characters long
		$L = 0;
		while($L < count($this->LabelLines))
			{
			$this->LabelLines[$L] = trim($this->LabelLines[$L]," \t");
			if(strlen($this->LabelLines[$L]) <3)
				unset($this->LabelLines[$L]);
			$L++;
			}
		$this->LabelLines = array_values($this->LabelLines); //renumber the array

		//Cases where the institute (capitalized) gets included in another line.  Separate them.
		$L=0;
		while($L < count($this->LabelLines))
			{
			if(preg_match("(\A[^a-z]+(of|de|the)[^a-z]+\Z)",$this->LabelLines[$L]))
				$this->LabelLines[$L] = strtoupper($this->LabelLines[$L]); //Single small word uncapitalized, can confuse so capitalize it.
			if(preg_match("(\b(INSTITUTE|INSTITUTO|MUNICIPAL|HERBARIUM|UNIVERSITY|BOTANICAL|GARDEN|RESERVA|NATIONAL|MUSEUM|MUSEO)\b)",$this->LabelLines[$L],$match))
				{
				$Found = preg_match("(([^A-Z]{2,20})\s([^a-z0-9]+)\Z)",$this->LabelLines[$L],$match);
				if($Found > 0)
					{
					if(strlen($match[2]) > 20)
						{
						$this->LabelLines[$L] = str_replace($match[2],"",$this->LabelLines[$L]);
						array_splice($this->LabelLines,$L+1,0,$match[2]);  //Break the line so the capitalized stuff is on a separate line.
						}
					}
				}
			$L++;
			}

		$this->LabelLines = array_values($this->LabelLines); //renumber the array again

		//Break each line up into an array of words resulting in a two-dimensional array -- $this->LabelArray
		//This may be phased out as regular expressions take over most of the work.  Let's hope!
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$OneLine = $this->LabelLines[$L];
			$Words = str_replace('\'','',$OneLine);
			$Words = str_replace('-',' ',$Words);
			$WordsArray = str_word_count($Words,1);
			$this->LabelArray[] = $WordsArray;
			}

		$this->InitStartLines();//Creates an array $LineStart where the element for each line indicates if it has a start word.

		//Save preliminary wordstat measurement of each line.  Avoids some repeated calls later.
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$OneStat = array();
			$this->ScoreOneLine($L, $Field, $Score);
			$OneStat['Field'] = $Field;
			$OneStat['Score'] = $Score;
			$this->StatScore[$L] = $OneStat;
			}
		//$this->PrintLines();
		//Save the lines as they are before fields are removed or modified.
		$this->VirginLines = $this->LabelLines;
		//*************************************************************
		//Here's where the individual fields get called and hopefully filled

		//Order is important.
		//Easy-to-find fields are done first, and then removed so as to not confuse other fields.
		//Example:  LatLong is easy to find because of degree symbols, sequences of numbers, etc.  And by finding LatLong first, we can then remove it from locality.

		//Second, knowing one field can help find another related field.
		//Example:  If we find recordedBy, then we know eventDate and recordNumber are probably very near, likely on the same line, so we can use that to rank the lines.
		//Example:  If we find LatLong, then we know Locality should be near, probably on the same line.
		$this->GetLatLong();
		$this->GetScientificName();
		$this->GetName('recordedBy');
		$this->GetName('identifiedBy');
		$this->GetEventDate("recordedBy","eventDate");
		$this->GetEventDate("identifiedBy", "dateIdentified");
		$this->GetRecordNumber();
		$this->GetElevation();
		$this->GetAssociatedTaxa();
		$this->GetCountryState();
		$this->GetWordStatFields();

		//Clean up the Results array and remove empty fields
		foreach($this->Results as $Key=>$Val)
			{
			if($Val == "")
				unset($this->Results[$Key]);
			else
				$this->Results[$Key] = trim($Val);
			}
		$this->Results['SALIXVersion'] = "0.905";
		return $this->Results;
	}

//************************************************************************************************************************************
//***************** Scientific Name Functions ****************************************************************************************
//************************************************************************************************************************************

	//**********************************************
	private function GetScientificName()
		{
		//Should not be after the associated Taxa.  Do a check afterwards.
		$this->FindFamilyName();//Look for family name first.  Can help locate sciname, and help validate sciname.
		$match=array();
		$ScoreArray = array();
		$MaxLine = 0;
		$Max = -100;
		for($L=0;$L<count($this->LabelLines);$L++)
			{//Check each line for possible scientific name.  Score the return value by a few parameters.
			$Score=0;
			//echo "Checking {$this->LabelLines[$L]}<br>";
			$Found = preg_match("((\A[A-Z][a-z]{3,20}) ([a-z]{4,20}\b))", $this->LabelLines[$L],$match);
			if($Found === 1)//Looks like a sciname and is at the beginning of the line.
				{
				$Score += 2 + $this->ScoreSciName($match[1],$match[2]);//Increase score based on how close it is to a valid scientific name
				//echo "1) {$match[1]} {$match[2]}, Score = $Score<br>";
				$FoundAuthor = preg_match("((\A[A-Z][a-z]{3,20}) ([a-z]{4,20}\b)[\S\s]+(\b[A-Za-z]{1,20}\.))", $this->LabelLines[$L]);
				if($FoundAuthor)//Looks like an author at the end
					$Score += 5;
				}
			else
				{
				$Found = preg_match("((\b[A-Z][a-z]{3,20}) ([a-z]{4,20}\b))", $this->LabelLines[$L],$match);
				if($Found ===1) //Looks like scientific name, though not near the beginning of the line
					$Score = $this->ScoreSciName($match[1], $match[2]);
				else
					{
					$Found = preg_match("((\A[A-Z]{3,20}) ([A-Z]{4,20}\b))", $this->LabelLines[$L],$match); //Might be all capitals
					if($Found ===1) //Looks like scientific name, though not near the beginning of the line
						$Score = $this->ScoreSciName($match[1], $match[2]);
					}
				}
			//echo "First, $Score,  {$this->LabelLines[$L]}<br>";

			if(preg_match("(\([A-Z][a-z\.])",$this->LabelLines[$L]) === 1)
				{
				$Score += 5; //Could be the author
				}
			if($Found === 0)
				{ //Finally check for a Genus
				$Found = preg_match("((\b[A-Z][a-z]{3,20}))", $this->LabelLines[$L],$match);
				if($Found ===1)
					$Score = $this->ScoreSciName($match[1]," ");
				}
			$PosDeduction = 0;
			if($Found ===1)
				{
				if($this->Family != "")
					{
					$PosDeduction = abs($L-$this->Assigned['family']); //Reduce the score if far from the family name
					//echo "PosD = $PosDeduction for {$this->LabelLines[$L]}<br>";
					}
				else
					$PosDeduction = $L-4;//Or reduce the score if far from line 3, which is about where the Family is usually listed.
				if($PosDeduction > 3)
					$PosDeduction = 3;

				$Score -= $PosDeduction;
				$ScoreArray[$match[0]] = $Score;
				//echo "$Score,  {$match[0]}<br>";
				}
			//Check for county, state our country
			if(count($match) > 1){
				$query = 'SELECT geoterm FROM geographicthesaurus WHERE geolevel = 60 AND geoterm = "'.$match[1].'" LIMIT 1';
				$result = $this->conn->query($query);
				if($result->num_rows > 0) $ScoreArray[$match[0]] =  0;
				$result->free();
				//echo "Found ".$result->num_rows." for state<br>";
				$query = 'SELECT geoterm FROM geographicthesaurus WHERE geolevel = 50 AND geoterm = "'.$match[1].'" LIMIT 1';
				$result = $this->conn->query($query);
				if($result->num_rows > 0) $ScoreArray[$match[0]] =  0;
				$result->free();
			}
			if($Score > $Max)
				{
				$Max = $Score;
				$MaxLine = $L;
				}
			}
		foreach($ScoreArray as $Key=>$Score)
			$ScoreArray[$Key] += floor($Score/10);

		asort($ScoreArray);//Sort the array, and then...
		end($ScoreArray); //Select the last (highest) element in the scores array
		$SciName = trim(key($ScoreArray));
		//$this->printr($ScoreArray,"SciName");
		if(count($ScoreArray) == 0 || max($ScoreArray) <7 || (10-$ScoreArray[$SciName])/(strlen($SciName)) > .2)
			{ //Didn't find it this way.  Try brute force, checking each two-word combination
			for($L=0;$L<count($this->LabelLines);$L++)
				{
				for($W=0;$W<count($this->LabelArray[$L])-1;$W++)
					{
					$Word1=mb_convert_case($this->LabelArray[$L][$W],MB_CASE_TITLE);
					$Word2=mb_convert_case($this->LabelArray[$L][$W+1],MB_CASE_LOWER);
					if($this->ScoreSciName($Word1,$Word2) >=10)
						{
						$this->FillInSciName($Word1." ".$Word2,$MaxLine);
						return;
						}
					}
				}
			return;
			}
		$this->FillInSciName($SciName,$MaxLine);//Need to check family to make sure it matches.
		}

	//**********************************************
	private function FindFamilyName()
		{//First look for "....aceae of" something
		$PotentialFamily = "";
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$Found = preg_match("((\b\w{2,20}(aceae))\s(of)\s(.*))i",$this->LabelLines[$L],$match);
			if($Found === 1)
				{
				//$this->printr($match,"ACEAE match:");
				$query = "SELECT family from omoccurrences WHERE family LIKE '{$match[1]}' LIMIT 1";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					{
					$this->Family = mb_convert_case($match[1],MB_CASE_TITLE);
					$this->AddToResults('family',$this->Family,$L);
					//echo "Family on line $L<br>";
					return;
					}
				}
			else if($PotentialFamily == "" && $L < 4)
				{
				$Found = preg_match("((\b\w{2,20}(aceae))\b)i",$this->LabelLines[$L],$match);//Replace above with case insensitive version
				if($Found === 1)
					{ //If not found as "...aceae of...", then use the first "...acea..." found.
					//$this->printr($match,"ACEAE match:");
					$query = "SELECT family from taxstatus WHERE family LIKE '{$match[1]}' LIMIT 1";
					$result = $this->conn->query($query);
					if($result->num_rows > 0)
						{
						$PotentialFamily = mb_convert_case($match[1],MB_CASE_TITLE);
						$FamilyLine = $L;
						//echo "Found Family on line $L<br>";

						}
					}
				}
			}
			//If something better was found, we won't get to this point.  But if we do, then use the most likely alternative
			if($PotentialFamily != "")
				{
				$this->Family = $PotentialFamily;
				$this->AddToResults('family',$PotentialFamily,$FamilyLine);
				}
		}


	//**********************************************
	private function CheckOneSciName($query, $L)
		{//Check the database.  If sciname is found and is in the family (if known), add to database.
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			$OneLine = $result->fetch_assoc();
			if($this->Family == "" || strtolower($OneLine['family']) == strtolower($this->Family))
				{//Either matches the family, or family not known.
				$this->AddToResults('family', $OneLine['family'],-1);
				$this->FillInSciName($OneLine['sciname'],$L);
				return true;
				}
			}
		return false;
		}

	//**********************************************
	private function ScoreSciName($First, $Second, $IgnoreFamily=false)
		{ //Find the closest match in the taxa table, and return a score.  Higher is better.
		//First is Genus, Second is specific epithet.
		//Start by checking the full name (Genus species).  Slowly reduce the number of pieces to check, accepting the closest match.
		$Score=0;
		$CloseSci="";
		$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$First $Second' LIMIT 1";
		//echo "Query=$query<br>";
		$result = $this->conn->query($query);
		if(!is_object($result))
			return 0;
		if($result->num_rows == 1)
			{//Found perfect match
			//echo "Perfect match<br>";
			$CloseSci = "$First $Second";
			$Score= 10;
			}
		if($Score < 10)
			{//Make the last half of specific epithet wild.
			$Piece2 = substr($Second, 0, floor(strlen($Second)/2));
			$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$First $Piece2%'";
			$result = $this->conn->query($query);
			if($result->num_rows >0) //Found some matches to Genus spec...
				$Score= $this->LevenshteinCheck("$First $Second",$result,$CloseSci);
			}
		if($Score < 10)
			{//Make the whole specific epithet wild
			$query = "SELECT sciname FrOM taxa WHERE UnitName1 LIKE '$First'";
			//echo "Query = $query<br>";
			$result = $this->conn->query($query);
			if($result->num_rows >0)  //Found matches to Genus.
				{
				$Score= $this->LevenshteinCheck("$First $Second",$result,$CloseSci);
				//echo "Check sci: $First<br>";
				}
			}
		if($Score < 10 && strlen($Second) > 3)
			{//Look for the specific epithet only
			$query = "SELECT sciname from taxa WHERE UnitName2 LIKE '$Second' LIMIT 1";
			if(!$IgnoreFamily && $this->Family != "")
				$query = "SELECT ts.family, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.UnitName2 LIKE '$Second' AND ts.family like '{$this->Family}' LIMIT 1";
			//echo $query."<br>";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				return 5;
			}
		if(!$IgnoreFamily && $this->Family != "")
			{//If we are already sure about the family, then check if the selected sciname is actually in that family.
			$query = "SELECT ts.family, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname LIKE '$CloseSci' AND ts.family like '{$this->Family}' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows == 0)
				{
				if(isset($this->FamilySyn[$this->Family]))
					{
					$Fam = $this->FamilySyn[$this->Family];
					$query = "SELECT ts.family, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname LIKE '$CloseSci' AND ts.family like '$Fam' LIMIT 1";
					$result = $this->conn->query($query);
					if($result->num_rows != 0)
						return $Score;
					}
				//Deduct 20 for not in known family";
				//echo "Not in family<br>";
				return -20;
				}
			}
		return $Score;
		}

	//**********************************************
	private function LevenshteinCheck($SciName, $result, &$CloseSci)
		{//Use levenshtein comparison to find closest match in the set of scinames and return the score
		// Return the closest scientific name too (called by address as CloseSci)
		//This is not used to correct OCR errors, but rather to improve detection of the scientific name which may contain errors
		$Best=100;
		$Len = strlen($CloseSci);
		while($OneSci = $result->fetch_assoc())
			{
			$Dist = levenshtein(trim($OneSci['sciname']),trim($SciName));
			if($Dist < $Best)
				{
				//echo "$SciName matched to {$OneSci['sciname']} scores $Dist<br>";
				$Best = $Dist;
				$CloseSci = $OneSci['sciname'];
				}
			}
		if($Best > 1 && $Len < 6)
			$Best += (8-$Len);
		return(10-$Best);//A perfect match will return a score of 10
		}


	//**********************************************
	private function FillInSciName($SciName,$L)
		{//Add scientific name to the Results array if it is in the right family
		 //If family not known, then determine family.
		 //Then look for infra-specific epithet and author

		$NameArray = array();
		$NameArray = explode(" ",$SciName);
		$Genus = $NameArray[0];
		$Author="";
		if(count($NameArray) > 1)
			{
			$Species = $NameArray[1];
			$SciName = $Genus." ".$Species;
			}
		else
			{
			$Species = "";
			$SciName = $Genus;
			}

		$this->AddToResults('sciname',$SciName,$L);
		if($this->Family == "")
			{ //Family was not already known, so find it from the taxstatus table
			$query = "SELECT ts.family FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid WHERE t.sciname LIKE '$SciName' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows == 1)
				{
				$OneLine = $result->fetch_assoc();
				$this->Family = $OneLine['family'];
				$this->AddToResults('family',$this->Family, -1);
				}
			}
		else if($this->Results['family'] == "")
			$this->AddToResults('family',$this->Family, -1);


		if(!$this->GetInfraSpecificEpithet($SciName,$Species,$L))
			{
			$query = "Select Author from taxa where SciName LIKE '$SciName' LIMIT 1";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				{
				$OneReturn = $result->fetch_assoc();
				$Author = $OneReturn['Author'];
				$this->AddToResults('scientificNameAuthorship',$Author,$L);
				$this->LabelLines[$L] = str_ireplace($SciName,"",$this->LabelLines[$L]);
				}
			}
		}

	//**********************************************
	private function GetInfraspecificEpithet($SciName, $Species, $L)
		{
		$TaxonPreg = "((\b(ssp|var|varietas|forma|for)\.?)\s([a-z]{3,20})(\s[A-Za-z\(\)&\. ]*)?)";
		$match = array();
		$Line = $this->Assigned['sciname'];
		$Found = preg_match($TaxonPreg,$this->LabelLines[$L],$match);
		if($Found !== 1 && $L < count($this->LabelLines)-1) //Infraspecific might be on the next line
			$Found = preg_match($TaxonPreg,$this->LabelLines[++$L],$match);
		if($Found === 1)
			{
			$Infra = $match[3];
			$Rank = $match[1];
			$this->AddToResults('taxonRank',$Rank,$L);
			$this->AddToResults('infraspecificEpithet',$Infra,$L);
			$this->AddToResults('scientificName',trim($SciName." ".$Rank." ".$Infra),$L);
			if(count($match) > 4)
				$this->AddToResults('scientificNameAuthorship',$match[4],$L);
			return true;
			}
		return false;
		}


//************************************************************************************************************************************
//********************* Associated Taxa **********************************************************************************************
//************************************************************************************************************************************

	private function GetAssociatedTaxa()
		{
		$SplitPoints = array();
		$RankArray = $this->RankAssociatedTaxa();
		if(count($RankArray) == 0)
			return;
		//$this->printr($RankArray,"AssTaxaRank");
		//All non-asociated taxa lines have been removed from RankArray
		reset($RankArray);
		$Start = key($RankArray);
		end($RankArray);
		$End = key($RankArray);
		while($End >= count($this->LabelLines))
			$End--;
		//foreach($RankArray as $L=>$Value)
		for($L=$Start;$L<=$End;$L++)
			{
			//echo "Rank {$RankArray[$L]}, {$this->LabelLines[$L]}<br>";
			$this->RemoveStartWords($L,'associatedTaxa');
			$TempString = $this->LabelLines[$L];
			$SciString = $TempString;
			if($L == $Start || $L == $End)
				{
				$SplitPoints = $this->SplitString($TempString, $Field, $Score1);
				if($SplitPoints[1] > 0)
					{
					//$this->printr($SplitPoints,"SplitPoints");
					if($End > $Start)
						{
						if($L==$Start)
							$SplitPoints[1] = strlen($TempString);
						else if($L == $End)
							$SplitPoints[0] == 0;
						}
					$SciString = substr($TempString,$SplitPoints[0],$SplitPoints[1]-$SplitPoints[0]); //Indicates the point to split apart.
					$this->LabelLines[$L]=str_replace($SciString,"",$this->LabelLines[$L]);
					//echo "Now: {$this->LabelLines[$L]}<br>";
					}
				}

			//$SciString = $this->RemoveStartWordsFromString($TempString,'associatedTaxa');
			//echo "Adding $SciString to AT<br>";
			$this->AddToResults("associatedTaxa", $SciString,$L);
			$this->LabelLines[$L] = str_replace($SciString,"",$this->LabelLines[$L]);
			$this->LabelLines[$L] = str_ireplace("with","",$this->LabelLines[$L]);
			//$this->LabelLines[$L] = "";
			}
		}


	private function SplitString($TempString, &$Field, &$Score)
		{ //Find where an Associated Species series ends and something else begins.  Only works for wordstats fields.

		$Found = preg_match_all("(([w|W]ith) ([A-Z][a-z]{4,20} ([a-z]{4,20})))",$TempString, $match, PREG_OFFSET_CAPTURE);
		if($Found)
			return array($match[2][0][1],strlen($TempString));

		$ScoreArray = array();
		$Found = preg_match_all("(\b[A-Za-z]{1,20}\b)",$TempString,$WordsArray, PREG_OFFSET_CAPTURE);//Break the string up into words
		$FieldScoreArray = array();
		$SciScoreArray=array();

		for($w=0;$w<count($WordsArray[0])-1;$w++)
			{
			$Loc = $WordsArray[0][$w][1];
			if(trim($WordsArray[0][$w+1][0] == "sp"))
				$Phrase = $WordsArray[0][$w][0];
			else
				$Phrase = $WordsArray[0][$w][0]." ".$WordsArray[0][$w+1][0]; //Two word phrase
			$Score = $this->SingleWordStats($Phrase);//Get word stats score for these two words
			$Phrase = trim(str_replace(" sp.","",$Phrase));
			$Phrase = trim(str_replace(" sp ","",$Phrase));
			//echo "Phrase = $Phrase, Score=$Score, ";
			$FieldScoreArray[$w] = $Score;
			$SciScore = 0;
			if(preg_match("([A-Z][a-z]{3,20} [a-z]{3,20})",$Phrase))
				{
				$query = "SELECT sciname FrOM taxa WHERE sciname LIKE '$Phrase' LIMIT 1";
				//echo $query."<br>";
				$result = $this->conn->query($query);
				if($result->num_rows == 1)
						$SciScore += 1;
				}
			if($SciScore == 0)
				if(preg_match("([A-Z][a-z]{3,20})",$WordsArray[0][$w][0]))
					{
					$query = "SELECT sciname FrOM taxa WHERE UnitName1 LIKE '{$WordsArray[0][$w][0]}' LIMIT 1";
					//echo $query."<br>";
					$result = $this->conn->query($query);
					if($result->num_rows == 1)
						$SciScore += 1;
					}
			if($SciScore == 0)
				if(preg_match("([a-z]{3,20})",$WordsArray[0][$w][0]))
					{
					$query = "SELECT sciname FrOM taxa WHERE UnitName2 LIKE '{$WordsArray[0][$w][0]}' LIMIT 1";
					$result = $this->conn->query($query);
					if($result->num_rows == 1)
						$SciScore += 1;
					}
			if($SciScore == 0)
				if(preg_match("(\b(ssp|var|forma)\b)",$Phrase) > 0)
					$SciScore += 1;
			if($SciScore == 0)
				if(preg_match("((and|&) [A-Z][a-z]{3,20})",$Phrase) > 0)
					$SciScore += 1;
			$SciScoreArray[$w] = $SciScore;
			//echo ", SciScore = $SciScore<br>";
			if($SciScore > 8)//It's a sciname
				$FScore = 0;
			else if($SciScore <= 0)
				$FScore = $Score+5;//It's not a sciname.
			else
				$FScore = floor($Score/$SciScore);
			if($Loc > 1 && $Loc < strlen($TempString) - 5)
				{
				if(strpbrk(substr($TempString,$Loc-2,5),".;") !== false)
					$FScore += 10;
				}
			//echo "FScore=$FScore<br>";
			$ScoreArray[$w] = $FScore;
			}

		$AverageField = 0;
		for($w=0;$w<count($FieldScoreArray)-1;$w++)
			$AverageField += $FieldScoreArray[$w];
		if(count($FieldScoreArray) == 0)
			return array(0,0);
		//echo "<br><br>";
		for($w=0;$w<count($FieldScoreArray)-1;$w++)
			{
			$SciScoreArray[$w] = ($SciScoreArray[$w] + $SciScoreArray[$w+1])/2;
			$SciScoreArray[$w] *= $AverageField;
			//echo $FieldScoreArray[$w].", ".$SciScoreArray[$w]."<br>";
			}
		$w=count($FieldScoreArray)-1;
		$SciScoreArray[$w] *= $AverageField;
		//echo $FieldScoreArray[$w].", ".$SciScoreArray[$w]."<br>";
		$Limit = 2*$AverageField/5; //Arbitrarily cut off at 3/4

		//$this->printr($SciScoreArray,"SSA Before pruning");
		//Now find a cluster of high numbers, hopefully on beginning or end of the string.
		foreach($SciScoreArray as $Key=>$Sci)
			if($Sci < $Limit)
				unset($SciScoreArray[$Key]);
		//$this->printr($SciScoreArray,"SSA After pruning");
		if(count($SciScoreArray) > 1) // Greater than what?
			{
			reset($SciScoreArray);
			$Start = key($SciScoreArray);
			if($Start < 4)
				$Start = 0;
			if($Start > 0)
				{
				if(preg_match("([a-z]{3,20})",$WordsArray[0][$Start][0]) && preg_match("([A-Z][a-z]{3,20})",$WordsArray[0][$Start-1][0]))
					$Start--; //It's the specific epithet, but previous word is probably the genus.
				}
			end($SciScoreArray);
			$LastKey = key($SciScoreArray);
			//echo "LastKey = $LastKey.  {$WordsArray[0][$LastKey][0]}<br>";
			//echo "Count: ".count($WordsArray[0])."<br>";
			if(count($WordsArray[0]) - $LastKey < 4)
				$End = strlen($TempString);
			else if($LastKey < count($WordsArray[0]))
				$End = $WordsArray[0][$LastKey+1][1];
			else
				$End = strlen($TempString);
			return array($WordsArray[0][$Start][1], $End);
			}
		else
			return array(0,0);


		die("Shouldn't be here");
		//$this->printr($SciScoreArray,"SSA after filter");
		for($w=0;$w<count($ScoreArray)-1;$w++)
			{
			if($ScoreArray[$w] > 30 && $ScoreArray[$w+1] > 20)
				return $WordsArray[0][$w][1];//Return the offset where the line should be split
			}
		return "0";
		}


	//**********************************************
	private function RankAssociatedTaxa()
		{//Each line is scored for probability of containing associated taxa
		//The array of lines is sorted by score and then reversed so the line of maximum probability is first, etc.

		$RankArray = array();
		$match = array();
		for($L=0;$L<count($this->LabelLines);$L++)
			{//Score the line
			//echo "{$this->LabelLines[$L]}<br>";
			$Rank = 0;
			//if(count($this->LabelArray[$L]) < 2)
			//	$Rank = -10; //Not likely if the line is only one word long.
			if($this->Assigned['sciname'] == $L)
				$Rank -=100;
			if($this->LineStart[$L] == "associatedTaxa" && !$this->Assigned['associatedCollectors'] != $L)
				{
				$Rank = 100;
				}
			if($this->Assigned['sciname'] == $L || $this->Assigned['associatedCollectors'] == $L)
				$Rank-=100; //This line is either the scientific name line, or associated collectors line.  Can't be associated species line.
			if($this->LineStart[$L] == 'substrate')
				$Rank -= 100;
			if($this->LineStart[$L] == 'habitat')
				{
				$Rank -= 100;
				//echo "Assigning -100 to {$this->LabelLines[$L]}<br>";
				}
			$CommaCount = substr_count($this->LabelLines[$L], ","); //Commas indicate a list, perhaps of associated species.
			if($CommaCount > 2)
				$CommaCount = 2;
			$Rank += $CommaCount;
			if($this->Assigned['associatedCollectors'] != "" && $this->Assigned['associatedCollectors'] != $L)
				{//As long as associated collectors is clearly not on this line, then the word "associated" means this is probably the line.
				if(strpos($this->LabelLines[$L],"associated") !== false)
					$Rank += 20;
				}
			$Found = preg_match("([w|W]ith ([A-Z][a-z]{4,20}) ([a-z]{3,20}))",$this->LabelLines[$L],$match);
			if($Found === 1 && $this->Assigned['associatedCollectors'] != $L)
				{
				$Rank+= 20;
				if($this->ScoreSciName($match[1],"",true))
					$Rank += 90;
				$Found=0;
				}
			else
				{
				$Found = preg_match("([w|W]ith ([A-Z][a-z]{4,20}))",$this->LabelLines[$L],$match); //With followed by a potential sciname is a strong indicator
				if($Found === 1 && $this->Assigned['associatedCollectors'] != $L)
					{//Found the word "with", and it isn't for associated collectors.
					$Rank+= 10;
					if($this->ScoreSciName($match[1],"",true))
						$Rank += 90;
					}
				}
			$Rank += (preg_match_all("(([A-Z][a-z]{3,20}) ([a-z]{4,20}))",$this->LabelLines[$L],$match))/2; //Add a point for each potential sciname
			$Rank += preg_match_all("(\b(by|ssp)\b)",$this->LabelLines[$L]); //Add a point for each ssp
			if($Rank < 10 && count($match[0]) > 0)
				{
				$Score = $this->ScoreSciName($match[1][0],$match[2][0],true);
				if($Score > 0)
					$Rank += $Score;
				if(count($match[0]) > 1)
					{
					$Score = $this->ScoreSciName($match[1][1],$match[2][1],true);
					if($Score > 0)
						$Rank += $Score;
					}
				}
			$Rank += preg_match_all("([a-z], )",$this->LabelLines[$L]); //Add for S. mirabilis... format
			if(preg_match("(\A([A-Z])\. [a-z]{3,20}\Z)",$this->LabelLines[$L],$match) > 0)
				{
				//$this->printr($match,"This match");
				$Rank+=5;
				if($L > 0 && $RankArray[$L-1] > 10 && $match[1] == $this->LabelLines[$L-1][0])
					$Rank += 5;
				}
			//echo "$L $Rank, {$this->LabelLines[$L]}<br>";
			//echo "Rank 14 = $Rank<br>";
			if($Rank >= 6)
				$RankArray[$L] = $Rank;
			}
		//Find maximum Rank
		$Max = 0;
		$MaxLine = -1;
		foreach($RankArray as $L=>$Val)
			{
			if($Val > $Max)
				{
				$Max = $Val;
				$MaxLine = $L;
				}
			}

		if($MaxLine < 1)
			return $RankArray;
		//Work up from this line, including lines as long as their rank is high enough, if it's not clearly the first line.
		$Start = $MaxLine;
		$End = $MaxLine;
		if($Max < 99)
			{//Only if it is clearly not the first line.
			for($L = $MaxLine-1;$L > 0;$L--)
				{
				if(isset($RankArray[$L]) && $RankArray[$L]> 5)
					{
					//echo "Set Start to $L<br>";
					$Start = $L;
					}
				else
					break;
				}
			}
		//Work down from this line in the same way.
		for($L = $MaxLine+1; $L < count($this->LabelLines);$L++)
			{
			if(isset($RankArray[$L]) && $RankArray[$L]> 5)
				{
				//echo "Set End to $End<br>";
				$End = $L;
				}
			else
				break;
			}
		//echo "Start  = $Start: {$this->LabelLines[$Start]}, End = $End<br>";
		//If the list contains several lines, make sure there isn't a mis-OCR causing false end to the list
		$TestEnd = $End+2;
		//while($TestEnd < count($this->LabelLines)-2 && $RankArray[$TestEnd] >= 10)
		while(isset($RankArray[$TestEnd]) && $RankArray[$TestEnd] > 5)
			{
			$End = $TestEnd;
			$TestEnd ++;
			}
		//echo "Then Start  = $Start, End = $End<br>";

		//$this->printr($this->Assigned,"Assigned");
		//Now $Start indicates the first line that has AT, and $End indicates the last.
		if($RankArray[$Start] >= 100)
			{
			foreach($RankArray as $Key=>$Value)
				if($Key < $Start)
					unset($RankArray[$Key]);
			}
		else
			{
			for($A = $Start - 1;$A >= 0;$A--)
				{
				if(preg_match("(\b[a-z]{3,20}\b)",$this->LabelLines[$A] == 0) && preg_match("([A-Z]\. [a-z]{3,20})") == 0)
					unset($RankArray[$A]);
				if($A < $this->Assigned['sciname'])
					unset($RankArray[$A]);

				}
			}
		for($A = $End + 1;$A < count($this->LabelLines);$A++)
			unset($RankArray[$A]);
		//$this->printr($RankArray,"Reduced RA");
		/*
		for($L=$Start+1;$L<count($this->LabelLines);$L++)
			{
			//echo "Check {$this->LabelLines[$L]}<br>";
			if(preg_match("(\A([A-Z][a-z]{3,20})( [a-z]{3,20})?\Z)",$this->LabelLines[$L],$match))
				$RankArray[$L] = 10;
			else
				break;
			}
		*/
		//asort($RankArray);//Sort the array of scores...
		//$RankArray = array_reverse($RankArray, true);//Then reverse order to bring most probable to the first position
		return $RankArray;
		}




//************************************************************************************************************************************
//******************* Lat/Long Functions *********************************************************************************************
//************************************************************************************************************************************

	//******************************************************************
	private function GetLatLong()
		{
		//Determine which line is most likely to contain Lat/Long by counting probable elements
		$RankArray = array();
		$Start = 0;$End = 0;
		$match=array();
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			//echo "$L, {$this->LabelLines[$L]}<br>";
			$RankArray[$L] = 0;
			if($this->CheckStartWords($L, 'verbatimCoordinates'))
				$RankArray[$L] = 100;
			if(preg_match_all("(\b[NSEW]\b)",$this->LabelLines[$L],$match) == 2)
				$RankArray[$L] += 5;
			$RankArray[$L] += preg_match_all("([0-9]{2,3}+[\?°\*\"\' ])",$this->LabelLines[$L],$match);
			if(strpos($this->LabelLines[$L],"°") > 0)
				$RankArray[$L] += 5; //A degree symbol pretty much clinches it for Lat/Long, though they often get mis-OCR'd
			}
		asort($RankArray);
		//$this->PrintRank($RankArray,"LL Rank");
		end($RankArray); //Select the last (highest) element in the scores array
		if(current($RankArray) > 1)
			$L=key($RankArray);
		else
			return;
		if(stripos($this->LabelLines[$L],"utm") !== false)
			{
			if($this->GetUTM($L))
				return;
			}
		if(preg_match("(N \d{1,2} \d{1,2} \d{3,3}, E \d{1,2} \d{1,2} \d{3,3})",$this->LabelLines[$L]))
			{
			if($this->GetUTM($L,true))
				return;
			}
		if(strpos($this->LabelLines[$L],"°") > 0 && $RankArray[$L] < 10)//Found degree symbol, but rank is a little low.  Maybe it's split over two lines
			{
			if($L > 0 && strpos($this->LabelLines[$L-1],"°") > 0)
				$OneLine = $this->LabelLines[$L-1]." ".$this->LabelLines[$L];
			else if($L < count($this->LabelLines)-1 && strpos($this->LabelLines[$L+1],"°") > 0)
				$OneLine = $this->LabelLines[$L]." ".$this->LabelLines[$L+1];
			else
				$OneLine = $this->LabelLines[$L];
			}
		else
			$OneLine = $this->LabelLines[$L];
		$Preg = "([0-9]{1,3}\.[0-9]{3,8}[,°:; ])";
		if($this->PregLatLong($Preg,$L,$OneLine))
			return;

		//Replace "O" for "Oeste" with "W" for "West"
		$OneLine = preg_replace("/(\d[\"\'][ ]?)(0|O)/",'$1W',$OneLine);
		$OneLine = preg_replace("(\bOeste)","W",$OneLine);
		$OneLine = preg_replace("(\bNorte)","N",$OneLine);
		//Put together the modules to build a Lat/Long in several formats
		$Preg = array();
		$Preg['dir'] = "[ ]*[NSEW][\.,: ]*";
		$Preg['deg'] = "[0-9\.]+[°\* ]+";
		$Preg['min'] = "[0-9\.]+[\', ]*";//Used to be a comma next to the last space
		//$Preg['sec'] = '[ ]*(?:[0-9\.]+")*';
		$Preg['sec'] = '[ ]*([0-9.]+")*';
		$Preg['decimal'] = '(([0-9\. ]+[°\* ]*[NSEW][\., ]*))';

		if($this->PregLatLong($Preg['dir'].$Preg['deg'].$Preg['min'].$Preg['sec'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['dir'].$Preg['deg'].$Preg['min'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['deg'].$Preg['min'].$Preg['sec'].$Preg['dir'],$L,$OneLine))
			return;
		if($this->PregLatLong($Preg['deg'].$Preg['min'].$Preg['dir'],$L,$OneLine))
			return;
		$this->PregLatLong($Preg['decimal'],$L,$OneLine);
		}

	//**********************************************
	private function GetUTM($L,$Baker = false)
		{
		$match=array();
		$TempString = $this->LabelLines[$L];
		if($Baker)
			{
			$Preg = "(N (\d{1,2}) (\d{1,2}) (\d{3,3}), E (\d{1,2}) (\d{1,2}) (\d{3,3}))";
			$Found = preg_match($Preg,$this->LabelLines[$L],$match);
			$Verb = $match[0];
			$UTM = "12 ".$match[4].$match[5].$match[6]." ".$match[1].$match[2].$match[3];
			}
		else
			{
			$Found = preg_match("((utm|UTM[ :-]*)([0-9]{1,2}\s*[A-Z]\s+[0-9]{6,8}\s*[EN]\s+[0-9]{6,8}\s*[EN]))",$TempString,$match);
			if(!$Found)
				return false;
			$TempString = $match[2];
			$Verb = $match[0];
			$UTM = preg_replace("(([0-9]{1,2})\s*([A-Z]\s+[0-9]{6,8})\s*([EN]\s+[0-9]{6,8})\s*([EN]))","$1$2$3$4$5",$TempString);
			}
		$OU = new OccurrenceUtilities;
		$LL = $OU->parseVerbatimCoordinates($UTM,"UTM");
		if(count($LL) > 0)
			{
			$this->AddToResults('verbatimCoordinates',$Verb,$L);
			$this->AddToResults('decimalLatitude',$LL['lat'],$L);
			$this->AddToResults('decimalLongitude',$LL['lng'],$L);
			return true;
			}
		return false;
		}


	//**********************************************
	private function PregLatLong($Preg,$L,$OneLine="")
		{
		//Use the regular expression passed as "$Preg" on $OneLine.  If $OneLine is empty, use LabelLine[$L].
		if($OneLine == "")
			$OneLine = $this->LabelLines[$L];
		$match=array();
		$Preg = '(('.$Preg.'))';
		//echo "Preg=$Preg<br>";
		//echo "$OneLine<br>";
		$Found = preg_match_all($Preg, $OneLine,$match);
		//$Found = 2;

		if($Found > 1)
			{
			//echo "Here<br>";
			$VerbLatLong = ($match[0][0]." ".$match[0][1]);
			//Correct where the decimal was rendered a space by OCR
			//Need to keep an eye on this
			$VerbLatLong = preg_replace("((\d{1,3}) (\d{3,6}) ([NESW]))","$1.$2 $3",$VerbLatLong);
			$OU = new OccurrenceUtilities;
			$LL = $OU->parseVerbatimCoordinates($VerbLatLong);
			if(count($LL) > 0)
				{
				$this->AddToResults('verbatimCoordinates',$VerbLatLong,$L);
				$this->AddToResults('decimalLatitude',$LL['lat'],$L);
				$this->AddToResults('decimalLongitude',$LL['lng'],$L);
				for($Line=$L-1;$Line < $L+2;$Line++)
					{
					if($Line < 0)
						continue;
					if($Line >= count($this->LabelLines))
						continue;
					$this->LabelLines[$Line] = str_replace($match[0][0],"",$this->LabelLines[$Line]);
					$this->LabelLines[$Line] = str_replace($match[0][1],"",$this->LabelLines[$Line]);
					}
				return true;
				}
			}
		return false;
		}



//************************************************************************************************************************************
//******************* Elevation Functions ********************************************************************************************
//************************************************************************************************************************************

	//******************************************************************
	private function GetElevation()
		{//Determine which lines are most probable to have an Elevation.
		//NOTE:  Still doesn't capture maximum elevation.
		//global $this->Label;
		$PregWords1 = "([\d,]{2,5})";
		$PregWords2 = "(\b(meters|m.|m|feet|ft.|ft|msnm)\b)";
		$PregWords3 = "(\b(elevation|elev|altitude|alt)\b)";
		$RankArray = array();
		$match=array();
		$Start = 0;$End=0;
		for($L=0;$L<count($this->LabelArray);$L++)
			{
			$RankArray[$L] = 0;
			if($this->LineStart[$L] == 'minimumElevationInMeters')
				$RankArray[$L]+=100;

			//Adjust for some possible confusing lines
			if($this->LineStart[$L] == 'recordedBy')
				$RankArray[$L]-=100;//elevation won't be here
			if($this->LineStart[$L] == 'locality')
				$RankArray[$L]+=10;//Often in this line
			if($this->LineStart[$L] == 'habitat')
				$RankArray[$L]+=5;//Sometimes in this line

			//Look for the PregWords and adjust rank.
			$Found = preg_match_all($PregWords1, $this->LabelLines[$L],$match);
			if($Found > 0)
				$RankArray[$L] += 5;
			$Found = 10*preg_match_all($PregWords2, $this->LabelLines[$L],$match);
			$Found = 20*preg_match_all($PregWords2, $this->LabelLines[$L],$match);
			$RankArray[$L] += $Found;
			if(preg_match("([0-9,]{2,6})",$this->LabelLines[$L]) != 1)
				$RankArray[$L] -= 100;  //There must be an appropriate numeric entry somewhere for elevation
			}
		//Sort the lines in rank order
		asort($RankArray);
		$RankArray = array_reverse($RankArray, true);
		//$this->PrintRank($RankArray,"Elev");
		//Now have an array of probable lines sorted most probable first.
		//ScoreArray will be filled with possible elevations and a score for each.
		$ScoreArray = array();
		foreach($RankArray as $L=>$Value)
			{//Iterate through the lines from most to least likely, cutting off if value gets below a threshold.
			//echo "Testing $L, $Value: {$this->LabelLines[$L]}<br>";
			if($Value < 5)
				break;
			$TempString = $this->LabelLines[$L];
			//$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*[about ]*([0-9,-]+)([\s]*)(m(eter)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
			$Found = preg_match("(([a|A]lt|[e|E]lev)[\S]*[\s]*[about ]*([0-9, -]+)\s?\d?([\s]*)(m(eter|snm)?s?\b|f(ee)?t?\b|\'))i",$TempString,$match);
			if($Found === 1) //Embedded in a line with number following key word, like "Found at elevation 2342 feet"
				{
				//$this->printr($match,"Elev match 1");
				$ScoreArray[$match[0]] = $Value+10;
				if(max($ScoreArray) == $Value+10)
					$FoundElev = $match[2];
				}
			if($Found !== 1)
				{
				$Found = preg_match("((ca\.?\s)?([0-9,]+)([ ]*)(m(eter|snm)?s?\b|f(ee)?t?\b|\')[\S]*[\s]*([a|A]lt|[e|E]lev)[ation]?[\S]*)",$TempString,$match);
				if($Found === 1)  //Same as above but with the key word Elevation/Altitude following the number
					{
				//$this->printr($match,"Elev match 2");
				$ScoreArray[$match[0]] = $Value+10;
				if(max($ScoreArray) == $Value+10)
					$FoundElev = $match[1];
					}
				}
			if($Found !== 1)
				{
				$Found = preg_match("(\A(ca\.?\s)?([0-9,]+)([ ]*)(m(eter|snm)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
				if($Found === 1) //Found at beginning of the line, but without "Elevation" or "Altitude" indicator.  Just feet and meters.
					{
					//$this->printr($match,"Elev match 3");
					$Score =$Value+2;
					if($match[1] > 1000 && $match[1] < 12000)
						$Score+= 2;//Increase score if altitude in reasonable range
					$ScoreArray[$match[0]] = $Score;
				if(max($ScoreArray) == $Score)
					$FoundElev = $match[1];
					}
				}
			if($Found !== 1)
				{//Least certain, just a number followed by feet or meters in middle of a line.  Could be height, distance, etc.
				$Found = preg_match_all("((ca\.?\s)?\b([0-9,]+)(\s?)(m(eter|snm)?s?\b|f(ee)?t?\b|\'))",$TempString,$match);
				if($Found >0)
					{
					//$this->printr($match,"Elev Match 4");
					$Score = $Value;
					$Elev = $match[0][0];
					if($Found > 1 && strpos($match[0][1],"m") > 1)
						$Elev = $match[0][1];
					if($Elev > 1000 && $Elev < 12000)
						$Score+= 2;//Increase score if altitude in reasonable range
					$ScoreArray[$Elev] = $Score;
				if(max($ScoreArray) == $Score)
					$FoundElev = $Elev;
					}
				}
			}

		//All potential altitudes are found and in $ScoreArray.  Now sort by most likely and accept the top one.

		if(count($ScoreArray) == 0)
			return;
		asort($ScoreArray);
		end($ScoreArray); //Select the last (highest) element in the scores array
		//$this->printr($ScoreArray,"ScoreArray");
		$TempString = key($ScoreArray);//Should be the best line.
		for($L=0;$L<count($this->LabelLines);$L++)
			if(strpos($this->LabelLines[$L],$TempString)!== false)
				{
				//$L = array_search($TempString, $this->LabelLines);
				$TempString = $this->RemoveStartWordsFromString($TempString,'minimumElevationInMeters');
				break;
				}
		if($L >= 0 && $L < count($this->LabelLines))
			{
			$this->LabelLines[$L] = $this->RemoveStartWordsFromString($this->LabelLines[$L],'minimumElevationInMeters');
			//echo "To this: {$this->LabelLines[$L]}<br>";
			}
		$Preg = "(\d+[a to-]+$TempString)";
		$Found = preg_match($Preg, $this->Label,$match);
		if($Found > 0)
			{
			$TempString = $match[0];
			$TempString = str_replace("to","-",$TempString);
			}


		$this->AddToResults('verbatimElevation', $TempString,$L);
		$Found = false;
		for($L=0;$L<count($this->LabelLines) && !$Found;$L++)
			if(strpos($this->LabelLines[$L],$TempString) !== false)
				{
				$this->LabelLines[$L] = str_replace($TempString,"",$this->LabelLines[$L]);
				$Found=true;
				}
		//Replace Spanish "a" with a hyphen
		$TempString = preg_replace("((\d\s?)(a)(\s?\d))","$1-$3",$TempString);
		//Remove comma from thousands place for simpler calculations
		$TempString = preg_replace("(([0-9]),([0-9]))","\\1\\2", $TempString);
		$TempString = trim(str_ireplace(array("altitude","elevation","about","ca"),"",$TempString));
		//echo "Tempstring = $TempString<br>";
		//$TempString = "1000 - 1200 m";
		$OU = new OccurrenceUtilities();
		$El = $OU->parseVerbatimElevation($TempString);
		$Found=false;
		//$this->printr($El,"El");
		if(isset($El['minelev']))
			{
			//$this->printr($El,"El Return");
			$Alt = $El['minelev'];
			if($Alt >= 0 && $Alt < 5000)
				{
				$this->AddToResults('minimumElevationInMeters',$Alt,$L);
				$Found=true;
				if(isset($El['maxelev']))
					$this->AddToResults('maximumElevationInMeters',$El['maxelev'],$L);
				}
			}
		if($Found)
			return;

		}


//************************************************************************************************************************************
//*********** Name Finding Routines **************************************************************************************************
//************************************************************************************************************************************

	private function GetName($Field)
		{//Ranks lines for probability, then looks for name pattern
		//Names are confirmed by checking the omoccurrences table for same name.
		//Names are rejected if they match
		global $NameRankArray;  //I know, but it simplifies things...
		if($this->Results[$Field] != "")
			return;
		$NameRankArray = $this->RankLinesForNames($Field);
		//$this->PrintRank($NameRankArray,"$Field");
		reset($NameRankArray);
		//echo "Current = ".current($NameRankArray)."<br>";
		if(current($NameRankArray) < 2 && $Field == "recordedBy")
			{
			$match=array();
			$Preg = "((Collector|Collected by:?)\s\b((([A-Z][a-z]{2,20} )|([A-Z][\.] ))([A-Z][\.] )?([A-Z][a-z]{2,20})\b))";
					//(Initial or first name), (optional middle initial), (last name).
			for($L=0;$L < count($this->LabelLines);$L++)
				{
				$Found = preg_match_all($Preg, $this->LabelLines[$L],$match);
				//$this->printr($match,"MidNameMatch");
				if($Found > 0)
					{
					$this->AddToResults($Field,$match[2][0],$L);
					$this->LabelLines[$L] = str_replace($match[0][0],"",$this->LabelLines[$L]);
					return;
					}
				}
			}


		foreach($NameRankArray as $L=>$Value)
			{
			//echo "Check ($Field): $L: {$this->LabelLines[$L]}<br>";
			if($Field=='identifiedBy' && $Value < 90)
				return; //Currently can't find identifiedBy unless there is a start word.  Need to find alternative clues.
			if($Value < 2)
				return; //Since array is sorted, no need to look at the rest
			$FieldLine = $this->Assigned[$Field];
			if($FieldLine > 0)
				{
				if($FieldLine > $L || $L - $FieldLine > 2)
					continue;
				}
			//echo "Checking $L: {$this->LabelLines[$L]}<br>";
			if($this->GetNamesFromLine($L,$Field))
				break;
			//echo "Here 1<br>";
			if($Field == 'recordedBy' && $this->Assigned['recordedBy'] >= 0 && $L > $this->Assigned['recordedBy']+1)
				break;//There shouldn't be any more associated collectors more than one line after main collector
			//echo "Here 2<br>";
			}
		return;
		}

	private function GetNamesFromLine($L,$Field,$TitleCase = false, $TempString = "")
		{
		global $NameRankArray;
		$ColNum = "";
		//echo "Testing $Field: {$this->LabelLines[$L]}<br>";
		$PName = "(\b(D')?[A-Z][a-z]{2,20}\b)";
		$PIn = "(\b[A-Z][. ]*)";
		$this->RemoveStartWords($L,'recordedBy');
		$match=array();
		if($TitleCase)
			$I = "i";
		else
			{
			$I = "";
			$TempString = $this->LabelLines[$L];
			}
		//Convert Mr. A. B. and Mrs. C. D. Smith to A. B. Smith, C. D. Smith.
		if(preg_match("(\b([DM]r\.?\s)($PIn\s$PIn)\s(and|&)\s(Mrs\.?\s)($PIn\s$PIn)\s$PName)$I", $TempString,$match) > 0)
			$this->LabelLines[$L] = str_replace($match[0],$match[2]." ".$match[10].", ".$match[7]." ".$match[10],$TempString);

		//Convert Robert. B. and Jane D. Smith to Robert B. Smith, Jane D. Smith.
		if(preg_match("(($PName\s$PIn?\s?)(and|&)(\s$PName\s$PIn?\s?)$PName)", $this->LabelLines[$L],$match) > 0)
			$this->LabelLines[$L] = str_replace($match[0],$match[1]." ".$match[8]." & ".$match[5]." ".$match[8],$TempString);

		//like:  Collected and prepared by John P. Smith
		if(preg_match("((\bcollected.+\bby\b)\s+([A-Za-z .]{2,25}))$I",$TempString,$match) > 0)
			{
			$Name = mb_convert_case($match[2], MB_CASE_TITLE);
			$this->AddToResults($Field,$Name,$L);
			$this->LabelLines[$L]= str_replace($match[1],"",$this->LabelLines[$L]);
			return true;
			}

		//(Initial) (middle name) (last name) followed by No or number
		if(preg_match_all("($PIn\s$PName\s$PName\s(No|[0-9]{2,6}))$I", $TempString,$match) > 0)
			{
			$match[0][0] = $match[1][0]." ".$match[2][0]." ".$match[3][0];
			if(preg_match("(\b(january|february|september|october|november|december)\b)",$match[0][0]) > 0)
				$Found = 0;
			}

		//(First name) (Middle name) (last name) No.
		if(count($match[0]) == 0 && preg_match_all("($PName\s$PName\s$PName\s(No|[0-9]{2,6}))$I", $TempString,$match) > 0)
			{
			$match[0][0] = $match[1][0]." ".$match[3][0]." ".$match[5][0];
			if($Field == "recordedBy")
				$ColNum = $match[7][0];
			if(preg_match("(\b(january|february|september|october|november|december)\b)",$match[0][0]) > 0)
				$Found = 0;
			}
		//(Initial or first name), (optional middle initial), (last name).  Most common case.
		if(count($match[0]) == 0)//Found == 0)
			preg_match_all("(($PName|$PIn)\s?$PIn?\s$PName)$I", $TempString,$match);

		//((Last name, init. init. init.) -- like Faria, J.E.Q.
		if(count($match[0]) == 0)//Found == 0)
			{
			$Preg = "(($PName),?\s?$PIn\s?$PIn\s?$PIn)$I";
			preg_match_all($Preg, $TempString,$match);
			}

		//((Last name, init. init.) -- like Staggemeier, V.G.
		if(count($match[0]) == 0)//Found == 0)
			{
			$Preg = "(($PName),?\s?$PIn\s?$PIn)$I";
			preg_match_all($Preg, $TempString,$match);
			}
		//echo "Second TempString=$TempString<br>";
		if(count($match[0]) != 0)//Found a match
			{
			for($N=0;$N<count($match[0]);$N++)
				{
				$Name = $match[0][$N];
				$Confirm = $this->ConfirmRecordedBy($Name);
				if($this->ConfirmRecordedBy($Name) < 0)
					continue;
				$Name = mb_convert_case($Name,MB_CASE_TITLE);
				$this->AddToResults($Field,$Name,$L);
				$this->RemoveStartWords($L,$Field);
				$this->LabelLines[$L] = str_ireplace("with","",$this->LabelLines[$L]);
				$this->LabelLines[$L] = trim(str_replace($match[0][$N],"",$this->LabelLines[$L])," ,;");
				if($ColNum != "")
					$this->AddToResults("recordNumber",$ColNum,$L);
				//if(strlen($this->LabelLines[$L]) > 5) //Just removed this, seemed to be causing problems and not sure why it is here. 6/3/15
				//	$this->GetNamesFromLine($L,$Field);

				if($L < count($this->LabelLines)-1 && preg_match("(\A$PName\Z)",$this->LabelLines[$L]) && preg_match("(\A$PName\Z)",$this->LabelLines[$L+1]))
					{//Catch the case where the given name on this line, surname is on the next line.  Fairly rare, but happens.
					$Name = $this->LabelLines[$L]." ".$this->LabelLines[$L+1];
					if($this->ConfirmRecordedBy($Name) >=0)
						{
						$this->AddToResults($Field,$Name,$L);
						$this->LabelLines[$L]="";
						$this->LabelLines[$L+1]="";
						}
					}
				if($Field == 'identifiedBy')
					return true;
				}
			if($Field == "recordedBy" && ($L < count($this->LabelLines)-1 && ($this->LineStart[$L+1] == "" || $this->LineStart[$L+1] == "associatedCollectors")))
				{
				if($NameRankArray[$L+1] > 5)
					return $this->GetNamesFromLine(++$L,$Field);//Recursively calls this routine to add possible associated collectors.
				}
			return true;
			}
		if(!$TitleCase)
			{
			$TempString = mb_convert_case($this->LabelLines[$L],MB_CASE_TITLE);
			$this->GetNamesFromLine($L,$Field,true,$TempString);
			}
		return false;
		}



	//**********************************************
	private function ConfirmRecordedBy(&$Name)
		{//Check for the name in the omoccurrences table.  Lower score if it looks like a university, herbarium, county, etc.
		$match=array();
		$Score = 0;
		if(strstr($Name,"'") !== false)
			$Name = mysqli_real_escape_string($this->conn,$Name);

		$PregNotNames = "(\b(arizona|municip|herbarium|agua|province|parish|port|university|mun|county|botanical|garden|planta[s]?|reserva|conserva|comunidad|pacific|date|north|south|canal|mountain|national|image|collection|island[s]?)\b)i";  //Known to be confused with names
		$Score -= 5*(preg_match_all($PregNotNames,$Name,$match));
		$query = "SELECT recordedBy FROM omoccurrences where recordedBy LIKE '$Name' LIMIT 1";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			{
			//echo "Num = ".$result->num_rows."<br>";
			return 10;
			}
		else
			{
			$query = "SELECT recordedBy FROM omoccurrences where recordedBy LIKE '$Name%' LIMIT 1";
			$result = $this->conn->query($query);
			//echo "Name $Name: $Score<br>";
			if($result->num_rows > 0)
				{
				//echo "Adding 10<br>";
				$Record = $result->fetch_assoc();
				$Name = $Record['recordedBy'];
				return 10;
				}
			//echo "Returning $Score from Confirm<br>";
			return $Score;
			}
		}



	//******************************************************************
	private function RankLinesForNames($Field)
		{//Determine which lines are most probable to have a name.  Also check for start words.
		 //Return array of names sorted in order of probability of having the $Field
		$ConflictFields = array('sciname','country','county','identifiedBy','associatedCollectors','recordedBy',"locality","family");
		$RankArray = array();
		$match=array();
		for($L=0;$L<count($this->LabelArray);$L++)
			{
			$RankArray[$L] = 0;
			$PregNotNames = "(\b(national|nacional|municip|trail|peak|mountain|herbarium|agua|province|university|mun|county|botanical|garden|forestal|pacific|island[s]?)\b)i";  //Known to be confused with names
			$RankArray[$L] -= 5*(preg_match_all($PregNotNames,$this->LabelLines[$L],$match));

			if(preg_match("(By[:\s]{0,2}(\b[A-Z][a-z]{3,20}\b)\s+(\b[A-Z][a-z]{3,20}\b))", $this->LabelLines[$L]))
				{
				$RankArray[$L] += 5;

				}
			if(count($this->LabelArray[$L]) < 2)
				{
				$RankArray[$L]-=100;
				continue;
				}
			if($this->LineStart[$L] == $Field)
				{
				//echo "Found $Field Start word in {$this->LabelLines[$L]}<br>";
				$RankArray[$L] += 1000;
				}
			if($this->StatScore[$L]['Score'] > 100)
				{
				//echo "$L: StatScore = {$this->StatScore[$L]['Score']}, {$this->StatScore[$L]['Field']}, {$this->LabelLines[$L]}<br>";
				if($this->StatScore[$L]['Field'] != "locality")
					$RankArray[$L] -= $this->StatScore[$L]['Score'];

				}
			foreach($ConflictFields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					//echo "SW for $F in {$this->LabelLines[$L]}<br>";
					if($F == $Field)
						{
						$RankArray[$L] += 100; //Increase if start word for this field
						continue; //Don't need to go on
						}
					if($F == 'sciname' || $F == 'country' || $F == 'county' || $F == 'family')
						{
						$RankArray[$L] -= 10; //Decrease if start word for another field
						//echo "$L Decrease 1<br>";
						}
					if($Field != 'identifiedBy' && $F == 'identifiedBy')
						$RankArray[$L] -= 100; //Decrease if start word for another field
					if($Field == 'recordedBy' && $F == 'associatedCollectors')
						{
						$RankArray[$L] -= 5; //Decrease, but not too much the associateds can be found later.
						if($L > 1 && $RankArray[$L-1] > 0)
							$RankArray[$L-1] += 5; //Decrease, but not too much the associateds can be found later.
						}
					if($Field == 'identifiedBy' && $F != 'identifiedBy')
						$RankArray[$L] -= 100; //Decrease if start word for another field
					}
				}
			if($this->Assigned['sciname'] == $L)
				$RankArray[$L] -= 100;
			if($Field == 'recordedBy' && $this->LineStart[$L] == 'recordNumber')
				{
				//echo "Recordnumber<br>";
				$RankArray[$L] += 10; //Decrease if start word for another field
				}
			if($this->SingleWordStats($this->LabelLines[$L],'locality') > 100)
				{
				$RankArray[$L] -= 10;
				}
			if($Field == 'associatedCollectors' && $this->Assigned['recordedBy'] > $L)
				$RankArray[$L] -= 110; //Associated Collectors should always follow main collector

			if($Field == 'identifiedBy' && $this->Assigned['recordedBy'] == $L)
				$RankArray[$L] -= 10; //Determiner rarely on same line as collector (?)

			if($this->Assigned['infraspecificEpithet'] == $L)
				{
				$RankArray[$L] -= 100; //Must be author instead of collector
				}
			$Found = preg_match("(\b(([A-Z][a-z]{2,20} )|([A-Z][\.] ))([A-Z][\.] )?([A-Z][a-z]{2,20}\b))",$this->LabelLines[$L]);
			if($Found === 1) //Looks like a name
				{
				$RankArray[$L] += 10;
				//echo "{$RankArray[$L]}: Looks like name:  {$this->LabelLines[$L]}<br>";
				}
			else if(preg_match("(\b(([A-Z]{2,20} )|([A-Z][\.] ))([A-Z][\.] )?([A-Z]{2,20}\b))i",$this->LabelLines[$L]))
				$RankArray[$L] += 5;
			//else
			{
				if($Field == "recordedBy" && preg_match("((\bcollected).+(\bby\b)\s+([A-Za-z .]{2,25}))i",$this->LabelLines[$L]) > 0)
					$RankArray[$L] += 10;
				else if($Field == "recordedBy" && preg_match("((\bcollected).+\b(by|in|at)\b)i",$this->LabelLines[$L]) > 0)
					{
					$RankArray[$L] -= 10;
					//echo "$L Reducing for collected in<br>";
					}
				//else
				//	$RankArray[$L] -= 10;
			}
			$Found = preg_match("( [0-9]{3,10} ?)",$this->LabelLines[$L]); //Add a little for a number -- could be date or collection number.
			if($Found > 0)
				{
				$RankArray[$L] += 3;
				if(preg_match("(\b(no|number|#)[. :]{1,2} \d{3,10})i",$this->LabelLines[$L]))
					$RankArray[$L] += 5;
				}
			$RankArray[$L] += 10*preg_match("(collected)i",$this->LabelLines[$L]); //The word "collected" without "by" isn't sure, but it helps
			$RankArray[$L] -= 3*preg_match("((\bft\b)|(\bm\b))",$this->LabelLines[$L]); //Subtract if looks like altitude
			$RankArray[$L] -= (3*preg_match_all("([0-9]\.[0-9])",$this->LabelLines[$L],$match)); //Decimal not likely to be in collection number or date
			$RankArray[$L] -= 2*preg_match_all("(\(|\)|�|\")",$this->LabelLines[$L],$match); //Parenthesis on the line probably mean author, not collector.  Degree looks like lat/long.  Quote looks like lat/long
			$RankArray[$L] += 5*preg_match($this->PregMonths."i",$this->LabelLines[$L]); //Add a little for a month -- could be collection date
			if($L < count($this->LabelLines)-1)
				$RankArray[$L] += 2*preg_match($this->PregMonths."i",$this->LabelLines[$L+1]); //Add a little for a month -- next line could be collection date
			if($L >0)
				$RankArray[$L] += 2*preg_match($this->PregMonths."i",$this->LabelLines[$L-1]); //Add a little for a month -- previous line could be collection date
			if($Field != 'identifiedBy' && preg_match("(\b(and|&)\b)",$this->LabelLines[$L])>0)
				{
				$RankArray[$L] += 4; // Could be associated collectors on the same line
				if(preg_match("(([a-z]{2,20}|[A-Z.]{2,2})\s(&|and)\s[A-Z][.a-z])",$this->LabelLines[$L]) > 0)
					{
					$RankArray[$L] += 5; //Capital after ampersand even better.
					}
				}
			if($Field == 'recordedBy' && strpos($this->LabelLines[$L]," s. n."))
				$RankArray[$L]+=10;
			}
		//Sort the lines in rank order

		asort($RankArray);
		return (array_reverse($RankArray, true));
		}



//**********************************************************************************************************************************
//*********** Event Date Routines **************************************************************************************************
//**********************************************************************************************************************************

	//******************************************************************
	private function GetEventDate($EventField, $Field)
		{
		//Used by both eventDate and identifiedDate, determined by $Field
		$m=0;$Year=0;$Day=0;$Date="";
		$FoundLine=0;
		$ReturnDate = array('Year'=>0,'Month'=>$m,'Day'=>0);
		$RankArray = $this->RankForDate($EventField, $Field);
		//$this->PrintRank($RankArray,$Field);
		foreach($RankArray as $L => $Value)
			{
			if($Value < 0)
				break;
			if($this->DateFromOneLine($Field,$L))
				return;
			}

		//Didn't find in a standard format.  Try "July 1992"
		foreach($RankArray as $L => $Value)
			{
			if($Value < 0)
				break;

			if($this->DateFromOneLine($Field,$L,true))
				return;
			}

		return;
		}

	//**********************************************
	private function RankForDate($EventField, $Field)
		{ //Rank lines for $EventField date
		$BadFields = array('minimumElevationInMeters','family','sciname');
		$RankArray = array();
		$RankArray = array_fill(0,count($this->LabelLines),0);
		if($this->Assigned[$EventField] != '-1')
			{
			$L = $this->Assigned[$EventField];
			$RankArray[$L] = 10; //Most likely on the same line as the event
			while(++$L < count($this->LabelArray)) //Diminishing likelihood on subsequent lines
				$RankArray[$L] = 10 - 3*($L-$this->Assigned[$EventField]);
			$L = $this->Assigned[$EventField];
			while(--$L >= 0) //Less likelihood on previous lines
				$RankArray[$L] = 8 - 2*($this->Assigned[$EventField]-$L);
			}
		for($L=0;$L<count($this->LabelArray);$L++)
			{//Various characteristics increase or decrease probability
			$TempString = $this->LabelLines[$L];
			foreach($BadFields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					$RankArray[$L] -= 100;
					}
				}
			if(preg_match("(\b(date|fecha)\b)i",$TempString) > 0)
				{//Found the word "Date" on the line
				$RankArray[$L] += 10;
				}

			if(preg_match("(\b\d+\b)",$TempString) !== 1)
				{// number RomanNumeral number format.  Probably a date.
				$Preg = "(([0-9]+[ ./-]{1,2})([IVX]{1,2})([ ./-]{1,2}[0-9]+))i";
				$Found = preg_match($Preg, $TempString,$match);
				if($Found > 0)
					$RankArray += 10; //Probable roman numeral
				else
					{
					$RankArray[$L] -= 100;
					continue;
					}
				}


			if($Field != "eventDate" && $this->Assigned['eventDate'] == $L)
				{//Not likely to find identifiedDate on the recordedby line
				$RankArray[$L] = -100;
				continue;
				}

			if($Field != "dateIdentified" && $this->Assigned['identifiedBy'] == $L)
				{//Not likely to find recordedBy date on identifiedBy line
				$RankArray[$L] = -100;
				continue;
				}

			if(preg_match("(\b(1[8-9]\d{2,2})|(20[01]\d{1,1})\b)",$TempString,$match)===1)
				{ //Year between 1800 and 2019 inclusive
				$Year = $match[0];
				$RankArray[$L]+=3;//Could be year between 1800 and 2019
				if($Year > 1950)
					$RankArray[$L] += 1;  //Last 60 some years, more likely
				if(Date("Y") - $Year < 3)
					$RankArray[$L] += 3;  //Last couple of years, a likely date
				}

			//Check numbers to see if fit the format of a month or day of month
			if(preg_match("((\b1[0|1|2]\b)|(\b[1-9]\b))",$TempString,$match)===1)
				$RankArray[$L] += 1; //Could be month.  Not worth much.

			if(preg_match("((\b[1-3]?[0-9]\b))",$TempString,$match)===1)
				if($match[0] < 32)
					$RankArray[$L] += 2; //Could be day of the month.

			$RankArray[$L] += 2*substr_count($this->LabelLines[$L],"/");//A slash is often found in a date.
			$Numeric=false;
			$Roman = false;
			if(strpos($this->LabelLines[$L],"X") || strpos($this->LabelLines[$L],"I"))
				$Roman = true; //Not doing anything with this yet.

			$TempString = strtolower($this->LabelLines[$L]);
			if(preg_match($this->PregMonths,$TempString) == 1)
				$RankArray[$L] += 10; //Month string appears in the line.
			if(preg_match("(\d{1,4})",$TempString) != 1)
					$RankArray[$L] -= 100;  //There must be an appropriate numeric entry somewhere for date

			}
		asort($RankArray);
		return (array_reverse($RankArray, true));
		}

	//**********************************************
	private function DateFromOneLine($Field, $L,$Partial=false)
		{//Checks several formats
		$RomanMonths = array("I"=>"Jan","II"=>"Feb","III"=>"Mar","IV"=>"Apr","V"=>"May","VI"=>"Jun","VII"=>"Jul","VIII"=>"Aug","IX"=>"Sep","X"=>"Oct","XI"=>"Nov","XII"=>"Dec");
		$match=array();
		$RealVDate = "";
		$VDate = "";
		$TempString = ($this->LabelLines[$L]);

		//Reformat expressions like:  "23 de abril de 1956" to "23 abril 1956"
		$TempString = preg_replace("((\d{1,2})\sde\s({$this->PregMonths})[\sde]{0,3}\s(\d{2,4}))","$1 $2 $5",$TempString);

		//echo "Getting date from {$this->LabelLines[$L]}<br>";
		if($Partial)
			$Preg = "(\b{$this->PregMonths}\s*(\b[0-9]{1,4}\b))i";
		else
			$Preg = "((\b[0-9]{1,4}\b)\s*{$this->PregMonths}[.,]*\s*(\b[0-9]{1,4}\b))i";
		$Found = preg_match($Preg,$TempString,$match);
		if($Found !== 1)
			{//Format April 21, 1929
			$Preg = "({$this->PregMonths}[.\s]*(\b[0-9]{1,4}\b)[.,]*\s*(\b[0-9]{1,4}\b)\s*)i";
			$Found = preg_match($Preg,$TempString,$match);
			if($Found)
				{
				$VDate = $match[2]." ".$match[3].", ".$match[4];
				$RealVDate = $match[0];
				}
			}
		if($Found !== 1)
			{//Format April, 1929
			$Preg = "({$this->PregMonths}[.,]?\s*(\b[0-9]{1,4}\b)\s*)i";
			$Found = preg_match($Preg,$TempString,$match);
			}
		if($Found !== 0)
			{
			if($VDate == "")
				{
				$VDate = $match[0];
				$RealVDate = $VDate;
				}
			}
		if($Found == 0)
			{ //Roman numeral for month
			$Preg = "(([0-9]+[ ./-]{1,2})([IVX]+)([ ./-]{1,2}[0-9]+))i";
			$Found = preg_match($Preg, $TempString,$match);
			if($Found >0)
				{
				$Month = $RomanMonths[strtoupper($match[2])];
				$VDate = trim($match[1]," .-")."-".$Month."-".trim($match[3]," .-");
				$RealVDate = $match[0];
				}
			}
		if($Found == 0)
			{//Day-AlphaMonth-Year
			$Preg = "(([0-9]+[ ./-]{1,2}){$this->PregMonths}([ ./-]{1,2}[0-9]{2,4}))i";
			$Found = preg_match($Preg, $TempString,$match);
			if($Found >0)
				{
				//$this->printr($match,"Day-Month-Year");
				$VDate = trim(str_replace("-"," ",$match[0]));
				$RealVDate = $match[0];
				}
			}
		if($Found == 0)
			{// day-month-year all numeric
			//echo "Checking $TempString: <br>";
			$Preg = "(\b([0-9]{1,2})[ ./-]+([0-9]{1,2})[ ./-]+([0-9]{2,4})\b)";
			$Found = preg_match($Preg, $TempString,$match);
			if($Found !== 0)
				{
				//echo "Found {$match[0]}<br>";
				$VDate = $match[0];
				$RealVDate = $VDate;
				}

			}
		if($Found == 0)
			{
			$Preg = "(\b(19[0-9]{2,2}|20[01][0-9])\b)";
			$Found = preg_match($Preg, $TempString,$match);
			if($Found !== 0)
				{
				//echo "Found {$match[0]}<br>";
				$VDate = $match[0];
				$RealVDate = $VDate;
				}

			}
		if($Found!== 0)
			{
			$FrenchMonths = array("janvier","febrier","mars","avril","mai","juin","juillet","aout","septembre","octobre","novembre","decembre");
			$VDate = str_replace($FrenchMonths,$RomanMonths,$VDate);//Converts french dates to English (Using the same Roman Numeral conversion array)
			$OU = new OccurrenceUtilities;
			$FormattedDate = $OU->formatDate($VDate);
			if($FormattedDate != "")
				{
				$FoundYear = preg_match("((\d{4,4})\-)",$FormattedDate,$match);
				//$this->printr($match,"FormattedDate");
				if($match[1] > date('Y') || $match[1] < 1700)
					return false;
				$this->AddToResults($Field, $FormattedDate,$L);
				if($RealVDate != "")
					$VDate = $RealVDate;
				$this->LabelLines[$L] = str_replace($VDate,"",$this->LabelLines[$L]);
				if($Field == "eventDate")
					$this->AddToResults("verbatimEventDate",$VDate,$L);
				$this->RemoveStartWords($L,$Field);
				return true;
				}
			}
		return;
		}

//************************************************************************************************************************************
//***************** RecordNumber Functions *******************************************************************************************
//************************************************************************************************************************************


//******************************************************************
	private function GetRecordNumber($L = -1)
		{//Find record number.  Typically look near recordedBy
		//May be recursively called with a specific line, but usually defaults to line -1
		$match=array();

		//Check for start word
		for($L1=0;$L1<count($this->LabelLines);$L1++)
			{
			if($this->LineStart[$L1] =='recordNumber' || $this->CheckStartWords($L1, 'recordNumber'))
				{
				$this->RemoveStartWords($L1,'recordNumber');
				$L = $L1;
				break;
				}
			}

		//Assume on the same line as recordedBy.  If no recordBy, then return empty.
		if($this->Assigned['recordedBy'] == "")
			return; //No collector, can't have collection number
		if($L==-1)
			$L = $this->Assigned["recordedBy"];//Find the collectors line

		if($L < 0)
			return; //No idea where to look.

		//Date will have already been removed from the line, so any number remaining is likely the collection number.
		$WordArray = preg_split("( )",$this->LabelLines[$L],-1,PREG_SPLIT_DELIM_CAPTURE);
		for($W=0;$W<count($WordArray);$W++)
			{//Check words one at a time for a match to typical collection number format.
			$Word= trim($WordArray[$W]);

			$PM=preg_match('(([A-Z-])?([#0-9]+-?)([0-9a-zA-Z]*)(-[0-9]*)?)',$Word,$match);
			if($Word != "" && !ctype_alpha($Word) && $PM!==0)
				{//Consists of digits, optional letters, and an optional hyphen.
				$Match = $match[0];
				if(preg_match("(($Match)°)",$this->LabelLines[$L]))
					continue;
				if(strpos($Word,"#") !== false && preg_match("([0-9])",$Word) === 0)
					{
					if($W < count($WordArray)-1 && preg_match("([0-9])", $WordArray[$W+1]) === 1)
						{
						$Match .= " ".$WordArray[$W+1];
						}
					}
				$this->AddToResults('recordNumber', $Match,$L);
				$this->LabelLines[$L] = str_replace($Match,"",$this->LabelLines[$L]); //Strip the collection number from the line so other parsing won't pick it up.
				return;
				}
			}
		if($L == $this->Assigned['recordedBy'] && $L < count($this->LabelLines)-1)
			$this->GetRecordNumber($L+1); //Recursively call to check the line after recordedBy, since it is often found there.
		}

//******************************************************************
//******************* Country/State Functions ***************************
//******************************************************************

	function GetCountryState()
		{
		//Look for phrases like "Plants of Arizona" or "Asteraceae of Brazil"
		$Label = str_replace("-"," ",$this->Label);
		$Found = preg_match("(([A-Za-z]{2,20}ACEAE)\s+(of)\s+(\b\S+[\b-])\s+(\b\S+\b)?)i",$Label,$match);
		if($Found !== 1)
			$Found = preg_match("((plants|plantas|flora|lichens|algae|fungi|cryptogams)\s(of|de|del|du)\s+(\b\S+\b)(\s?\b\S+\b)?)i",$Label,$match);
		if($Found)
			{// Found "Plants of...".  Look for state or country
			//echo "Found Plants of...<br>";
			$Name1 = trim($match[3]," .,;:");
			$Name2 = "";
			if(count($match) > 4)
				$Name2 = $Name1." ".trim($match[4]," .,;:");
			//Check first for two word names.
			$this->PlantsOf($Name2);
			//If not found, check for single name
			if($this->Results['country'] == "")
				$this->PlantsOf($Name1);
			//Not found, then check for other single name.
			if($this->Results['country'] == "" && count($match) > 4)
				$this->PlantsOf(trim($match[4]));
			}
		if($this->Results['country'] != "") //Found it.
			return;
		//"Plants of" didn't work.  Look for "xxx State".
		$match = $this->LabeledRegion("stateProvince");
		if($match != false)
			{ //Found "xxx province or state"
			$State = $match[1]." ".$match[2];
			//echo "State might be $State<br>";
			if(!$this->PlantsOf($State))
				$this->PlantsOf(trim($match[2]));
			}
		if($this->Results['country'] != "") //Found it.
			return;
		//Look for "xxx County".
		$match = $this->LabeledRegion("county");
		if($match != false)
			{ //Found "xxx County"
			$County = $match[1]." ".$match[2];
			//echo "County might be $County<br>";
			if(!$this->ScanForState($County))
				$this->ScanForState(trim($match[2]));
			}
		//Brute force, look for country with matching state
		$this->AnyCountry();
		if($this->Results['country'] != "")
			return;



		$this->SeekState(223,-1);
		if($this->Results['stateProvince'] != "")
			return;
		//echo "Still here<br>";
		//If coordinates have been found look in omoccurrences table for similar ..
		if($this->Results['decimalLatitude'] != "" && $this->Results['decimalLongitude'] != "")
			{
			$Lat = $this->Results['decimalLatitude'];
			$Long = $this->Results['decimalLongitude'];
			$this->GetFromLatLong($Lat, $Long);
			}
		}

	//*****************************************************************************
	private function LabeledRegion($Type)
		{ //Looks for a labeled region, such as "County: xxx"  or "Prov: yyy" on the label
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|vascular|university|specimen|botanical\b))i";
		$PrePreg = "((\b[A-Z][a-z]{1,20}\b)?[\s]*(\b[A-Z][a-z]{1,20}\b)[\s]*";
		switch($Type)
			{
			case "stateProvince":
				$Preg = "(\b(prov|province|provincia|state|estado|parish|par)\b[:. ]*)";
				break;
			case "county":
				$Preg = "(\b(County|Co)\b[: ]*)";
				break;
			default:
				return false;
			}
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			if(preg_match($BadWords,$this->LabelLines[$L]))
				continue;
			//echo "Preg=$Preg<br>";
			$Found = preg_match($Preg."i",$this->LabelLines[$L],$match);

			if($Found)
				{
				//echo "Found {$this->LabelLines[$L]}<br>";
				$FullPreg = $PrePreg.$Preg.")i" ;
				$Found = preg_match($PrePreg."(".$Preg."))i",$this->LabelLines[$L],$match);
				//echo $PrePreg."(".$Preg."))i<br>";
				if($Found == 0)
					{
					$FullPreg = "(".$Preg.$PrePreg."))i";
					$Found = preg_match($FullPreg,$this->LabelLines[$L],$match);
					if($Found)
						{
						//$this->printr($match,"");
						$match[1] = $match[4];
						$match[2] = $match[5];
						}
					}
				if($Found)
					{
					$match['Line'] = $L;
					//$this->printr($match,"Match");
					return $match;
					}
				}
			}
		return false;
		}

	//*****************************************************************************
	function PlantsOf($Name){ //Find country/state from "Plants of" statement.
		$countryArr = array();
		$query = 'SELECT geoThesID, geoTerm FROM geographicthesaurus WHERE geolevel = 50 AND geoterm = "'.$Name.'" LIMIT 1';
		$result = $this->conn->query($query);
		$countryArr = $result->fetch_assoc();;
		$result->free();
		if($countryArr){
			$this->AddToResults('country',$countryArr['geoTerm'],-1);
			$this->SeekState($countryArr['geoThesID'],-1);
			return true;
		}
		else{
			//echo "Here for $Name<br>";
			$match = $this->LabeledRegion('county');
			if($match != false){ //Found a county name on the label.  Check if $Name is a state that contains the county
				//echo "Here 1<br>";
				$County = $match[1]." ".$match[2];
				if(!$this->CheckCounty($County, $Name, $match['Line'])){
					$County = $match[2];
					if(!$this->CheckCounty($County, $Name, $match['Line'])){
						$County = $match[1];
						if(!$this->CheckCounty($County, $Name, $match['Line'])) return true;
					}
					else return true;
				}
				else return true;
			}
			//Didn't find a county.  Just look for the state
			$query = 'SELECT c.geoThesID AS countryId, c.geoterm AS countryName, s.geoThesID AS stateId, s.geoterm AS stateName
				FROM geographicthesaurus c INNER JOIN geographicthesaurus s ON c.geoThesID = s.parentID
				WHERE c.geolevel = 50 AND s.geolevel = 60 AND s.geoterm = "'.$Name.'"';
			$result = $this->conn->query($query);
			while($row = $result->fetch_assoc()){
				//$this->printr($OneState,"OneState");
				if(!$this->Results['stateProvince']){
					if($this->Results['decimalLatitude'] && $this->Results['decimalLongitude']){ //If we have lat/long, use that to confirm state
						if($this->CheckCoordinates($row['stateName'])){
							if($County = $this->ScanForCounty($row)){
								$this->AddToResults('county', $County,-1);
								for($L=0; $L<count($this->LabelLines); $L++){
									if(stripos($this->LabelLines[$L], $County)!== false){
										$this->LabelLines[$L] = str_ireplace($County." county","",$this->LabelLines[$L]);
										$this->LabelLines[$L] = str_ireplace($County, '', $this->LabelLines[$L]);
										$this->LabelLines[$L] = trim($this->LabelLines[$L], " ,;:");
										return true;
									}
								}
							}
						}
					}
					elseif($result->num_rows == 1 || preg_match("(\b({$row['countryName']})\b)", $this->Label)){
						if(isset($match['Line'])) $L = $match['Line'];
						else $L = -1;
						$this->AddToResults('stateProvince', $row['stateName'],$L);
						$this->AddToResults('country', $row['countryName'],$L);
						$County = $this->ScanForCounty($row);
						if($County != "") $this->AddToResults('county', $County, -1);
					}
				}
				if($this->Results['county'] != "")
					return true;
			}
			$result->free();
		}
	}

	//*****************************************************************************
	private function CheckCounty($County, $State, $L){ //Look for a match between the state and county.  If found, accept.
		$query = 'SELECT cr.geoThesID AS countryId, cr.geoterm AS countryName, s.geoThesID AS stateId, s.geoterm AS stateName, cy.geoterm AS countyName
			FROM geographicthesaurus cr INNER JOIN geographicthesaurus s ON cr.geoThesID = s.parentID
			INNER JOIN geographicthesaurus cy ON s.geoThesID = cy.parentID
			WHERE cy.geolevel = 50 AND s.geolevel = 60 AND cy.geolevel = 70 AND s.geoterm = "'.$State.'" AND cy.geoterm = "'.$County.'"';
		$result = $this->conn->query($query);
		if($row = $result->fetch_assoc()){
			//$L = $match['Line'];
			$this->AddToResults('county', $row['countyName'], $L);
			$this->AddToResults('country', $row['countryName'], $L);
			$this->AddToResults('stateProvince', $row['stateName'], $L);
			if($L >= 0) $this->LabelLines[$L] = trim(str_replace($County, '', $this->LabelLines[$L]), " ,;:");
			return true;
		}
	}

	//*****************************************************************************
	private function CheckCoordinates($State) {  //Given a state, see if there are in omoccurrences table states with similar coordinates
		$status = false;
		if($this->Results['decimalLatitude'] && $this->Results['decimalLongitude']){
			$Lat = $this->Results['decimalLatitude'];
			$Long = $this->Results['decimalLongitude'];
			$query =  'SELECT decimalLatitude, decimalLongitude, stateProvince, country, county FROM omoccurrences WHERE stateProvince = "'.$State.'" AND decimalLongitude IS NOT NULL LIMIT 5';
			$result = $this->conn->query($query);
			while($row = $result->fetch_array()){
				if(abs($Lat - $row['decimalLatitude']) < 2 && abs($Long - $row['decimalLongitude'] < 2)){
					$this->AddToResults('country', $row['country'], -1);
					$this->AddToResults('stateProvince', $row['stateProvince'], -1);
					$status = true;
				}
			}
			$result->free();
		}
		return $status;
	}



	//*****************************************************************************
	function ScanForState($County){
		//Given a county, come up with a list of possible states, then scan for them.
		$status = false;
		$maybeState = array();
		$query = 'SELECT s.geoThesID AS stateId, s.geoTerm AS stateName, c.geoTerm AS countyName
			FROM geographicthesaurus c INNER JOIN geographicthesaurus s ON c.geoThesID = s.parentID
			WHERE c.geolevel = 70 AND s.geolevel = 60 AND c.geoterm = "'.$County.'"';
		$result = $this->conn->query($query);
		while($row = $result->fetch_array()){
			if(preg_match('(\b'.$row['stateName'].'\b)i', $this->Label)) $maybeState[] = $row;
		}
		$result->free();
		if(count($maybeState) == 1){
			$this->AddToResults('county', $maybeState[0]['countyName'], -1);
			$this->AddToResults('stateProvince', $maybeState[0]['stateName'], -1);
			$this->AddCountry($maybeState[0]['stateId']);
			$status = true;
		}
		return $status;
	}


	//*****************************************************************************
	function ScanForCounty($OneState){
		//Given a state, find a county
		//First look for the word "County"...
		$retStr = '';
		$badCounties = array('island', 'park'); //These are much more likely to be false positives.
		$query = 'SELECT c.geoTerm AS countyName
			FROM geographicthesaurus s INNER JOIN geographicthesaurus c ON s.geoThesID = c.parentID
			WHERE s.geolevel = 60 AND c.geolevel = 70 AND s.geoThesID = "'.$OneState['stateId'].'"';
		//echo $query."<br>";
		$result = $this->conn->query($query);
		while($row = $result->fetch_assoc()){
			//echo "Checking {$row['countyName']}<br>";
			if(preg_match('(\b'.$row['countyName'].'\b)', $this->Results['recordedBy'])) continue;  //Rare case of collector name matching a county
			$pregTerm = '((\b'.$row['countyName'].'\b)(\scounty)?)i';
			if(preg_match($pregTerm, $this->Label, $match)){
				if(array_search(strtolower($match[1]), $badCounties) !== false && !isset($match[2])) continue;
				else $retStr = $row['countyName'];
			}
			if(preg_match("(Saint [A-Z][a-z]{2,20})", $row['countyName'])){
				$pregTerm = str_replace('Saint ', 'St. ', $pregTerm);
				if(preg_match($pregTerm, $this->Label)) $retStr = $row['countyName'];
			}
		}
		$result->free();
		return $retStr;
	}

	//*****************************************************************************
	function AddCountry($StateId){
		//Given the state Id from geographicthesaurus, find the country.
		$query = 'SELECT c.geoterm AS countryName
			FROM geographicthesaurus c INNER JOIN geographicthesaurus s ON c.geoThesID = s.parentID
			WHERE c.geolevel = 50 AND s.geolevel = 60 AND s.geoThesID = '.$StateId.' LIMIT 1';
		$result = $this->conn->query($query);
		if($row = $result->fetch_assoc()){
			$this->AddToResults('country', $row['countryName'], -1);
		}
		$result->free();
		return;
	}

	private function GetFromLatLong($Lat,$Long)
		{//If Lat/Long has been found previously, looks in the omoccurrences table for similar lat/long and checks the table's state/country to see if it will work with this label.
		//Slow since lat long unindexed in the table.  Left as a last resort.
		$Box = .5;
		$query = "SELECT country,stateProvince FROM omoccurrences where decimalLatitude between ".($Lat-$Box)." AND ".($Lat+$Box)." AND decimalLongitude between ".($Long-$Box)." AND ".($Long+$Box)." AND country IS NOT NULL LIMIT 5";
		//echo "$query<br>";
		$result = $this->conn->query($query);
		while($Loc = $result->fetch_assoc())
			{
			$Country = $Loc['country'];
			$State = $Loc['stateProvince'];
			for($L=0;$L < count($this->LabelLines);$L++)
				{ //Check each line to see if this state name shows up.
				if(preg_match("(\b($State)\b)i",$this->LabelLines[$L]))
					{
					if($this->Results['country'] == "" || $this->Results['country'] != $Country)
						$this->AddToResults('country',$Country,$L);
					$this->AddToResults('stateProvince',$State,$L);
					$this->LabelLines[$L] = preg_replace("(\b($State)\b)i","",$this->LabelLines[$L]);
					return true;
					}
				else if($this->Results['country'] == "" && preg_match("(\b($Country)\b)i",$this->LabelLines[$L]))
					{//Didn't fine the state, check for the country.
					$this->AddToResults('country',$Country,$L);
					}
				}
			}
		}

	//*****************************************************************************
	private function AnyCountry(){
		//Look through label for any country.  Require that a matching state be present.
		$query = 'SELECT geoThesID AS countryId, geoTerm AS countryName FROM geographicthesaurus WHERE geolevel = 50';
		$result = $this->conn->query($query);
		$fullLabel = preg_replace('(\bbrasil\b)i', 'Brazil', $this->Label);
		while($row = $result->fetch_assoc()){
			if(!$this->Results['country']){
				//$this->printr($row,"Country");
				if(preg_match('(\b{'.$row['countryName'].'}\b)i', $fullLabel)){
					//echo "Checking..<br>";
					$this->SeekState($row['countryId']);
				}
			}
		}
		$result->free();
	}


	//*****************************************************************************
	private function SeekState($CountryId, $m=-1){
		//Given the country (or if none assume USA), scan the whole label for a contained state.
		$status = false;
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|vascular|university|specimen|botanical\b))i";

		//Query the database for all the states in the given country.
		$query = 'SELECT geoTerm AS stateName, geoThesID AS stateId FROM geographicthesaurus WHERE parentID = '.$CountryId;
		//echo $query."<br>";
		$stateArray = array();
		$stateResult = $this->conn->query($query);
		//echo "Looking for state<br>";
		//Look in the most likely lines first.
		$RankArray = $this->RankCountryLines();
		//$this->PrintRank($RankArray,"States");
		foreach($RankArray as $L=>$Score){
			$stateResult->data_seek(0); //Set the query result pointer back to the start
			if(preg_match($BadWords, $this->LabelLines[$L])>0) continue;
			if($Score < 0) break; //No need to keep checking.  The rest of the lines probably contain misleading results.
			while($row = $stateResult->fetch_assoc()){
				//Check each potential state one at a time.
				//echo "Looking for {$OneState['stateName']}<br>";
				if(preg_match("(\b{$row['stateName']}\b)i",$this->LabelLines[$L]) > 0){
					//Found a state listed on the line.
					//echo "Found state {$OneState['stateName']}<br>";
					$stateArray[] = $row['stateName'];
					$StateId[] = $row['stateId'];
				}
			}
			if($stateArray){
				$lengths = array_map('strlen', $stateArray); //Get the length of each potential state name
				$index = $this->MaxKey($lengths); //Take the longest state name in the array.  Least likely to be a random match.
				$this->AddToResults('stateProvince', $stateArray[$index], $L);
				$this->LabelLines[$L]= str_ireplace($stateArray[$index], '', $this->LabelLines[$L]);

				//Look for a county within the state.
				$county = $this->ScanForCounty(array ('stateId' => $StateId[$index]));
				if(!$county) $this->AddToResults('county', $county, -1);
				if(!$this->Results['country']){
					$query = 'SELECT geoterm AS countryName FROM geographicthesaurus WHERE geoThesID = '.$CountryId;
					$countryResult = $this->conn->query($query);
					if($country = $countryResult->fetch_assoc()){
						$this->AddToResults('country', $country['countryName'], -1);
					}
					$countryResult->free();
				}
				$status = true;
			}
		}
		$stateResult->free();
		return $status;
	}

	//*****************************************************************************
	private function RankCountryLines()
		{//Rank the lines for likelihood of containing country or state

		//If these words are on the line, then probably not country or state.  In fact, they can contain the wrong state word, such as "University of Arizona", etc.
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|vascular|university|specimen|botanical\b))i";

		//If the line has been assigned to one of these conflict fields, then probably not country or state
		$BadFields = array("recordedBy","family","identifiedBy","associatedCollectors","sciname","infraspecificEpithet");

		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$RankArray[$L] = 0;
			$Score = 0;
			if(preg_match($BadWords,$this->LabelLines[$L]) > 0)
				$Score -= 10; //Contains bad word
			foreach($BadFields as $F)
				{
				if($this->LineStart[$L] == $F)
					{//Starts with conflict field start word.
					$Score -= 10;
					break;
					}
				if($this->Assigned[$F] == $L)
					{//Line already assigned to one of the conflict fields
					$Score -= 10;
					break;
					}
				}
			//If the line is shorter than 5 characters, doubtful to be country or state
			if(strlen($this->LabelLines[$L]) < 5)
				$Score -= (5 - strlen($this->LabelLines[$L]));

			if($this->LineStart[$L] =='locality')
				$RankArray[$L] += 5;  //Country/state often found on locality line
			if($this->Assigned['locality'] == $L)
				$RankArray[$L] += 5;  //Country/state often found on locality line
			//if(count($this->LabelArray[$L])<2)
			//	$RankArray[$L] -= 5;  //Less than two words.  Less likely

			//Look for title case words and increase probability one point for each found.
			$RankArray[$L] += preg_match_all("(\b[A-Z][a-z]{3,20}\b)",$this->LabelLines[$L]);
			}
		asort($RankArray);
		$RankArray = array_reverse($RankArray, true);
		return $RankArray;
		}


//*********************************************************************************************************************

//************************************************************************************************************************************
//******************* Word Stat functions ********************************************************************************************
//************************************************************************************************************************************


	//******************************************************************
	private function GetWordStatFields()
		{ //Process each line for word stats.  Assign to fields as determined.

		//WordStats fields
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");

		//List of words that usually mean the whole line does not belong to a wordstats field
		$BadWords = "(\b(copyright|herbarium|garden|database|institute|instituto|university|plants of|aceae of|flora de|univ|et al)\b)i";

		for($L=0;$L<count($this->LabelLines);$L++)
			{
			if($this->SpecialWordStatCases($L))
				continue;
			$Skip=false;

			//If line $L contains a wordstats field start word, then just assign the whole line to that field
			foreach($Fields as $F)
				{
				if($this->LineStart[$L] == $F)
					{
					//echo "Starts with $F, {$this->LabelLines[$L]}<br>";
					$this->RemoveStartWords($L,$F);
					$this->AddToResults($F,$this->LabelLines[$L],$L);
					$Skip = true;
					break;
					}
				}
			foreach(array("recordedBy","family","identifiedBy","sciname","infraspecificEpithet") as $F)
				{//Don't bother scoring if this line has start words or has already been assigned.
				if($this->LineStart[$L] ==$F)
					{
					$Skip=true;
					break;
					}
				}
			if($this->Assigned['ignore'] == $L)
				{
				$Skip = true;
				//echo "Skipping for ignore<br>";
				}
			if(preg_match("([a-z])",$this->LabelLines[$L]) === 0)
				{//Valid lines usually not all upper case.  That usually means an institute, herbarium or something similar
				$Skip=true;
				}
			if($this->StatScore[$L]['Score'] < 20)
				{
				$Skip = true;  //Already measured this line and it falls short.
				}
			if(preg_match($BadWords,$this->LabelLines[$L]) > 0)
				$Skip=true;
			//if($this->PlantsOfLine == $L)
			//	$Skip=true;
			if($Skip)
				{//One of the above reasons found to not bother scoring this line.
				continue;
				}
			$this->SplitWordStatLine($L);
			continue;
			}
		return;
		}

	//************************************************************************************************
	private function SpecialWordStatCases($L)
		{ //A few (two only right now) cases where the wordstats results can be easily determined.
		$PregArray[] = array('Preg'=>"(\A[.0-9-]+ m\.?( tall)?[\r\n])",'Field'=>'verbatimAttributes');// 4-6 m. tall
		foreach($PregArray as $P)
			{
			if(preg_match($P['Preg'],$this->LabelLines[$L])>0)
				{
				//echo "Found {$P['Field']} in {$this->LabelLines[$L]}<br>";
				$this->AddToResults($P['Field'],$this->LabelLines[$L],$L);
				return true;
				}
			}
		if(($this->Assigned['stateProvince'] == $L) && strlen($this->LabelLines[$L]) < 20)
				{
				//echo "Found {$P['Field']} in {$this->LabelLines[$L]}<br>";
				$this->AddToResults('locality',$this->LabelLines[$L],$L);
				return true;
				}
		}


	//************************************************************************************************
	private function SplitWordStatLine($L)
		{//Complex, tangled function!  Scores a line for wordstats fields, and splits it if the field changes mid-line.  Reasonably successful.

		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$ScoreArray = array();
		$TempString = $this->LabelLines[$L];
		$TempString = str_replace("'","`",$TempString);
		$WordsArray = array();
		if(preg_match_all("(\b[A-Za-z`]{1,20}\b)", $TempString, $WordsArray, PREG_OFFSET_CAPTURE)){ //Break the string up into words
			$FieldScoreArray = array();
			$StatSums = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);
			for($w=0;$w<count($WordsArray[0])-1;$w++)
				{//Get the wordstats values for each word and word pair in a line
				$Loc = $WordsArray[0][$w][1];
				$Word1 = $WordsArray[0][$w][0];
				$Word2 = $WordsArray[0][$w+1][0];
				$ScoreArray = $this->ScoreTwoWords($Word1,$Word2);//Get word stats score for these two words
				if(max($ScoreArray) == 0)
					{//Could be a locality.  See if it looks like a capitalized word.
					//This could be putting too many things into locality and may be better removed.
					if($w>0 && $this->MaxKey($FieldScoreArray[$w-1]) == 'locality' && preg_match("([A-Z][a-z]{2,20})", $Word1) > 0)
						$ScoreArray['locality'] = 100;
					}
				$FieldScoreArray[$w] = $ScoreArray;
				foreach($Fields as $F)
					{
					$StatSums[$F] += $ScoreArray[$F];
					}
				}

			//Get the value for the single last word (which doesn't have a following paired word, of course.)
			$w = count($WordsArray[0])-1;
			$ScoreArray = $this->ScoreTwoWords($WordsArray[0][$w][0],"");

			foreach($Fields as $F)
				{ //Put all the scores into FieldScoreArray
				$FieldScoreArray[$w][$F] = $ScoreArray[$F];
				$StatSums[$F] += $ScoreArray[$F];
				}
			/*
			//Debug routines used often enough to leave here.  For now.
			foreach($WordsArray[0] as $w=>$Word)
				{
				echo "$Word[0], ";
				foreach($Fields as $F)
					echo $FieldScoreArray[$w][$F].",";
				echo"<br>";
				}
			echo"<br>";
			*/
			if(count($FieldScoreArray) == 0)
					return;

			//Check for first/last words same field and larger than any other field for the rest of the words.
			//If so, then assume whole line is the same field.
			$Size = count($WordsArray[0])-2;
			if($Size > 0)
				{
				$Field1 = $this->MaxKey($FieldScoreArray[0]);
				$Field2 = $this->MaxKey($FieldScoreArray[$Size]);
				if(max($FieldScoreArray[0]) != 0 && $Field1 == $Field2)
					{//First and last the same field
					//echo "First and last ($Size) both $Field1<br>";
					$this->AddToResults($Field1,$this->LabelLines[$L],$L);
					return;
					}
				}

			//Smooth the array, removing isolated high and low points
			foreach($Fields as $F)
				{
				for($w=1;$w < count($FieldScoreArray)-1;$w++)
					{
					if($FieldScoreArray[$w][$F] < $FieldScoreArray[$w-1][$F] && $FieldScoreArray[$w][$F] < $FieldScoreArray[$w+1][$F])
						$FieldScoreArray[$w][$F] = ($FieldScoreArray[$w][$F]+$FieldScoreArray[$w-1][$F]+$FieldScoreArray[$w+1][$F])/3;
					if($FieldScoreArray[$w][$F] > $FieldScoreArray[$w-1][$F] && $FieldScoreArray[$w][$F] > $FieldScoreArray[$w+1][$F])
						$FieldScoreArray[$w][$F] = ($FieldScoreArray[$w][$F]+$FieldScoreArray[$w-1][$F]+$FieldScoreArray[$w+1][$F])/3;
					}
				}

			//Find the maximum score for any field.
			if(count($WordsArray[0]) > 0)
				$Max = max($StatSums)/(count($WordsArray[0]));
			else
				return;  //Array is empty.

			if($Max < 20)
				return; //Maximum wordstats score too low.

			//Now try to split into Fields.
			$ResultFields = array();
			$ChangeFields = array();

			//Some prepositions that influence where to split a line.
			$Prep = array("in","on","of","by","inside","along","ca","de","en");

			//Find any zero value words and set to the following word...?
			do
				{
				$Flag = false;
				$w=0;
				while($w < count($FieldScoreArray)-1)
					{
					if(max($FieldScoreArray[$w]) == 0)
						if(max($FieldScoreArray[$w+1]) > 0)
							{
							//echo "Setting $w to ".($w+1)."<br>";
							$FieldScoreArray[$w] = $FieldScoreArray[$w+1];
							$Flag = true;
							}
					$w++;
					}
				}while($Flag);

			//Walk through the array of word scores and make adjustments.
			for($w=0;$w<count($FieldScoreArray);$w++)
				{
				$Word1 = $WordsArray[0][$w][0];//Word "w" in the line
				$ResultFields[$w] = $this->MaxKey($FieldScoreArray[$w]);
				//$Max = max($FieldScoreArray[$w]);
				//$Field = array_search($Max,$FieldScoreArray[$w]);
				//$ResultFields[$w] = $Field;

				//echo "Max $Max in $Field<br>";

				//ChangeFields is an array of locations in the line where the field changes
				if($w==0)
					$ChangeFields[0] = 0;

				//If the previous word was locality and this word is title-case, then probably also locality, unless some cases
				if($w > 0 && $ResultFields[$w-1] == 'locality' && $Word1 == mb_convert_case($Word1,MB_CASE_TITLE))
					{
					//echo "Setting {$WordsArray[0][$w][0]} to locality<br>";
					$Pos = $WordsArray[0][$w-1][1] + strlen($WordsArray[0][$w-1][0])+1;
					if(strpos(".,;",$TempString[$Pos]=== false)) //Unless previous word was followed by period, comma, semicolon...
						$ResultFields[$w] = 'locality'; //last field was locality, and this word is capitalized.  Probably part of previous.
					}

				//If this word is a preposition, set it to the same field as the previous word
				if($w>0 && array_search($WordsArray[0][$w][0],$Prep))
					{
					//echo "Change $Word1 to {$ResultFields[$w-1]}<br>";
					$ResultFields[$w] = $ResultFields[$w-1];
					}

				//Find points in the line where the field changes.
				if($w>0 && $ResultFields[$w] != $ResultFields[$w-1])
					{ //Add this as a point in the line where the field changes.
					//echo "Change at $w<br>";
					$ChangeFields[] = $w;
					}
				}

			//Now adjust fields
			//Look for and correct anomalies, such as single words in a field
			//Loop through the following until nothing changes, indicated by $Adjust['Flag']
			//It's a complicated and somewhat tangled routine.
			do
				{ //First check for widows or orphans
				$Adjust = array('Flag'=>false);
				if(count($ChangeFields) > 1) //Otherwise don't bother.
					{
					if($ChangeFields[1] < 3) //Changing on second or third word is suspicious
						{
						if(count($ChangeFields) == 2)
							{
							if(count($WordsArray[0]) < 5 || count($WordsArray[0]) - $ChangeFields[1] > 3)
								{
								if($FieldScoreArray[$ChangeFields[1]][$ResultFields[1]] > ($FieldScoreArray[$ChangeFields[1]-1][$ResultFields[0]])/2)
									{
									//echo "Adjust 1<br>";
									$Adjust = array('RemovePoint' => 1, 'NewField'=> $ResultFields[1],'FieldStart' =>0,'FieldEnd'=>0,'Flag'=>true);
									}
								}
							}
						else if($ChangeFields[2] - $ChangeFields[1] > 3)
							{
							$Adjust = array('RemovePoint' => 1, 'NewField'=> $ResultFields[1],'FieldStart' =>0,'FieldEnd'=>0,'Flag'=>true);
							//echo "Adjust 2<br>";
							}
						}

					if(!$Adjust['Flag'])
						{
						$LastChange = count($ChangeFields)-1;
						$LastWord = count($WordsArray[0])-1;
						if($LastWord - $ChangeFields[$LastChange] < 4 && $FieldScoreArray[$ChangeFields[$LastChange]][$ResultFields[$ChangeFields[$LastChange]]] < 500)
								{
								$Adjust = array('RemovePoint' => $LastChange, 'NewField'=> $ResultFields[$ChangeFields[$LastChange-1]],'FieldStart' =>$ChangeFields[$LastChange],'FieldEnd'=>$LastWord,'Flag'=>true);
								}
						}

					if(!$Adjust['Flag'])
						for($c=0;$c<count($ChangeFields)-1;$c++)
							{
							$w1 = $ChangeFields[$c];
							$w2 = $ChangeFields[$c+1];
							if($w2 - $w1 < 4)
								{
								//echo "Found $w1, $w2 {$WordsArray[0][$w1][0]} in:  {$this->LabelLines[$L]}<br>";
								if($c>0 && $ResultFields[$ChangeFields[$c-1]] == $ResultFields[$w2])
									{//A few on one field in the middle of another field.  Use the surrounding field.
									//echo "Adjust 4<br>";
									$Adjust = array('RemovePoint' => $c, 'RemovePoint2'=>$c+1, 'NewField'=> $ResultFields[$w2],'FieldStart' =>$w1,'FieldEnd'=>$w2,'Flag'=>true);
									}
								else
									{ //Not a simple change/change back.  Find which field it shoud belong too.
									//echo "---{$WordsArray[0][$w1][0]} is {$ResultFields[$w1]}, {$WordsArray[0][$w2][0]} is {$ResultFields[$w2]}<br>";
									if($w2 < count($FieldScoreArray) && $FieldScoreArray[$w2][$ResultFields[$w1]] > 50)
										{//Reasonable score on previous value.  Set back to that
										//echo "Adjust 6<br>";
										if($w2 < count($ResultFields)-1 && $ResultFields[$w2+1] != $ResultFields[$w1])
											{
											$ChangeFields[]= $w2+1;
											asort($ChangeFields);
											}
										//echo "Adjust 5<br>";

										$Adjust = array('RemovePoint' => $c+1, 'NewField'=> $ResultFields[$w1],'FieldStart' =>$w2,'FieldEnd'=>$w2,'Flag'=>true);
										break;
										}
									else if($w2 < count($WordsArray[0])-1)
										{
										;//$w3 =

										}
									}
								}
							}
					if(!$Adjust['Flag'])
						{
						$LastChange = count($ChangeFields)-1;
						$LastWord = count($WordsArray[0])-2;
						if($ResultFields[0] == $ResultFields[$LastWord] && $LastChange > 0)
							{
							for($w=0;$w<$LastWord;$w++)
								{
								$ResultFields[$w] = $ResultFields[0];
								}
							$ChangeFields = array(0=>0);
							}
						}
					if($Adjust['Flag'])
						{//Adjust or remove the point in the string where the field changes, as indicated in the $Adjust array.
						for($w=$Adjust['FieldStart'];$w<=$Adjust['FieldEnd'];$w++)
							$ResultFields[$w] = $Adjust['NewField'];
						unset($ChangeFields[$Adjust['RemovePoint']]);
						if(isset($Adjust['RemovePoint2']))
							unset($ChangeFields[$Adjust['RemovePoint2']]);
						$ChangeFields = array_values($ChangeFields);
						}
					}
				}while($Adjust['Flag']);


			if(count($ChangeFields) == 1)
				{//Only the first word is a change point, which means the whole line is a single field
				$Field = $ResultFields[0];
				$this->AddToResults($Field,trim($this->LabelLines[$L],": ,;"),$L);
				}
			else
				{//The line should be split into two fields
				for($c=0;$c<count($ChangeFields)-1;$c++)
					{
					$w1 = $ChangeFields[$c];
					$w2 = $ChangeFields[$c+1];
					$p1 = $WordsArray[0][$w1][1];
					if($w2 < count($WordsArray[0])-1)
						$p2 = $WordsArray[0][$w2][1];
					else
						{
						$p2 = strlen($this->LabelLines[$L]);
						}
					$Field = $ResultFields[$ChangeFields[$c]];
					$TempString = trim(substr($this->LabelLines[$L],$p1,$p2 - $p1),": ,;");
					$this->AddToResults($Field,$TempString,$L);
					}
				if($p2 < strlen($this->LabelLines[$L]))
					{
					$Field = $ResultFields[$w2];
					$TempString = trim(substr($this->LabelLines[$L],$p2),": ,;");
					$this->AddToResults($Field,$TempString,$L);
					}


				}
			/*
			$this->printr($Fields);
			echo "{$this->LabelLines[$L]}<br>";
			for($w=0;$w < count($FieldScoreArray)-1;$w++)
				{
				echo $WordsArray[0][$w][0]." ".$WordsArray[0][$w+1][0];
				echo " {$ResultFields[$w]}, ";
				foreach($Fields as $F)
					echo ", ".floor($FieldScoreArray[$w][$F]);
				echo"<br>";

				}
			*/
		}
		return;
	}

	//************************************************************************************************
	private function ScoreTwoWords($Word1,$Word2)
		{//Important part of wordstats scoring.  Takes two words and scores them for all wordstats fields, both as the first word alone, and as a pair of words.
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$ScoreArray = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0);

		if(preg_match("([nsewNSEW] of)",$Word1." ".$Word2) > 0)
			{ //Almost certainly locality
			$ScoreArray['locality'] = 200;
			return $ScoreArray;
			}

		//$ExcludeWords = array('verbatimAttributes'=>array("herbarium","institute","university","botanical"),'habitat'=>array("herbarium","university"));
		$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL LIMIT 3";
		//echo "$query<br>";
		$result = $this->conn->query($query);
		$num1 = $result->num_rows;
		$MaxScore1 = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0); //Single word scores
		$MaxScore2 = array("occurrenceRemarks"=>0,"habitat"=>0,"locality"=>0,"verbatimAttributes"=>0,"substrate"=>0); //Word pair scores
		if($num1 > 0)
			{
			while($Values = $result->fetch_assoc())
				{
				$Factor = 1;
				if($Values['totalcount'] < 10) //Reduce impact if only seen few times
					{
					$Factor = ($Values['totalcount'])/10;
					$Factor = ($Factor*$Factor)/100;
					}
				foreach($Fields as $F)
					{
					$OneScore = $Factor * $Values[$F.'Freq'];
					//echo "Adding $OneScore to $F for $Word1<br>";
					if($OneScore < 1)
						$OneScore = 0;
					if($OneScore > $MaxScore1[$F])
						$MaxScore1[$F] = $OneScore;
					}
				}
			}
		if($Word2 != "")
			{//Look for two-word combinations.  Score with more weight than single words -- 10X as I write this comment.
			$query = "SELECT * from salixwordstats where firstword like '$Word1' AND secondword LIKE '$Word2' LIMIT 3";
			//echo "$query<br>";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				{
				while($Values = $result->fetch_assoc())
					{
					$Factor = 1;
					if($Values['totalcount'] < 10) //Reduce impact if only seen few times
						{
						$Factor = ($Values['totalcount']);
						$Factor = ($Factor*$Factor)/100;
						}
					foreach($Fields as $F)
						{
						$OneScore = 10*$Factor * $Values[$F.'Freq'];
						if($OneScore < 1)
							$OneScore = 0;
						//echo "Adding $OneScore to $F for $Word1, $Word2<br>";
						if($OneScore > $MaxScore2[$F])
							$MaxScore2[$F] = $OneScore;
						}
					}
				}
			}
		foreach($Fields as $F)
			$ScoreArray[$F] = $MaxScore2[$F]+$MaxScore1[$F];//+$MaxScore1A[$F];
		//$this->printr($ScoreArray,"Raw $Word1 $Word2");
		return $ScoreArray;
		}


	//************************************************************************************************
	private function ScoreOneLine($L, &$Field, &$Score)
		{ //Score a line for wordstats
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$ExcludeWords = array('verbatimAttributes'=>array("herbarium","institute","university","botanical"),'habitat'=>array("herbarium","university"));
		$StartField = "";
		foreach($Fields as $F)
			{
			if($this->LineStart[$L] == $F)
				{
				$Field = $F;
				$Score = 1000;
				return;
				}
			}
		$this->ScoreString($this->LabelLines[$L],$Field, $Score);
		if(isset($ExcludeWords[$Field]))
			{
			foreach($ExcludeWords[$Field] as $Ex)
				{
				//echo "Checking for $Ex in {$this->LabelLines[$L]}<br>";
				if(stristr($this->LabelLines[$L],$Ex) !== false)
					{
					//echo "Found $Ex<br>";
					//echo "Score = $Score<br>";
					$Score -= 1000;
					}
				}
			}
		return;
		}

	//************************************************************************************************
	private function ScoreString($TempString,&$Field, &$Score)
		{//Called by ScoreOneLine and also by associated species routine
		//Return the most probable field and the score for that field in the passed-by-reference variables
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$BadWords = "(\b(copyright|herbarium|garden|database)\b)i";
		$ScoreArray  = array_fill_keys($Fields,0);
		if(preg_match($BadWords,$TempString) > 0)
			{ //The words in the BadWords array are not likely to be in any of the wordstats fields.
			return;
			}
		$match=array();
		$Found = preg_match_all("(\b\w{2,20}\b)",$TempString,$match);
		if($Found == 0)
			return;
		else
			$WordsArray = $match[0];
		for($W=0;$W<count($WordsArray);$W++)
			{//Look for each word, and each two word combination in the salix word stats table
			//First look for single word
			$query = "Select * from salixwordstats where firstword like '{$WordsArray[$W]}' AND secondword IS NULL LIMIT 3";
			//echo "$query<br>";
			$result = $this->conn->query($query);
			$num1 = $result->num_rows;
			if($num1 > 0)
				{
				while($Values = $result->fetch_assoc())
					{
					$Factor = 1;
					if($Values['totalcount'] < 10) //Reduce impact if only seen few times
						$Factor = ($Values['totalcount'])/10;
					foreach($Fields as $F)
						$ScoreArray[$F] += $Factor * $Values[$F.'Freq'];
					}
				}
			if(strtolower($WordsArray[$W]) == "on")
				{
				if(array_search($this->Family,$this->LichenFamilies)!==false)
					{
					$ScoreArray['substrate'] += 1000;
					}
				}
			if($W < count($WordsArray)-1)
				{//Look for two-word combinations.  Score with more weight than single words -- 5X as I write this comment.
				$query = "SELECT * from salixwordstats where firstword like '{$WordsArray[$W]}' AND secondword LIKE '{$WordsArray[$W+1]}' LIMIT 3";
				//echo "$query<br>";
				$result = $this->conn->query($query);
				if($result->num_rows > 0)
					{
					while($Values = $result->fetch_assoc())
						{
						$Factor = 1;
						if($Values['totalcount'] < 10) //Reduce impact if only seen few times
							$Factor = ($Values['totalcount'])/10;
						foreach($Fields as $F)
							$ScoreArray[$F] += $Factor*5*$Values[$F.'Freq'];
							}
					}
				}
			}
		if(preg_match("(\b[nsewNSEW] of [A-Z][a-z]{2,20})",$TempString) > 0)
			$ScoreArray['locality'] += 100;
		//$this->printr($ScoreArray,"ScoreArray");
		asort($ScoreArray);
		end($ScoreArray); //Select the last (highest) element in the scores array
		$Field = key($ScoreArray); //Maximum field
		$Score = floor($ScoreArray[$Field]/count($WordsArray));
		//echo "Score = $Score<br>";
		}


	//**********************************************
	private function SingleWordStats($Words, $Field="All")
		{//Used mainly for non-word stats fields to adjust their probability.
		//e.g. if I think it might be a collector's name but the line scores high for locality, then probably a place name instead

		$Preg = "(\b[A-Za-z]{2,20}\b)";
		$match = array();
		$Found = preg_match_all($Preg,$Words,$match);
		if($Found === 0)
			return 0;
		$Word1 = $match[0][0];
		$Score=0;

		if(count($match[0]) > 1)
			{
			$Word2 = $match[0][1];
			$query = "SELECT * from salixwordstats where firstword like '$Word1' AND secondword LIKE '$Word2' ORDER BY totalcount DESC LIMIT 3";
			$result = $this->conn->query($query);
			if($result->num_rows > 0)
				$Score += 5*$this->ScoreWordStatResult($result,$Field);
			}
		//$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL LIMIT 3";
		$query = "Select * from salixwordstats where firstword like '$Word1' AND secondword IS NULL ORDER BY totalcount DESC LIMIT 3";
		$result = $this->conn->query($query);
		if($result->num_rows > 0)
			$Score += $this->ScoreWordStatResult($result,$Field);
		return $Score;
		}

	private function ScoreWordStatResult($result,$Field)
		{//Called from SingleWordStats above
		$Fields = array("occurrenceRemarks","habitat","locality","verbatimAttributes","substrate");
		$Score = 0;
		while($Values = $result->fetch_assoc())
			{
			$Factor = .1;
			if($Values['totalcount'] < 10) //Reduce impact if only in database a few times
				$Factor = ($Values['totalcount'])/100;
			if($Field=="All")
				foreach($Fields as $F)
					$Score += $Factor * $Values[$F.'Freq'];
			else
					$Score += $Factor * $Values[$Field.'Freq'];
			}
		return $Score;
		}


//************************************************************************************************************************************
//******************* Misc Functions *************************************************************************************************
//************************************************************************************************************************************

	//******************************************************************
	private function AddToResults($Field, $String, $L)
		{ //Set $Field results to $String.  Mark Line $L as used for this field
		$Preps = "(\b(of|to|by|at|in|over|under|on|from|along|beside|with|con)[ \r\n]{0,3}\Z)";

		if($Field == "recordedBy" && $this->Results['recordedBy'] != "")
			{//Separate associated collectors from recordedBy
			$RBStart = strpos($this->VirginLines[$L],$this->Results['recordedBy']);
			$ACStart = strpos($this->VirginLines[$L],$String);
			if($RBStart !== false && $ACStart !== false && $ACStart < $RBStart && strpos($this->Results['associatedCollectors'],";") === false)
				{//First name should be collector, not associated collectors.  Swap them.
				$TempName = $this->Results['recordedBy'];
				$this->Results['recordedBy'] = $String;
				$String = $TempName;
				}
			$this->AddToResults('associatedCollectors',$String,$L);  //Recursive call
			return;
			}
		if($Field == "associatedCollectors")
			{
			if($this->Assigned['sciname'] == $L || $this->Assigned['taxonRank'] == $L)
				return;//Probably picking up the author instead of an associated collector
			else if($this->Assigned['recordedBy'] > $L)
				return;//Associated collectors should always be after the main collector
			}
		if($L>=0)
			$this->Assigned[$Field] = $L;
		$String = trim($String," :;,");

		if(array_search($Field, array("country","stateProvince","county","minimimumElevationInMeters")) !== false)
			{ //Make sure not to add multiple results to these fields.  Assume the later addition is more likely to be correct, so replace.
			//(That may turn out to be a bad assumption)
			$this->Results[$Field] = $String;
			return;
			}

		if($this->Results[$Field] == "")
			{ //The result field is empty.  Just add the string.
			$this->Results[$Field] = $String;
			return;
			}
		if(array_search($Field, array("associatedCollectors","identifiedBy","associatedTaxa")) !== false)
			$this->Results[$Field] .= "; ".$String;  //Append with a semi-colon in these cases
		else if($this->Results[$Field] != "")
			$this->Results[$Field] .= ", ".$String; //Append with a comma in all other
		}


	//**********************************************
	private function InitStartWords()
		{//Fill the StartWords array, an array of start words for each field
		$this->PregStart['family'] = "(^(family)\b)i";
		$this->PregStart['recordedBy'] = "(^(Coll(.|ected)? by|Collectors|Collector|Colector|Coll|Col|Leg|By)\b)i";
		$this->PregStart['eventDate'] = "(^(EventDate|Date|fecha)\b)i";
		$this->PregStart['recordNumber'] = "(^(Number|no)\b)i";
		$this->PregStart['identifiedBy'] = "(^(Det(.|:|ermined|ermidado)? (by|por)|Determined|det(.)? dupl|det|Identified by|Identified)\b)i";
		$this->PregStart['associatedCollectors'] = "(^(with|and|&|con)\b)i";
		$this->PregStart['habitat'] = "(^(Habitat|site)\b)i";
		$this->PregStart['locality'] = "(^(Locality|Location|Localidad|loc|collected off|collected near)\b)i";
		$this->PregStart['substrate'] = "(^(Substrate|growing on)\b)i";
		$this->PregStart['country'] = "(^(Country)\b)i";
		$this->PregStart['stateProvince'] = "(^(State|Province|Provincia de|Provincia|Prov)\b)i";
		$this->PregStart['county'] = "(^(County|Parish)\b)i";
		$this->PregStart['minimumElevationInMeters'] = "(^(Elevation|Elevacion|Elev|Altitude|alt)\b)i";
		$this->PregStart['associatedTaxa'] = "(^(growing with|Associated taxa|Associated with|Assoc(.|iated)? plants|Assoc(.|iated)? spp|Assoc(.|iated)? species|Especies representativas|especies|Associated|Other spp)\b)i";
		$this->PregStart['infraspecificEpithet'] = "(^(ssp|variety|subsp)\b)i";
		$this->PregStart['occurrenceRemarks'] = "(^(Notes)\b)i";
		//$this->PregStart['verbatimAttributes'] = "(^(habit)\b)i";
		$this->PregStart['verbatimCoordinates'] = "(^(utm|Latitude|Latitud|Longitude|Longitud|Coordenadas|Lat|Long)\b)i";
		$this->PregStart['ignore'] = "(^(synonym)\b)i";
		}

	private function InitStartLines()
		{
		for($L=0;$L<count($this->LabelLines);$L++)
			{
			$this->LineStart[$L] = "";
			foreach($this->PregStart as $Field=>$Val)
				{
				//echo "Checking $Field: {$this->LabelLines[$L]}<br>";
				if($this->CheckStartWords($L,$Field))
					{
					//echo "Assign $Field to {$this->LabelLines[$L]}<br>";
					$this->LineStart[$L] = $Field;
					break;
					}
				}
			}
		}


	//**********************************************
	private function CheckStartWords($L, $Field)
		{//Returns true if Line $L starts with any start word from $Field
		if($this->PregStart[$Field] == "" )
			return false;
		if($L >= count($this->LabelArray))
			return false;
		//if(substr_count ($this->LabelLines[$L]," ") < 1)
		//	return false;
		$Found = preg_match($this->PregStart[$Field], $this->LabelLines[$L]);
		if($Found === 1)
			{
			return true;
			}
		else
			return false;
		}

	//**********************************************
	private function RemoveStartWords($L, $Field)
		{//Clean up a field by removing any start words.  Example:
		//"Collector Les Landrum" would have "Collector" removed from the line
		$this->LabelLines[$L] = $this->RemoveStartWordsFromString($this->LabelLines[$L], $Field);
		return;
		}

	//**********************************************
	private function RemoveStartWordsFromString($TempString,$Field)
		{//Same as RemoveStartWords, but takes any string instead of just a label line.
		$PF = $this->PregStart[$Field];
		if($Field == 'associatedTaxa')
			$PF = str_replace("associated with","associated with|with", $PF);
		if($PF == "")
			return $TempString;
		$match=array();
		if($this->PregStart[$Field] == "" )
			return $TempString;
		if($TempString == "")
			return $TempString;
		$Found = preg_match($PF, $TempString,$match);
		if($Found === 1)
			{
			$Preg = "(\A{$match[0]}\b)";
			$TempString = preg_replace($Preg," ",$TempString);
			$TempString = trim($TempString,": ,;-.\t");
			}
		return $TempString;

		}

	//**********************************************
	private function getRawOcr($prlid){
		$retStr = '';
		if(is_numeric($prlid)){
			//Get raw OCR string
			$sql = 'SELECT rawstr '.
				'FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retStr = $r->rawstr;
			}
			$rs->free();
		}
		return $retStr;
	}

	//**********************************************
	private function GetLichenNames()
		{//An array of lichen families.  Used to determine when to include substrate.
		$this->LichenFamilies = array("Acarosporaceae","Adelococcaceae","Agyriaceae","Anamylopsoraceae","Anziaceae","Arctomiaceae","Arthoniaceae","Arthopyreniaceae","Arthrorhaphidaceae","Aspidotheliaceae","Atheliaceae","Baeomycetaceae","Biatorellaceae","Bionectriaceae","Brigantiaeaceae","Caliciaceae","Candelariaceae","Capnodiaceae","Catillariaceae","Cetradoniaceae","Chionosphaeraceae","Chrysothricaceae","Cladoniaceae","Clavulinaceae","Coccocarpiaceae","Coccotremataceae","Coenogoniaceae","Collemataceae","Coniocybaceae","Coniophoraceae","Crocyniaceae","Dacampiaceae","Dactylosporaceae","Didymosphaeriaceae","Epigloeaceae","Fuscideaceae","Gloeoheppiaceae","Gomphillaceae","Graphidaceae","Gyalectaceae","Gypsoplacaceae","Haematommataceae","Helotiaceae","Heppiaceae","Herpotrichiellaceae","Hyaloscyphaceae","Hygrophoraceae","Hymeneliaceae","Hyponectriaceae","Icmadophilaceae","Lahmiaceae","Lecanoraceae","Lecideaceae","Lepidostromataceae","Letrouitiaceae","Lichenotheliaceae","Lichinaceae","Lobariaceae","Loxosporaceae","Mastodiaceae","Megalariaceae","Megalosporaceae","Megasporaceae","Melanommataceae","Melaspileaceae","Microascaceae","Microcaliciaceae","Microthyriaceae","Monoblastiaceae","Mycoblastaceae","Mycocaliciaceae","Mycosphaerellaceae","Mytilinidiaceae","Myxotrichaceae","Naetrocymbaceae","Nectriaceae","Nephromataceae","Niessliaceae","Nitschkiaceae","Obryzaceae","Odontotremataceae","Ophioparmaceae","Pannariaceae","Parmeliaceae","Parmulariaceae","Peltigeraceae","Peltulaceae","Pertusariaceae","Phlyctidaceae","Phyllachoraceae","Physciaceae","Pilocarpaceae","Placynthiaceae","Platygloeaceae","Pleomassariaceae","Pleosporaceae","Porinaceae","Porpidiaceae","Protothelenellaceae","Pseudoperisporiaceae","Psoraceae","Pucciniaceae","Pyrenotrichaceae","Pyrenulaceae","Ramalinaceae","Requienellaceae","Rhizocarpaceae","Roccellaceae","Schaereriaceae","Solorinellaceae","Sphaerophoraceae","Sphinctrinaceae","Stereocaulaceae","Stictidaceae","Strigulaceae","Syzygosporaceae","Teloschistaceae","Thelenellaceae","Thelocarpaceae","Thelotremataceae","Tremellaceae","Tricholomataceae","Trypetheliaceae","Umbilicariaceae","Verrucariaceae","Vezdaeaceae","Xanthopyreniaceae");
		}

	//**********************************************
	private function GetFamilySyn()
		{ //Fill an array with family synonyms.  Currently only one family.  Add more as encountered.
		$this->FamilySyn = array("Agavaceae"=>"Asparagaceae");
		}


	//**********************************************
	private function getWordFreq(){
		$sql = 'SELECT firstword, secondword, locality, localityfreq, habitat, habitatFreq, substrate, substrateFreq, '.
			'verbatimAttributes, verbatimAttributesFreq, occurrenceRemarks, occurrenceRemarksFreq, totalcount, datelastmodified '.
			'FROM salixwordstats '.
			'WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			//Not sure if this is the best way organize the word stats, but it's an idea
			$this->wordFreqArr[$r->firstword][$r->secondword]['loc'] = $r->localityfreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['hab'] = $r->habitatFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['sub'] = $r->substrateFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['att'] = $r->verbatimAttributesFreq;
			$this->wordFreqArr[$r->firstword][$r->secondword]['rem'] = $r->occurrenceRemarksFreq;
		}
		$rs->free();
	}

	//**********************************************
	private function rebuildWordStats(){
		$salixHandler = new SalixUtilities();
		$salixHandler->setVerbose(0);
		$salixHandler->buildWordStats($this->collId, 1);
	}

	//**********************************************
	private function MaxKey($A)
		{ //Return the key of the maximum element in an array.  PHP should have this as a function...!
		//Note that if there are two elements with the same maximum value, the first value will be returned.
		$MaxV = -1000;
		$Field = "";
		foreach($A as $Key=>$Value)
			{
			if($Value > $MaxV)
				{
				$MaxV = $Value;
				$Field = $Key;
				}
			}
		return $Field;
		}

//************************************************************************************************************************************
//******************* Debug Functions.  Can be deleted for production*****************************************************************
//************************************************************************************************************************************


	//**********************************************
	private function printr($A, $Note="")
		{//Debug only.  Enhances the print_r routine by adding an optional note before and a newline after.
		// [^/]\$this->printr regular expression search to find uncommented instances
		if(!is_array($A))
			echo "Not array";
		else
			{
			if($Note != "")
				echo $Note.": ";
			print_r($A);
			}
		echo "<br>";
		}

	private function PrintRank($RankArray, $Name = "")
		{//Debug only.  Print RankArray as a table showing line text
		echo "<br>";
		if($Name != "")
			echo "$Name<br>";
		echo "<table border=1><tr><td>Line</td><td>Score</td><td>Text</td></tr>";
		foreach($RankArray as $L=>$Value)
			echo "<tr><td>$L:</td><td>{$RankArray[$L]}</td><td>{$this->LabelLines[$L]}</td></tr>";
		echo"</table>";
		}

	private function PrintLines($Name = "")
		{//Debug only.  Print LabelLine Array as a table showing each line's text
		echo "<br>";
		if($Name != "")
			echo "$Name<br>";
		echo "<table border=1><tr><td>Line</td><td>Text</td></tr>";
		foreach($this->LabelLines as $L=>$Value)
			echo "<tr><td>$L:</td><td>{$this->LabelLines[$L]}</td></tr>";
		echo"</table>";
		}

}



?>