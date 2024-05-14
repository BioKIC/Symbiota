<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

/**
 * AgentManager.php
 *
 * Support for an authority file of collectors and other scientits
 *
 * This file contains a controler, the AgentManager class, along with
 * model classes Agent, AgentNames, agentnumberpattern... for the
 * set of related tables concerning agents, and some view classes.
 *
 */

/**
 * Controler class for actions related to agents.
 *
 */
class AgentManager{

	protected $conn;
	protected $searchTermsArr = Array();
	protected $useCookies = 1;
	protected $reset = 0;

 	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
		$this->useCookies = (array_key_exists("usecookies",$_REQUEST)&&$_REQUEST["usecookies"]=="false"?0:1);
 		if(array_key_exists("reset",$_REQUEST) && $_REQUEST["reset"]){
 			$this->reset();
 		}
 		if($this->useCookies && !$this->reset){
 			$this->readCollCookies();
 		}
 		//Read DB cookies no matter what
		if(array_key_exists("colldbs",$_COOKIE)){
			$this->searchTermsArr["db"] = $_COOKIE["colldbs"];
		}
		elseif(array_key_exists("collclid",$_COOKIE)){
			$this->searchTermsArr["clid"] = $_COOKIE["collclid"];
		}
 	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	protected function getConnection($conType = "readonly"){
		return MySQLiConnectionFactory::getCon($conType);
	}

	public function reset(){
		global $CLIENT_ROOT;
		setCookie("colltaxa","",time()-3600,($CLIENT_ROOT?$CLIENT_ROOT:'/'));
		setCookie("collsearch","",time()-3600,($CLIENT_ROOT?$CLIENT_ROOT:'/'));
		setCookie("collvars","",time()-3600,($CLIENT_ROOT?$CLIENT_ROOT:'/'));
 		$this->reset = 1;
		if(array_key_exists("db",$this->searchTermsArr) || array_key_exists("oic",$this->searchTermsArr)){
			//reset all other search terms except maintain the db terms
			$dbsTemp = "";
			if(array_key_exists("db",$this->searchTermsArr)) $dbsTemp = $this->searchTermsArr["db"];
			$clidTemp = "";
			if(array_key_exists("clid",$this->searchTermsArr)) $clidTemp = $this->searchTermsArr["clid"];
			unset($this->searchTermsArr);
			if($dbsTemp) $this->searchTermsArr["db"] = $dbsTemp;
			if($clidTemp) $this->searchTermsArr["clid"] = $clidTemp;
		}
	}

	private function readCollCookies(){
		if(array_key_exists("collsearch",$_COOKIE)){
			$collSearch = $_COOKIE["collsearch"];
			$searArr = explode("&",$collSearch);
			foreach($searArr as $value){
				$this->searchTermsArr[substr($value,0,strpos($value,":"))] = substr($value,strpos($value,":")+1);
			}
		}
		if(array_key_exists("collvars",$_COOKIE)){
			$collVarStr = $_COOKIE["collvars"];
			$varsArr = explode("&",$collVarStr);
			foreach($varsArr as $value){
				if(strpos($value,"reccnt") === 0){
					$this->recordCount = substr($value,strpos($value,":")+1);
				}
			}
		}
		return $this->searchTermsArr;
	}

	public function getSearchTerms(){
		return $this->searchTermsArr;
	}

	public function getSearchTerm($k){
		if(array_key_exists($k,$this->searchTermsArr)){
			return $this->searchTermsArr[$k];
		}
		else{
			return "";
		}
	}

	public function getSqlWhere(){
		$sqlWhere = "";

		if(array_key_exists("name",$this->searchTermsArr)){
			$nameArr = explode(";",$this->searchTermsArr["name"]);
			$tempArr = Array();
			foreach($nameArr as $value){
				$tempArr[] = "(n.name = '".trim($value)."')";
			}
			$sqlWhere .= "AND (".implode(" OR ",$tempArr).") ";
			$this->localSearchArr[] = implode(" OR ",$countryArr);
		}
		if(strlen($sqlWhere>0)){
			$sqlWhere = 'WHERE '.trim($sqlWhere);
		}
		return $sqlWhere;
	}

	public function getUseCookies(){
		return $this->useCookies;
	}

	public function getClName(){
		return $this->clName;
	}

    /**
     *  Is the currently logged in user authorized to edit agent records?
     *
     *  @return true if authorized, otherwise false.
     */
    public function isAgentEditor() {
        global $SYMB_UID, $IS_ADMIN, $USER_RIGHTS;
        $isAgentEditor = FALSE;
        if($SYMB_UID){
	        if($IS_ADMIN || array_key_exists("CollEditor",$USER_RIGHTS) || array_key_exists("CollAdmin",$USER_RIGHTS)) {
               $isAgentEditor = TRUE;
            }
        }
        return $isAgentEditor;
    }

	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

    public  function assembleNameBits($prefix,$firstname,$middlename,$lastname,$suffix,$name,$order='First Middle Last') {
       $result = "";
       if (strlen($name)>0) {
         $result = trim($name);
       } else {
         if ($order='First Middle Last') {
            $result = trim("$prefix $firstname $middlename $lastname $suffix");
         } elseif ($order = 'Last, First Middle') {
            $result = trim("$lastname $suffix, $prefix $firstname $middlename $suffix");
         } else {
            $result = trim("$lastname");
         }
         // reduce multiple spaces
         $result = preg_replace('/ +/',' ',$result);
       }
       return $result;
    }

    /**
     * Method to query for and list agent names matching a provided string. Suitable for use by a web service that
     * returns json.
     *
     * @param text, string to search for in the agent names, if first character
     * is a '^', does not add wildcards to the wildcard search string.
     *
     * @return string list of query results as agent names, formated for html display.
     */
    public function agentSearch($text,$regexsearch=false) {
        global $CLIENT_ROOT;

        $agents = array();
        // attempt to recognize a request to do a regex search
        $pattern = '/[[^$+]/';
        if (preg_match($pattern,$text,$m)==1) {
            $regexsearch = true;
        }
        // recognize if the search is to be limited to a particular atomic name field
        $targetfield = "n.name";
        if (preg_match('/^(familyname|firstname|middlename|namestring|yearofbirth|yearofdeath)=(.*)$/',$text,$matches)==1) {
            $text = $matches[2];
            $targetfield = "a.".$matches[1];
        }
        if ($regexsearch) {
            $sql = "select a.agentid, a.type, prefix, firstname, middlename, familyname, suffix, namestring, yearofbirth, yearofbirthmodifier, yearofdeath, yearofdeathmodifier, living, curated, n.name, '' as score from agents a left join agentnames n on a.agentid = n.agentid where $targetfield rlike ? order by a.type desc, a.familyname asc, a.namestring asc ";
        } else {
            $sql = "select a.agentid, a.type, prefix, firstname, middlename, familyname, suffix, namestring, yearofbirth, yearofbirthmodifier, yearofdeath, yearofdeathmodifier, living, curated, n.name, if(n.name=?,'Exact', truncate(match (n.name) against ( ? ) , 2)) as score from agents a left join agentnames n on a.agentid = n.agentid where match (n.name) against ( ? ) or $targetfield like ? order by a.type desc, a.familyname asc, a.namestring asc ";
        }
        if ($statement = $this->conn->prepare($sql)) {
            $wildtext = "%$text%";
            if ($regexsearch) {
                $statement->bind_param("s", $text);
            } else {
                $statement->bind_param("ssss", $text, $text, $text, $wildtext);
            }
            $statement->execute();
            $statement->bind_result($agentid, $type, $prefix, $firstname, $middlename, $familyname, $suffix, $namestring, $yearofbirth, $yearofbirthmodifier, $yearofdeath, $yearofdeathmodifier, $living, $curated, $matchednamevariant, $score);
            $statement->store_result();
            $rows = $statement->num_rows;

            $results = array();
            while ($statement->fetch()) {

                $agent = (object) array('prefix'=>$prefix, 'firstname'=>$firstname, 'familyname'=>$familyname, 'middlename'=>$middlename,
                    'suffix'=>$suffix, 'namestring'=>$namestring, 'yearofbirth'=>$yearofbirth, 'yearofdeath'=>$yearofdeath);

                $result = (object) array('agent'=>$agent, 'matchednamevariant'=>$matchednamevariant, 'score'=>$score);

                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Method to query for and list agent names matching a provided string.
     *
     * @param text, string to search for in the agent names, if first character
     * is a '^', does not add wildcards to the wildcard search string.
     *
     * @return string list of query results as agent names, formated for html display.
     */
    public function agentNameSearch($text,$regexsearch=false) {
	   global $CLIENT_ROOT;
       $result = "Searched for $text ";
       $agents = array();
       // attempt to recognize a request to do a regex search
       $pattern = '/[[^$+]/';
       if (preg_match($pattern,$text,$m)==1) {
          $regexsearch = true;
       }
       // recognize if the search is to be limited to a particular atomic name field
       $targetfield = "n.name";
       if (preg_match('/^(familyname|firstname|middlename|namestring|yearofbirth|yearofdeath)=(.*)$/',$text,$matches)==1) {
           $text = $matches[2];
           $targetfield = "a.".$matches[1];
       }
       if ($regexsearch) {
           $sql = "select a.agentid, a.type, prefix, firstname, middlename, familyname, suffix, namestring, yearofbirth, yearofbirthmodifier, yearofdeath, yearofdeathmodifier, living, curated, n.name, '' as score from agents a left join agentnames n on a.agentid = n.agentid where $targetfield rlike ? order by a.type desc, a.familyname asc, a.namestring asc ";
       } else {
           $sql = "select a.agentid, a.type, prefix, firstname, middlename, familyname, suffix, namestring, yearofbirth, yearofbirthmodifier, yearofdeath, yearofdeathmodifier, living, curated, n.name, if(n.name=?,'Exact', truncate(match (n.name) against ( ? ) , 2)) as score from agents a left join agentnames n on a.agentid = n.agentid where match (n.name) against ( ? ) or $targetfield like ? order by a.type desc, a.familyname asc, a.namestring asc ";
       }
       if ($statement = $this->conn->prepare($sql)) {
          $wildtext = "%$text%";
          if ($regexsearch) {
             $statement->bind_param("s",$text);
          } else {
             $statement->bind_param("ssss", $text, $text, $text, $wildtext);
          }
          $statement->execute();
          $statement->bind_result($agentid,$type,$prefix,$firstname,$middlename,$familyname,$suffix,$namestring, $yearofbirth,$yearofbirthmodifier,$yearofdeath, $yearofdeathmodifier,$living,$curated,$matchednamevariant,$score);
          $statement->store_result();
          $rows = $statement->num_rows;
          $idarray = array();
          while ($statement->fetch()) {
             if (!in_array($agentid,$idarray)) { $idarray[] = $agentid; }
             $name = $this->assembleNameBits($prefix,$firstname,$middlename,$familyname,$suffix,$namestring,'Last, First Middle');
             $dates = "";
             if ($living=='Y') {
                $dates = "($yearofbirth $yearofbirthmodifier-present)";
             } else {
                $dates = "($yearofbirth $yearofbirthmodifier-$yearofdeath $yearofdeathmodifier)";
                $dates = str_replace($dates,' ','');
             }
             $curationstate = "";
             if ($curated==1) {
                $curationstate = "*";
             }
             if (strlen($score)>0) { $displayscore = " at $score"; } else { $displayscore = ''; }
             $agent = "<a href='$CLIENT_ROOT/agents/agent.php?agentid=$agentid&findobjects=1'>$name</a> $dates $curationstate [$matchednamevariant$displayscore] ";
             if (array_key_exists($agentid,$agents)) {
                $agents[$agentid] .= "[$matchednamevariant$displayscore] ";
             } else {
                $agents[$agentid] = $agent;
             }
          }
          $result .= " found $rows names for ". count($idarray)." Agents.<br>";
          foreach($agents as $key => $value) {
             $result .= $value . '<BR>';
          }
          $statement->close();
       } else {
         $result .= $this->conn->error;
       }
       return $result;
    }

    /**
     * Method to query for agent names and list matching agents as json.
     * Provides [{id:'',label:'',value:''}] json appropriate for backing
     * the jqueryui autocomplete widget.
     *
     * @param text, string to search for in the agent names.
     * @param type type of name to limit the search to.
     * @param regexsearch optional boolean, true to use rlike on $text.
     *
     * @return string list of query results as json of id, label, value triplets.
     */
    public function agentNameSearchJSON($text,$type,$regexsearch=false) {
	   global $CLIENT_ROOT;
       $agents = array();
       $agentsjson = array();
       switch ($type) {
          case 'Team':
            $clause = " and a.type = 'Team' ";
            break;
          case 'Individual':
            $clause = " and a.type = 'Individual' ";
            break;
          case 'Organization':
            $clause = " and a.type = 'Organization' ";
            break;
          default :
            $clause = "";
       }
       // attempt to recognize a request to do a regex search
       $pattern = '/[[^$+]/';
       if (preg_match($pattern,$text,$m)==1) {
          $regexsearch = true;
       }
       // recognize if the search is to be limited to a particular atomic name field
       $targetfield = "n.name";
       if (preg_match('/^(familyname|firstname|middlename|namestring|yearofbirth|yearofdeath)=(.*)$/',$text,$matches)==1) {
           $text = $matches[2];
           $targetfield = "a.".$matches[1];
       }
       if ($regexsearch) {
           $sql = "select a.agentid, a.type, prefix, firstname, middlename, familyname, suffix, namestring, yearofbirth, yearofbirthmodifier, yearofdeath, yearofdeathmodifier, living, curated, n.name, '' as score from agents a left join agentnames n on a.agentid = n.agentid where ( $targetfield rlike ? ) $clause order by a.type desc, a.familyname asc, a.namestring asc ";
       } else {
           $sql = "select a.agentid, a.type, prefix, firstname, middlename, familyname, suffix, namestring, yearofbirth, yearofbirthmodifier, yearofdeath, yearofdeathmodifier, living, curated, n.name, if(n.name=?,'Exact', truncate(match (n.name) against ( ? ) , 2)) as score from agents a left join agentnames n on a.agentid = n.agentid where ( match (n.name) against ( ? ) or $targetfield like ? ) $clause order by a.type desc, a.familyname asc, a.namestring asc ";
       }
       if ($statement = $this->conn->prepare($sql)) {
          $wildtext = "%$text%";
          if ($regexsearch) {
             $statement->bind_param("s",$text);
          } else {
             $statement->bind_param("ssss", $text, $text, $text, $wildtext);
          }
          $statement->execute();
          $statement->bind_result($agentid,$type,$prefix,$firstname,$middlename,$familyname,$suffix,$namestring, $yearofbirth,$yearofbirthmodifier,$yearofdeath, $yearofdeathmodifier,$living,$curated,$matchednamevariant,$score);
          $statement->store_result();
          $rows = $statement->num_rows;
          $idarray = array();
          while ($statement->fetch()) {
             if (!in_array($agentid,$idarray)) { $idarray[] = $agentid; }
             $name = $this->assembleNameBits($prefix,$firstname,$middlename,$familyname,$suffix,$namestring,'Last, First Middle');
             $dates = "";
             if ($living=='Y') {
                $dates = "($yearofbirth $yearofbirthmodifier-present)";
             } else {
                $dates = "($yearofbirth $yearofbirthmodifier-$yearofdeath $yearofdeathmodifier)";
                $dates = str_replace($dates,' ','');
             }
             $curationstate = "";
             if ($curated==1) {
                $curationstate = "*";
             }
             if (strlen($score)>0) { $displayscore = " at $score"; } else { $displayscore = ''; }
             $agent = "$name $dates $curationstate ";
             if (!array_key_exists($agentid,$agents)) {
                $agentarr = array();
                $agentarr['id'] = $agentid;
                $agentarr['label'] = trim($agent);
                $agentarr['value'] = $agentid;
                $agentsjson[] = $agentarr;
                $agents[$agentid] = $agent;
             }
          }
          $result .= json_encode($agentsjson);
          $statement->close();
       } else {
         $result .= $this->conn->error;
       }
       return $result;
    }

    /**
     * Get agent fields from the request, and construct an
     * agent object using those values.  The agent is loaded from the
     * database and then has values changed from the request if a
     * value is provided for agentid, otherwise a new agent is
     * created.  This method does not save the changes to the
     * database, invoke save on the returned object to accomplish that.
     *
     * @return an agent object in state dirty, or null if an error
     * occurred in obtaining data.
     */
    public function getAndChangeAgentFromRequest($agentid='') {
       $result = new Agent();
       $agentid = preg_replace("[^0-9]",'',$agentid);
       if (strlen($agentid)>0) {
          $result->load($agentid);
       }
       $result->setfamilyname($_REQUEST["familyname"]);
       $result->setfirstname($_REQUEST["firstname"]);
       $result->setmiddlename($_REQUEST["middlename"]);
       $result->setprefix($_REQUEST["prefix"]);
       $result->setsuffix($_REQUEST["suffix"]);
       $result->setnamestring($_REQUEST["namestring"]);
       $result->settype($_REQUEST["type"]);
       $result->setyearofbirth($_REQUEST["yearofbirth"]);
       $result->setyearofbirthmodifier($_REQUEST["yearofbirthmodifier"]);
       $result->setyearofdeath($_REQUEST["yearofdeath"]);
       $result->setyearofdeathmodifier($_REQUEST["yearofdeathmodifier"]);
       $result->setstartyearactive($_REQUEST["startyearactive"]);
       $result->setendyearactive($_REQUEST["endyearactive"]);
       $result->setnotes($_REQUEST["notes"]);
       $result->setrating($_REQUEST["rating"]);
       $result->setguid($_REQUEST["guid"]);
       $result->setbiography($_REQUEST["biography"]);
       $result->settaxonomicgroups($_REQUEST["taxonomicgroups"]);
       $result->setcollectionsat($_REQUEST["collectionsat"]);
       $result->setcurated($_REQUEST["curated"]);
       $result->setmbox_sha1sum($_REQUEST["mbox_sha1sum"]);
       $result->setpreferredrecbyid($_REQUEST["preferredrecbyid"]);
       $result->setnototherwisespecified($_REQUEST["nototherwisespecified"]);
       $result->setliving($_REQUEST["living"]);

       return $result;
    }


    public function getAndChangeAgentNameFromRequest($agentnameid='') {
       $result = new agentnames();
       $agentnameid = preg_replace("[^0-9]",'',$agentnameid);
       if (strlen($agentnameid)>0) {
          $result->load($agentnameid);
       }
       $result->setagentid($_REQUEST["agentid"]);
       $result->settype($_REQUEST["type"]);
       $result->setname($_REQUEST["name"]);
       $result->setlanguage($_REQUEST["language"]);
       return $result;
    }

    public function getAndChangeAgentNumberPatternFromRequest($agentnumberpatternid='') {
       $result = new agentnumberpattern();
       $agentnumberpatternid = preg_replace("[^0-9]",'',$agentnumberpatternid);
       if (strlen($agentnumberpatternid)>0) {
          $result->load($agentnumberpatternid);
       }
       $result->setagentid($_REQUEST["agentid"]);
       $result->setnumbertype($_REQUEST["numbertype"]);
       $result->setnumberpattern($_REQUEST["numberpattern"]);
       $result->setnumberpatterndescription($_REQUEST["numberpatterndescription"]);
       $result->setstartyear($_REQUEST["startyear"]);
       $result->setendyear($_REQUEST["endyear"]);
       $result->setintegerincrement($_REQUEST["integerincrement"]);
       $result->setnotes($_REQUEST["notes"]);
       return $result;
    }

    public function getAndChangeAgentLinksFromRequest($agentlinksid='') {
       $result = new agentlinks();
       $agentlinksid = preg_replace("[^0-9]",'',$agentlinksid);
       if (strlen($agentlinksid)>0) {
          $result->load($agentlinksid);
       }
       $result->setagentid($_REQUEST["agentid"]);
       $result->settype($_REQUEST["type"]);
       $result->setlink($_REQUEST["link"]);
       $result->setisprimarytopicof($_REQUEST["isprimarytopicof"]);
       $result->settext($_REQUEST["text"]);
       return $result;
    }
    public function getAndChangeAgentRelationsFromRequest($agentrelationsid='') {
       $result = new agentrelations();
       $ct = new ctrelationshiptypes();
       $agentrelationsid = preg_replace("[^0-9]",'',$agentrelationsid);
       if (strlen($agentrelationsid)>0) {
          $result->load($agentrelationsid);
       }
       $result->setfromagentid($_REQUEST["fromagentid"]);
       $result->settoagentid($_REQUEST["toagentid"]);
       $result->setrelationship($_REQUEST["relationship"]);
       $result->setnotes($_REQUEST["notes"]);
       if ($ct->isInverse($result->getrelationship())) {
          // if name of an inverse relationship was given, flip to forward
          $result->setrelationship($ct->flipToForward($result->getrelationship()));
          $to = $result->getfromagentid();
          $from = $result->gettoagentid();
          $result->settoagentid($to);
          $result->setfromagentid($from);
       }
       return $result;
    }
    public function getAndChangeAgentTeamsFromRequest($agentteamsid='') {
       $result = new agentteams();
       $agentteamsid = preg_replace("[^0-9]",'',$agenttemsid);
       if (strlen($agentteamsid)>0) {
          $result->load($agentteamsid);
       }
       $result->setteamagentid($_REQUEST["teamagentid"]);
       $result->setmemberagentid($_REQUEST["memberagentid"]);
       $result->setordinal($_REQUEST["ordinal"]);
       return $result;
    }
    /**
     *  Return an html list of first letters of agent names, along with counts, linked to
     *  searches to retrieve those agents.
     *
     *  @param letters, the number of letters to retrieve from the beginning of the agent names,
     *    default is 1, list by first letter of last name from A-Z, letters=2 lists by first two
     *    letters of last name (Aa-Zz).
     *  @return html list of letters linked to agent name searches.
     */
    public function getLastNameLinks($letters=1) {
	   global $CLIENT_ROOT;
       $result = "<ul>";
       $sql = 'select count(*), left(familyname,?) from agents where familyname is not null and familyname <> \'\' group by left(familyname,?) order by left(familyname,?)';
       if ($statement = $this->conn->prepare($sql)) {
          $statement->bind_param('iii',$letters,$letters,$letters);
          $statement->execute();
          $statement->bind_result($count,$letter);
          $statement->store_result();
          while ($statement->fetch()) {
             $result .= "<li><a href='$CLIENT_ROOT/agents/index.php?name=familyname%3d^$letter'>$letter ($count)</a></li>";
          }
          $statement->close();
       } else {
          throw new Exception("Error preparing query $sql");
       }
       $result .= "</ul>";
       return $result;
    }

    public function getNameStats() {
	   global $CLIENT_ROOT;
       $result = "<ul>";
       $sql = 'select count(*) as ct, type from agents group by type';
       if ($statement = $this->conn->prepare($sql)) {
          $statement->execute();
          $statement->bind_result($count,$type);
          $statement->store_result();
          while ($statement->fetch()) {
             $result .= "<li>$type ($count)</li>";
          }
          $statement->close();
       } else {
          throw new Exception("Error preparing query $sql");
       }
       $result .= "</ul>";
       return $result;
    }
    public function getTeamLinks($letters=1) {
	   global $CLIENT_ROOT;
       $result = "<ul>";
       $sql = 'select count(*), left(namestring,?) from agents where type =\'Team\' and namestring is not null and namestring <> \'\' group by left(namestring,?) order by left(namestring,?)';
       if ($statement = $this->conn->prepare($sql)) {
          $statement->bind_param('iii',$letters,$letters,$letters);
          $statement->execute();
          $statement->bind_result($count,$letter);
          $statement->store_result();
          while ($statement->fetch()) {
             $result .= "<li><a href='$CLIENT_ROOT/agents/index.php?name=namestring%3d^$letter'>$letter ($count)</a></li>";
          }
          $statement->close();
       } else {
          throw new Exception("Error preparing query $sql");
       }
       $result .= "</ul>";
       return $result;
    }
    public function getOrganizationLinks($letters=1) {
	   global $CLIENT_ROOT;
       $result = "<ul>";
       $sql = 'select count(*), left(namestring,?) from agents where type =\'Organization\' and namestring is not null and namestring <> \'\' group by left(namestring,?) order by left(namestring,?)';
       if ($statement = $this->conn->prepare($sql)) {
          $statement->bind_param('iii',$letters,$letters,$letters);
          $statement->execute();
          $statement->bind_result($count,$letter);
          $statement->store_result();
          while ($statement->fetch()) {
             $result .= "<li><a href='$CLIENT_ROOT/agents/index.php?name=namestring%3d^$letter'>$letter ($count)</a></li>";
          }
          $statement->close();
       } else {
          throw new Exception("Error preparing query $sql");
       }
       $result .= "</ul>";
       return $result;
    }

    /** Given a name, construct an agent object of the appropriate type.
     *
     * @param name the name of the agent to construct.
     * @return an agent object or null;
     */
    public function constructAgentDetType($name) {
       $result = null;
       if (preg_match("/([|;&]| and )/",$name)) {
          // agent is a composite of more than one agent
          $result = constructNewAgent('Team','','','',$name);
       }
       else {
           if (preg_match("/([Ee]xpedition|Exped.|[Cc]onsortium|Bureau of|[Ss]ociety|[Cc]ommission)/",$name)) {
               $result = constructNewAgent('Organization','','','',$name);
           }
           else {
              $namebits = Agent::parseLeadingNameInList($name);
              $result = constructNewAgent('Individual',$namebits['first'],$namebits['middle'],$namebits['last'],'');
              $result->setnotes($name);
           }
       }
       return $result;
    }

    public function constructNewAgent($type,$firstname,$middlename,$familyname,$name,$notes='') {
       $result = new Agent();
       $result->setType($type);
       if (strlen($notes)>0) {
          $result->setnotes($notes);
       }
       switch ($type)  {
          case 'Individual':
             $result->setfamilyname($familyname);
             $result->setfirstname($firstname);
             $result->setmiddlename($middlename);
             break;
          case 'Team':
          case 'Organization':
             $result->setnamestring($name);
             break;
          default :
			throw new Exception("Unable to construct agent.  Unknown agent type [$type]");
       }
       return $result;
    }

    public function saveAgent($toSave) {
       global $CLIENT_ROOT;
       $result = "";
       if ($toSave->save()) {
          $result .= "Saved. <a href='$CLIENT_ROOT/agents/agent.php?agentid=".$toSave->getagentid()."'>View [". $toSave->getagentid() ."]</a>";
       } else {
          $result .=  "Error in saving agent record [".$toSave->getagentid()."]: " . $toSave->errorMessage();
       }
       return $result;
    }

    public function saveNewAgent($toSave,$forceaka=FALSE) {
       global $CLIENT_ROOT;
       $result = "";
       if ($toSave->save()) {
              switch ($toSave->gettype()) {
                case 'Team':
                    $name = AgentManager::standardizeNameString($toSave->getAssembledName());
                    //$name = str_replace(';','|',$name);  // convert any semicolon separators to pipe
                    //$name = str_replace('|',' | ',$name);  // add spaces around pipe separator
                    $name = preg_replace('/ +/',' ',$name); // strip out any duplicate spaces
                    $name = trim($name);
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Standard DwC List');
                    $an->setname($name);
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                    }
                    // put list in human readable form delimited by ; with the last element separated by and.
                    $aname = explode(' | ',$name);
                    $separator = "";
                    for($i=0;$i<count($aname);$i++) {
                       $sname .= $separator.$aname[$i];
                       // if at penultimate position, change separator to " and " so that it will be
                       // used between the penultimate and ultimate positions.
                       if ($i<(count($aname)-2)) { $separator = "; "; } else { $separator = " and "; }
                    }
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Also Known As');
                    $an->setname($sname);
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                    }
                    break;
                case 'Organization':
                    $name = trim($toSave->getAssembledName());
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Full Name');
                    $an->setname($name);
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                    }
                    break;
                case 'Individual':
                 $suffix = "";
                 if (strlen($toSave->getsuffix())>0) {
                    $suffix = " ". $toSave->getsuffix();
                 }
                 $prefix = "";
                 if (strlen($toSave->getprefix())>0) {
                    $prefix = $toSave->getprefix() . " ";
                 }
                 $hitlong = FALSE;
                 if (strlen($toSave->getfirstname())>2 && strlen($toSave->getmiddlename())>2) {
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Full Name');
                    $an->setname($toSave->getAssembledName());
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                   }
                   $hitlong = TRUE;
                 }
                 if (!$hitlong &&
                      strlen($toSave->getfirstname())>1 && strlen($toSave->getmiddlename())>1 &&
                      (strpos($toSave->getAssembledName(),'.')===false)
                 ) {
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Full Name');
                    $an->setname($toSave->getAssembledName());
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                   }
                   $hitlong = TRUE;
                 }
                 if (strlen($toSave->getfirstname())>0 || strlen($toSave->getmiddlename())>0) {
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Last Name, Initials');
                    $an->setname(str_replace(" .",'',$prefix.$toSave->getfamilyname() . "$suffix, " . substr($toSave->getfirstname(),0,1) . ". " . substr($toSave->getmiddlename(),0,1) . "."));
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                    }
                 }
                 if (strlen($toSave->getfirstname())>0 || strlen($toSave->getmiddlename())>0) {
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Initials Last Name');
                    $an->setname(str_replace(" .",'',substr($toSave->getfirstname(),0,1) . ". " . substr($toSave->getmiddlename(),0,1) . ". $prefix"  . $toSave->getfamilyname(). $suffix));
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                    }
                 }
                 if (strlen($toSave->getfirstname())>2) {
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('First Initials Last');
                    $an->setname(str_replace(" .",'',$toSave->getfirstname() . " " . substr($toSave->getmiddlename(),0,1) . ". $prefix"  . $toSave->getfamilyname().$suffix));
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                    }
                    $hitlong = TRUE;
                 }
                 if (!$hitlong && $forceaka) {
                    $an = new agentnames();
                    $an->setagentid($toSave->getagentid());
                    $an->setType('Also Known As');
                    $an->setname($toSave->getAssembledName());
                    if (!$an->save()) {
                        $result .=  "Error in saving agent name record: " . $an->errorMessage();
                    }
                 }
                 break;
                default :
                  throw new Exception("Error: Unable to create name for agent without a recognized type.");
              }
              $result .= "Saved. <a href='$CLIENT_ROOT/agents/agent.php?agentid=".$toSave->getagentid()."'>View [". $toSave->getagentid() ."]</a>";
       } else {
            $result .=  "Error in saving agent record [".$toSave->getagentid()."]: " . $toSave->errorMessage();
       }
       return $result;
    }


    public function saveNewAgentName($toSave) {
       global $CLIENT_ROOT;
       $result = "";
         if ($toSave->save()) {
            $id = "_".$toSave->getagentid()."_".$toSave->getagentnamesid();
            $result = "Saved: <a id='editLink$id' onClick=' handlerEdit$id();'>" . $toSave->getname() . '</a> ('. $toSave->gettype().')';
//            $result .= "<script type='text/javascript'>$('editLink$id').bind('click',handlerEdit$id);</script>";
         } else {
            $result .=  "Error in saving agentname record: " . $toSave->errorMessage();
         }
       return $result;
    }

    public function saveNewAgentNumberPattern($toSave) {
       global $CLIENT_ROOT;
       $result = "";
         if ($toSave->save()) {
            $id = "_".$toSave->getagentid()."_".$toSave->getagentnumberpatternid();
            $result = "Saved: <a id='editLink$id' onClick=' handlerEdit$id();'>" . $toSave->getnumbertype() . '</a> ('. $toSave->getnumbertype().')';
//            $result .= "<script type='text/javascript'>$('editLink$id').bind('click',handlerEdit$id);</script>";
         } else {
            $result .=  "Error in saving agent number pattern record: " . $toSave->errorMessage();
         }
       return $result;
    }

    public function saveNewAgentLinks($toSave) {
       global $CLIENT_ROOT;
       $result = "";
         if ($toSave->save()) {
            $id = "_".$toSave->getagentid()."_".$toSave->getagentlinksid();
            $result = "Saved: <a id='editLink$id' onClick=' handlerEdit$id();'>" . $toSave->gettext() . '</a> ('. $toSave->gettext().')';
//            $result .= "<script type='text/javascript'>$('editLink$id').bind('click',handlerEdit$id);</script>";
         } else {
            $result .=  "Error in saving agent links record: " . $toSave->errorMessage();
         }
       return $result;
    }
    public function saveNewAgentRelations($toSave) {
       global $CLIENT_ROOT;
       $result = "";
         if ($toSave->save()) {
            $id = "_".$toSave->getfromagentid()."_".$toSave->getagentrelationsid();
            $result = "Saved: <a id='editRelation$id' onClick=' handlerEdit$id();'>" . $toSave->getrelationship() . '</a>';
         } else {
            $result .=  "Error in saving agent relationship record: " . $toSave->errorMessage();
         }
       return $result;
    }
    public function saveNewAgentTeams($toSave) {
       global $CLIENT_ROOT;
       $result = "";
         if ($toSave->save()) {
            $id = "_".$toSave->getagentteamid();
            $result = "Saved: ". $toSave->getagentteamid();
         } else {
            $result .=  "Error in saving agent team record: " . $toSave->errorMessage();
         }
       return $result;
    }


    /**
     * Given an id for an agent, get the list of names for that agent, marked up as html for display.
     *
     * @param agentid, the id for the agent for which to find names.
     * @return an html list of agent names
     */
    public function getAgentNamesForAgent($agentid) {
	   global $CLIENT_ROOT;
       $editable = false;
       if ($this->isAgentEditor()) {
         $editable = true;
       }
       $result  = "";
       $link = "";
       if ($editable) {
           $link= "<a id='addAgentNameLink'>Add</a>";
       }
       $result .= "<div id='addAgentNameDiv'>$link</div>\n";
       $result .= "<div id='addedAgentNamesDiv'></div>\n";
       if ($editable) {
          $result .= "
     <script type='text/javascript'>
        function handlerAddName () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=create&table=AgentName&agentid=".$agentid."',
               dataType : 'html',
               success: function(data){
                  $('#addedAgentNamesDiv').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#addAgentNameLink').bind('click',handlerAddName);
     </script>
          ";
       }
       $result .= "<ul>";
       $sql = "select agentnamesid, type, name from agentnames where agentid = ? ";
       if ($statement = $this->conn->prepare($sql)) {
          $statement->bind_param('i',$agentid);
          $statement->execute();
          $statement->bind_result($agentnamesid, $type, $name);
          $statement->store_result();
          while ($statement->fetch()) {
             if (!$editable) {
                $result .= "<li>$name ($type)</li>";
             } else {
                $id = "_$agentid"."_$agentnamesid";  // Use to make html element ids unique
                $link = "<a id='editLink$id'>$name</a> ($type) <a id='deleteLink$id'>Delete</a>";
                $result .= "<li><div id='nameDetailDiv$id' >$link</div></li>";
                $result .= "
     <script type='text/javascript'>
        function handlerEdit$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=edit&table=AgentName&agentnamesid=".$agentnamesid."',
               dataType : 'html',
               success: function(data){
                  $('#nameDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#editLink$id').bind('click',handlerEdit$id);
        function handlerDelete$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=delete&table=AgentName&agentnamesid=".$agentnamesid."',
               dataType : 'html',
               success: function(data){
                  $('#nameDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#deleteLink$id').bind('click',handlerDelete$id);
     </script>
                ";
             }
          }
          $statement->close();
       } else {
          throw new Exception(" Error preparing query $sql");
       }
       $result .= "</ul>";
       return $result;
    }

    /**
     * Obtain an html formatted list of the members of an agent that is a team,
     *  or an empty string if the agent is not a team.
     * @param agentid the team agent to find members for.
     * @return html list of team members or an empty string.
     */
    public function getTeamMembersForAgent($agentid) {
	   global $CLIENT_ROOT;
       $count = 0;
       $editable = false;
       if ($this->isAgentEditor()) {
         $editable = true;
       }
       $sql = "select count(*) from agents where agentid = ? and type = 'Team' ";
       if ($statement = $this->conn->prepare($sql)) {
          $statement->bind_param('i',$agentid);
          $statement->execute();
          $statement->bind_result($count);
          $statement->store_result();
          $statement->fetch();
       } else {
           throw new Exception("Error preparing statement $sql");
       }
       if ($count > 0) {
          $result = "<li><h3>Team Members</h3></li><ul>";
          if ($editable) {
              $link= "<a id='addTeamMemberLink'>Add</a>";
              $result .= "<div id='addTeamMember'>$link</div>\n";
              $result .= "<div id='addedTeamMemberDiv'></div>\n";
              $result .= "
     <script type='text/javascript'>
        function handlerAddTeamMember() {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=create&table=AgentTeams&teamagentid=".$agentid."',
               dataType : 'html',
               success: function(data){
                  $('#addedTeamMemberDiv').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#addTeamMemberLink').bind('click',handlerAddTeamMember);
     </script>
              ";
          }
          $sql = "select memberagentid, agentteamid from agentteams where teamagentid = ? order by ordinal asc ";
          if ($statement = $this->conn->prepare($sql)) {
             $statement->bind_param('i',$agentid);
             $statement->execute();
             $statement->bind_result($memberagentid,$agentteamid);
             $statement->store_result();
             $countpotential += $statement->num_rows;
             $member = new Agent();
             while ($statement->fetch()) {
                $count ++;
                $member->load($memberagentid);
                $name = $member->getAssembledName();
                $delete = "";
                if ($editable) {
                  $delete = " <a id='deleteAT_Link$agentteamid'>Delete</a>";
                  $delete .= "
     <script type='text/javascript'>
        function handlerATDelete$agentteamid () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=delete&table=AgentTeams&agentteamid=".$agentteamid."',
               dataType : 'html',
               success: function(data){
                  $('#ATDetailDiv$agentteamid').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#deleteAT_Link$agentteamid').bind('click',handlerATDelete$agentteamid);
     </script>
                   ";
                }
                $result .= "<li><div id='ATDetailDiv$agentteamid'><a href='$CLIENT_ROOT/agents/agent.php?agentid=$memberagentid'>$name</a>$delete</div></li>";
             }
          } else {
              throw new Exception("Error preparing statement $sql");
          }
          $result .= "</ul>";
       }
       return $result;
    }

    /**
     * Obtain an html formatted list of teams that an agent is a member of.
     * @param agentid the agent to find teams for.
     * @return html list of teams.
     */
    public function getTeamMembershipForAgent($agentid) {
	   global $CLIENT_ROOT;
          $result = "<li><h3>Member of Teams</h3></li><ul>";
          $sql = "select teamagentid from agentteams where memberagentid = ? order by ordinal asc ";
          if ($statement = $this->conn->prepare($sql)) {
             $statement->bind_param('i',$agentid);
             $statement->execute();
             $statement->bind_result($teamagentid);
             $statement->store_result();
             $countpotential += $statement->num_rows;
             $member = new Agent();
             while ($statement->fetch()) {
                $count ++;
                $member->load($teamagentid);
                $name = $member->getAssembledName();
                $result .= "<li><a href='$CLIENT_ROOT/agents/agent.php?agentid=$teamagentid'>$name</a></li>";
             }
          } else {
              throw new Exception("Error preparing statement $sql");
          }
          $result .= "</ul>";
       return $result;
    }

    public function getLinksForAgent($agentid) {
	   global $CLIENT_ROOT;
       $result  = "";
       $result .= "<li><h3>Links</h3></li>";
       $link = "";
       if ($this->isAgentEditor()) {
           $link .= "<a id='addAgentLinkLink'>Add</a>\n";
       }
       $result .= "<div id='addAgentLinkDiv'>$link</div>\n";
       $result .= "<div id='addedAgentLinkDiv'></div>\n";
       if ($this->isAgentEditor()) {
          $result .= "
     <script type='text/javascript'>
        function handlerAddLink () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=create&table=AgentLinks&agentid=".$agentid."',
               dataType : 'html',
               success: function(data){
                  $('#addedAgentLinkDiv').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#addAgentLinkLink').bind('click',handlerAddLink);
     </script>
          ";
       }
       $l = new agentlinks();
       $links = $l->loadArrayByagentid($agentid);
       if (count($links)>0) {
           $linkView = new agentlinksView();
           $result .= "<ul>";
           foreach ($links as $link) {
                  $linkView->setModel($link);
                  $result .= '<li>' . $linkView->getSummaryLine($this->isAgentEditor()) . '</li>';
           }
           $result .= "</ul>";
       }
       return $result;
    }

    /**
     * Given an id for an agent, get the relationships for that agent, marked up as html for display.
     *
     * @param agentid, the id for the agent for which to find relationships.
     * @return an html list of agent relationships.
     */
    public function getRelationsForAgent($agentid) {
	   global $CLIENT_ROOT;
       $result  = "";
       $result .= "<li><h3>Relationships</h3></li>";
       $link = "";
       if ($this->isAgentEditor()) {
           $link .= "<a id='addAgentRelationLink'>Add</a>\n";
       }
       $result .= "<div id='addAgentRelationDiv'>$link</div>\n";
       $result .= "<div id='addedAgentRelationDiv'></div>\n";
       if ($this->isAgentEditor()) {
          $result .= "
     <script type='text/javascript'>
        function handlerAddRelation () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=create&table=AgentRelations&fromagentid=".$agentid."',
               dataType : 'html',
               success: function(data){
                  $('#addedAgentRelationDiv').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#addAgentRelationLink').bind('click',handlerAddRelation);
     </script>
          ";
       }
       $r = new agentrelations();
       $frelations = $r->loadArrayByfromagentid($agentid);
       if (count($frelations)>0) {
           $rView = new agentrelationsView();
           $result .= "<ul>";
           foreach ($frelations as $relation) {
              $rView->setModel($relation);
              $result .= '<li>' . $rView->getSummaryLine($this->isAgentEditor(),false) . '</li>';
           }
           $result .= "</ul>";
       }
       $trelations =  $r->loadArrayBytoagentid($agentid);
       if (count($trelations)>0) {
           $rView = new agentrelationsView();
           $result .= "<ul>";
           foreach ($trelations as $relation) {
              $rView->setModel($relation);
              $result .= '<li>' . $rView->getSummaryLine($this->isAgentEditor(),true) . '</li>';
           }
           $result .= "</ul>";
       }
       return $result;
    }


    /**
     * Given an id for an agent, get the collector number patterns for that agent, marked up as html for display.
     *
     * @param agentid, the id for the agent for which to find collector number patterns.
     * @return an html list of agent number patterns.
     */
    public function getNumberPatternsForAgent($agentid) {
	   global $CLIENT_ROOT;
       $result  = "";
       $result .= "<li><h3>Collector Number Patterns</h3></li>";
       $link = "";
       if ($this->isAgentEditor()) {
           $link .= "<a id='addNumberPatternLink'>Add</a>\n";
       }
       $result .= "<div id='addNumberPatternDiv'>$link</div>\n";
       $result .= "<div id='addedNumberPatternDiv'></div>\n";
       if ($this->isAgentEditor()) {
          $result .= "
     <script type='text/javascript'>
        function handlerAddName () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=create&table=AgentNumberPattern&agentid=".$agentid."',
               dataType : 'html',
               success: function(data){
                  $('#addedNumberPatternDiv').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#addNumberPatternLink').bind('click',handlerAddName);
     </script>
          ";
       }
       $numpat = new agentnumberpattern();
       $numpats = $numpat->loadArrayByagentid($agentid);
       if (count($numpats)>0) {
           $numpatView = new agentnumberpatternView();
           $result .= "<ul>";
           foreach ($numpats as $np) {
                  $numpatView->setModel($np);
                  $result .= '<li>' . $numpatView->getSummaryLine($this->isAgentEditor()) . '</li>';
           }
           $result .= "</ul>";
           //$result .= "<table>";
           //$result .= $numpatView->getShortHeaderRow();
           //foreach ($numpats as $np) {
           //       $numpatView->setModel($np);
           //       $result .= $numpatView->getShortTableRowView();
           //}
           //$result .= "</table>";
       }
       return $result;
    }

    public function createTeamsFromCollectors() {
       $count = 0;
       $countpotential = 0;
       $teamcount = 0;
       $leaveout = " and recordedby not like '\"' and associatedcollectors not like '\"' ";
       // obtain list of possible pipe or semicolon delimited teams from recordedby and associated collectors.
       $sql = "select min(year(eventdate)), max(year(eventdate)), concat(recordedby,ifnull(concat('|',associatedcollectors),'')) from omoccurrences where recordedby is not null and (associatedcollectors is not null or recordedby rlike '[;|]') and recordedby like ? $leaveout group by concat(recordedby,ifnull(concat('|',associatedcollectors),''))";
       $letters = array( 'A%', 'B%', 'C%', 'D%', 'E%', 'F%', 'G%', 'H%', 'I%', 'J%', 'K%', 'L%', 'M%', 'N%', 'O%', 'P%', 'Q%', 'R%', 'S%', 'T%', 'U%', 'V%', 'W%', 'X%', 'Y%', 'Z%');
       if ($statement = $this->conn->prepare($sql)) {
          foreach ($letters as $letter) {
            $lcount = 0;
            $statement->bind_param('s',$letter);
            $statement->execute();
            $statement->bind_result($startyear, $endyear, $name);
            while ($statement->fetch()) {
              $countpotential++;
              // convert any semicolon delimiters to the DarwinCore standard pipe.
              // This does not handle any cases where a comma is the delimiter for a list of names.
              $team = trim(html_entity_decode($name));
              $team = str_replace(' & ','|',$team);  // amperstand is a frequent separator in name lists
              $team = str_replace(' and ','|',$team);
              $team = str_replace(';','|',$team);

              // remove some pathologies found in real data
              $team = str_replace(" et. al.",'',$team);
              $team = str_replace(" et al.",'',$team);
              $team = str_replace(" et al",'',$team);
              $team = preg_replace("/\| *et al\./",'|',$team);
              $team = preg_replace("/\| *[0-9]+(\|$)/",'|',$team);   // collector number
              $team = str_replace("collected by",'',$team);
              $team = str_replace('\\\'',"'",$team);
              $team = str_replace('\\\"','"',$team);

              // cleanup separators
              $team = preg_replace("/\| *\|/",'|',$team);     // repeated separators
              $team = preg_replace("/\| *$/",'',$team);         // terminal separator
              $team = trim(preg_replace("/  /",' ',$team));  // remove any double spaces and trim.

              // explode into array on pipe character
              $members = explode('|',$team);
              $memberagentids = array();
              $membercount = 0;
              foreach($members as $member) {
                  $member = trim($member);
                  if (strlen(trim($member))>1) { $membercount++; }
                  $pattern = '/^('.'[A-Z][a-z]+ [A-Z][a-z]+ [A-Z][a-z]+|'.'|'.   // First Middle Last
                                  '[A-Z][a-z]+ [A-Z]\. [A-Z][a-z]+'.'|'.        // First Initial Last
                                  '[A-Z]\. [A-Z]\. [A-Z][a-z]+'.')$/';           // Initials Last
                  if (preg_match($pattern,$member)) {
                     $notes = "Generated from '$member' split out of team collector name '$name'.";
                     $add = $this->addFMLAgentIfNotExist($member,null,null,$notes);
                     $count += $add['added'];
                     $memberagentids[] = $add['agentid'];
                  }
              }
              // skip some pathological cases
              $skip = FALSE;
              if (strpos($name,'participants')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' Students')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' students')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' Class ')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' class')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' Bioblitz ')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' workshop')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' Workshop')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' typo ')!==FALSE) { $skip = TRUE; }
              if (strpos($name,' should ')!==FALSE) { $skip = TRUE; }
              if ($membercount>1 && !$skip) {
                 // plausibly is a team.
                 $ag = new Agent();
                 $teamid = $ag->findIdByName($team);
                 if (!$teamid) {
                    $team = AgentManager::standardizeNameString($team);
                    $an = new agentnames();
                    $matches = $an->findAgentIdByName($team);
                    if (count($matches)==0) {
                       $notes = "Generated from collector name '$name'.";
                       $toSave = $this->constructNewAgent('Team','','','',$team,$notes);
                       $result .= $this->saveNewAgent($toSave);
                       $teamid = $toSave->getagentid();
                       $teamcount++;
                       $lcount++;
                    } else {
                       $teamid = $matches[0];
                    }
                 }
                 $ordinal = 1;
                 foreach ($memberagentids as $memberagentid) {
                     $at = new agentteams();
                     $at->setteamagentid($teamid);
                     $at->setmemberagentid($memberagentid);
                     $at->setordinal($ordinal);
                     $at->save();
                     $ordinal ++;
                 }
              }
            }
            echo "$letter(+$lcount) ";
            flush();
          }
       } else {
         $error .= "Error preparing query '$sql'. ";
       }
       return "<h3>Created $count individual records and $teamcount team agent records from $countpotential collector teams.</h3>\n<h3>$error</h3>\n$result\n";
    }

    /** Given the name of an individual agent in the form "first middle last" where
      * first or middle may be initials, create a new agent if one doesn't exist.
      * Intended as a convenience method for creating agents from collector records,
      * if year of birth and death are known, set directly on Agent record.
      *
      * @param name of the agent to add, must be in form "first middle last" with
      *   spaces as separators.
      * @startyear approximate first year collected or null
      * @endyear approximate last year collected or null
      *
      * @returns an array of name, agentid, and added (0|1).
      */
    public function addFMLAgentIfNotExist($name,$startyear,$endyear,$notes) {
        $result = array();
        $name = AgentManager::standardizeNameString($name);
        $an = new agentnames();
        $matches = $an->findAgentIdByName(trim($name));
        if (count($matches)==0) {
           $bits = explode(' ',$name);
           if ($startyear=='0' || strlen(trim($startyear)==0)) { $startyear = null; }
           if ($endyear=='0' || strlen(trim($endyear)==0)) { $endyear = null; }
           $toSave = $this->constructNewAgent('Individual',$bits[0],$bits[1],$bits[2],'',$notes);
           $toSave->setPlausibleYearValues($startyear,$endyear);
           $result['name']  = $this->saveNewAgent($toSave,TRUE);
           $result['agentid'] = $toSave->getagentid();
           $result['added'] = 1;
        } else {
           $result['name'] = $name;
           $result['agentid'] = $matches[0];  // get first agentid from matches array
           $result['added'] = 0;
        }
        return $result;
    }

    public function addAgentsFromPartsIfNotExist($name,$firstname,$middlename,$familyname,$birthyear,$deathyear,$living,$notes) {
        $result = array();
        $name = AgentManager::standardizeNameString($name);
        $an = new agentnames();
        $matches = $an->findAgentIdByName(trim($name));
        if (count($matches)==0) {
           if ($birthyear=='0' || strlen(trim($birthyear)==0)) { $birthyear = null; }
           if ($deathyear=='0' || strlen(trim($deathyear)==0)) { $deathyear = null; }
           $toSave = $this->constructNewAgent('Individual',$firstname,$middlename,$familyname,$name,$notes);
           $toSave->setyearofbirth($birthyear);
           $toSave->setyearofdeath($deathyear);
           $toSave->setliving($living);
           $result['name']  = $this->saveNewAgent($toSave,TRUE);
           $result['agentid'] = $toSave->getagentid();
           $result['added'] = 1;
        } else {
           $result['name'] = $name;
           $result['agentid'] = $matches[0];  // get first agentid from matches array
           $result['added'] = 0;
        }
        return $result;
    }

    public function addAgentsFromPartsIfNotExistExt($name,$prefix,$firstname,$middlename,$familyname,$suffix,$birthyear,$deathyear,$living,$notes,$guid) {
        $result = array();
        $name = AgentManager::standardizeNameString($name);
        $an = new agentnames();
        $matches = $an->findAgentIdByName(trim($name));
        if (count($matches)==0) {
           if ($birthyear=='0' || strlen(trim($birthyear)==0)) { $birthyear = null; }
           if ($deathyear=='0' || strlen(trim($deathyear)==0)) { $deathyear = null; }
           $toSave = $this->constructNewAgent('Individual',$firstname,$middlename,$familyname,$name,$notes);
           $toSave->setyearofbirth($birthyear);
           $toSave->setyearofdeath($deathyear);
           $toSave->setliving($living);
           if ($prefix!=null && strlen($prefix)>0) { $toSave->setprefix($prefix); }
           if ($suffix!=null && strlen($suffix)>0) { $toSave->setsuffix($suffix); }
           if ($guid!=null && strlen($guid)>0) { $toSave->setguid($guid); }
           $result['name']  = $this->saveNewAgent($toSave,TRUE);
           $result['agentid'] = $toSave->getagentid();
           $result['added'] = 1;
        } else {
           $result['name'] = $name;
           $result['agentid'] = $matches[0];  // get first agentid from matches array
           $result['added'] = 0;
        }
        return $result;
    }

    public static function standardizeNameString($name) {
         $name = trim($name);
         $name = str_replace(';','|',$name);
         $name = str_replace('|',' | ',$name);
         $name = preg_replace('/ +/',' ',$name);   // remove duplicate spaces
         $name = preg_replace('/ \| $/','',$name); // remove any trailing pipe
         $name = preg_replace('/^ \| /','',$name); // remove any leading pipe
         $name = trim($name);
         $pattern = array();
         $pattern[] = "/^([A-Z]) ([A-Z]) ([A-Z][a-z]+)$/"; // initials last, no periods
         $pattern[] = "/^([A-Z])\.([A-Z])\. ([A-Z][a-z]+)$/"; // intitals last, no space
         foreach ($pattern as $pat) {
            if (preg_match($pat,$name,$mth)) {
                $name = $mth[1].'. '.$mth[2].'. '. $mth[3];
            }
         }
         $name = trim($name);
         return $name;
    }

    /**
     *  Populate the agents and agentnames tables from existing collector strings.
     *
     *  Examnine the values of omoccurrences.recordedBy, extract strings that fit the patterns of
     *  Agent names in known forms, and if matching agents don't exist, add them.
     *
     *  @return a string describing actions that were carried out.
     */
    public function createCollectorsByPattern() {
      $result = "";
      $error = "";
      $count = 0;
      $countpotential = 0;
      $pattern[] = '^[A-Z][a-z]+ [A-Z][a-z]+ [A-Z][a-z]+$';   // First Middle Last
      $pattern[] = '^[A-Z][a-z]+ [A-Z]\. [A-Z][a-z]+$';   // First Initial Last
      $pattern[] = '^[A-Z]\. [A-Z]\. [A-Z][a-z]+$';   // Initials Last
      $pattern[] = '^[A-Z] [A-Z] [A-Z][a-z]+$';   // Initials Last, no periods
      $pattern[] = '^[A-Z]\.[A-Z]\. [A-Z][a-z]+$';   // Initials Last, no space
      $letters = array( 'A%', 'B%', 'C%', 'D%', 'E%', 'F%', 'G%', 'H%', 'I%', 'J%', 'K%', 'L%', 'M%', 'N%', 'O%', 'P%', 'Q%', 'R%', 'S%', 'T%', 'U%', 'V%', 'W%', 'X%', 'Y%', 'Z%');
      // using rlike binary for case sensitive regex.
      $leaveout = " and recordedby not like '\"' ";
      $sql = 'select min(year(eventdate)), max(year(eventdate)), recordedby from omoccurrences where recordedby like ? and recordedby rlike BINARY ? '.$leaveout.' group by recordedby order by count(*) desc';
      if ($statement = $this->conn->prepare($sql)) {
          foreach ($pattern as $pat) {
            echo "<p>$pat</p>\n";
            flush();
            foreach ($letters as $letter) {
             $lcount = 0;
             $statement->bind_param('ss',$letter,$pat);
             $statement->execute();
             $statement->bind_result($startyear, $endyear, $name);
             $statement->store_result();
             $countpotential += $statement->num_rows;
             while ($statement->fetch()) {
                 // test again, as mysql's regex like may not behave with multibyte characters.
                 if (preg_match("/$pat/",$name)) {
                    $notes = "Generated from collector name '$name'.";
                    $add = $this->addFMLAgentIfNotExist($name,$startyear,$endyear,$notes);
                    if ($add['added']==1) {
                       $count++;
                       $lcount++;
                       $result .= $add['name'];
                    }
                 }
             }
             echo "$letter(+$lcount) ";
             flush();
            }
          }
          $statement->close();
      } else {
         $error .= "Error preparing query '$sql'. ";
      }
      // different patterns from the first middle last three atom pattern.
      $pattern = '^[A-Z][a-z]+, [A-Z]\. [A-Z]\.$';   // Last, Initials

      return "<h3>Created $count agent records from $countpotential collectors.</h3>\n<h3>$error</h3>\n$result\n";
    }

    /** Given an agentid, get an html formated list of collection object
     * records possibly collected by that collector.
     */
    public function getPrettyListOfCollectionObjectsForCollector($agentid) {
       global $CLIENT_ROOT;
       $result = "";
       $array = $this->getListOfCollectionObjectsForCollector($agentid);
       $hits = count($array);
       $s = 's';
       if ($hits==1) { $s = ''; }
       $result .= "<h3>Found $hits collection object record$s that have a collector that matches a form of this agent's name.  Numbers in bold match known patterns for this collector.</h3>";
       foreach ($array as $occid => $row) {
          $dwctriplet = $row['institutioncode'] . ':' . $row['collectioncode'] . ':'. $row['catalognumber'];
          $matchstart = '';  $matchend = '';
          if ($row['matchespattern']==1) { $matchstart = "<strong>";  $matchend = "</strong>"; }
          $coll = $row['datecollected'] . " " . $row['collector'] . " $matchstart" . $row['collectornumber'] . $matchend;
          $result .= "<a href='$CLIENT_ROOT/collections/individual/index.php?occid=$occid' >$dwctriplet</a> $coll<br>";
       }
       return $result;
    }

    /**
     * Given an agentid, return an array containing a summary of collection
     * object records that might have been collected by that collector.
     *
     * @param agentid the primary key value for the agent to search for.
     * @result an array, indexed by occid containing arrays of key value
     * pairs for the summary of the occurrence record.
     */
    public function getListOfCollectionObjectsForCollector($agentid) {
       $result = array();
       // obtain a list of agent number patterns for this collector
       $sql = 'select numberpattern from agentnumberpattern where agentid = ? ';
       if ($statement = $this->conn->prepare($sql)) {
          $statement->bind_param('i',$agentid);
          $statement->execute();
          $statement->bind_result($pattern);
          $statement->store_result();
          while ($statement->fetch()) {
             $result = $result + $this->getListCO($pattern,$agentid);
          }
          $result = $result + $this->getListCO('.*',$agentid);
          $statement->close();
       } else {
           $error .= "Error preparing query '$sql'. ";
       }
       return $result;
    }

    protected function getListCO($pattern,$agentid) {
       $result = array();
       // simple rlike query to get records with matching collector and collector number pattern.
       // $sql = 'select occid, ifnull(o.institutioncode,c.institutioncode) as institutioncode, ifnull(o.collectioncode,c.collectioncode) as collectioncode, catalognumber, recordedby, eventdate, recordnumber from omoccurrences o inner join omcollections c on o.collid = c.collid where recordedby in (select name from agentnames where agentid = 1831) and recordnumber rlike ? order by eventdate asc ';
       // using regexp in the query to obtain all records but coding them for match on the number pattern.
       // subquery on recordedby in is inefficient, first get forms of the collector name, then query for occurrence records.
       $sql = "select name from agentnames where agentid = ? ";
       $namelist = "";
       $comma = "";
       if ($stmt = $this->conn->prepare($sql)) {
           $stmt->bind_param("i",$agentid);
           $stmt->execute();
           $stmt->bind_result($name);
           while ($stmt->fetch()) {
             $namelist .= "$comma'$name'";
             $comma = ",";
           }
       }
       // obtain any recordedbyid
       if ($pattern=='.*') {
          $sql = " select * from (
                select occid, ifnull(o.institutioncode,c.institutioncode) as institutioncode, ifnull(o.collectioncode,c.collectioncode) as collectioncode, catalognumber, recordedby, eventdate, recordnumber, ? = '' as matches  from omoccurrences o inner join omcollections c on o.collid = c.collid where o.recordedbyid = ?
                union
                select occid, ifnull(o.institutioncode,c.institutioncode) as institutioncode, ifnull(o.collectioncode,c.collectioncode) as collectioncode, catalognumber, recordedby, eventdate, recordnumber, ? = '' as matches  from omoccurrences o inner join omcollections c on o.collid = c.collid where recordedby in (?)
                ) sub order by matches desc, eventdate asc
          ";
       } else {
          $sql = "select * from (
                  select occid, ifnull(o.institutioncode,c.institutioncode) as institutioncode, ifnull(o.collectioncode,c.collectioncode) as collectioncode, catalognumber, recordedby, eventdate, recordnumber, recordnumber regexp ? as matches  from omoccurrences o inner join omcollections c on o.collid = c.collid  where (o.recordedbyid = ?) and recordnumber is not null and recordnumber <> 's.n.' and recordnumber <> 'unknown'
                  union
                  select occid, ifnull(o.institutioncode,c.institutioncode) as institutioncode, ifnull(o.collectioncode,c.collectioncode) as collectioncode, catalognumber, recordedby, eventdate, recordnumber, recordnumber regexp ? as matches  from omoccurrences o inner join omcollections c on o.collid = c.collid where (recordedby in (?)) and recordnumber is not null and recordnumber <> 's.n.' and recordnumber <> 'unknown'
                  ) sub order by matches desc, eventdate asc";
       }
       if ($stmt = $this->conn->prepare($sql)) {
           $stmt->bind_param("siss",$pattern,$agentid,$pattern,$namelist);
           $stmt->execute();
           $stmt->bind_result($occid,$institutioncode,$collectioncode,$catalognumber,$collector,$datecollected,$collectornumber,$matchespattern);
           while ($stmt->fetch()) {
              $row = array();
              $row['institutioncode']=$institutioncode;
              $row['collectioncode']=$collectioncode;
              $row['catalognumber']=$catalognumber;
              $row['collector']=$collector;
              $row['datecollected']=$datecollected;
              $row['collectornumber']=$collectornumber;
              $row['matchespattern']=$matchespattern;
              $result[$occid] = $row;
           }
           $stmt->close();
       } else {
            $error .= "Error preparing query '$sql'. ";
       }
       return $result;
    }

    /**
     *  Obtain an html list of agents which are bad duplicates of the specified agent.
     *
     *  @param agentid of the good agent.
     *  @return html listing the bad duplicates for this agent, if any.
     */
    function getBadDuplicatesForAgent($agentid) {
       global $CLIENT_ROOT;
       $result = "";
       $agent = new Agent();
       $agent->load($agentid);
       $baddups = $agent->getBadDuplicatesForAgent();
       if (count($baddups)>0) {
          $result .= "<li><h3>Has bad duplicates</h3></li>";
       }
       $result .= '<ul>';
       foreach ($baddups as $keyagentid => $valueagent) { 
          $result .= "<li><a href='$CLIENT_ROOT/agents/agent.php?agentid=$keyagentid'>" . htmlspecialchars($valueagent->getAssembledName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a>";
       } 
       $result .= '</ul>';
       return $result;
    }

    /**
     * Returns the html for an ajax filtering autocomplete control for picking an
     * agent by name and returning the id of that agent.
     * @param fieldname the name to give the hidden input field that will be submitted
     *   carrying the value of the selected agentid.
     * @param label the label to provide for the picklist
     * @param currentagentid optional value for the currently selected agentid to display
     *   as the currently selected agent.
     * @returns html with javascript.
     */
    public function createAgentPicker($fieldname,$label,$currentagentid=''){
       global $CLIENT_ROOT;
       $id = rand(1,100000);
       $returnvalue .= "
       <script type='text/javascript'>
          $('#agentselect$id').autocomplete({
              source: '".$CLIENT_ROOT."/agents/rpc/handler.php?mode=listjson',
              minLength: 2,
              select: function( event, ui ) {
                    $('#$fieldname').val(ui.item.value);
                    $('#agentselect$id').val(ui.item.label);
                    event.preventDefault();
                 }
              });
       </script>
       ";
       $dupof = "";
       if (strlen($currentagentid)>0) {
          $dup = new Agent();
          $dup->load($currentagentid);
          $dupof = $dup->getAssembledName(TRUE);
       }
       $returnvalue .= '<li>
                          <div class="ui-widget">
                             <label for="agentselect'.$id.'">'.$label.'</label>
                             <input id="agentselect'.$id.'" value="'.$dupof.'"/>
                             <button onClick=\' $( "#agentselect'.$id.'").val(""); $("#'.$fieldname.'").val(""); \' >-</button>
                             <input type="hidden" id="'.$fieldname.'" name="'.$fieldname.'" value="'.$currentagentid.'"/>
                          </div>
                        </li>';
       return $returnvalue;
    }

}

// **************************************************************************************
// ***************** Supporting Model Classes *******************************************
// **************************************************************************************

/**
 * PDO model class for agents
 */
class Agent {
	protected $conn;

	private $agentid; // PK INTEGER
	private $familyname; // VARCHAR(45)
	private $firstname; // VARCHAR(45)
	private $middlename; // VARCHAR(45)
	private $startyearactive; // INTEGER
	private $endyearactive; // INTEGER
	private $notes; // VARCHAR(255)
	private $rating; // INTEGER
	private $guid; // VARCHAR(45)
	private $preferredrecbyid; // INTEGER
	private $initialtimestamp; // TIMESTAMP
	private $biography; // TEXT
	private $taxonomicgroups; // VARCHAR(900)
	private $collectionsat; // VARCHAR(900)
	private $curated; // BIT
	private $nototherwisespecified; // BIT
	private $datelastmodified; // TIMESTAMP
	private $lastmodifiedbyuid; // INTEGER
	private $type; // enum Individual, Team, Organization
	private $namestring; // TEXT
	private $prefix; // VARCHAR(32)
	private $suffix; // VARCHAR(32)
	private $living; // enum Y, N, ?
	private $yearofbirth; // INTEGER
	private $yearofdeath; // INTEGER
	private $yearofbirthmodifier; // VARCHAR(12)
	private $yearofdeathmodifier; // VARCHAR(12)
	private $mbox_sha1sum; // CHAR(40)
	private $uuid; // CHAR(43)

	private $dirty;
	private $loaded;
	private $error;

    public function errorMessage() {
      return $this->error;
    }

	// These constants hold the field names of the table in the database.
	const AGENTID      = 'agentid';
	const UUID              = 'uuid';
	const FAMILYNAME        = 'familyname';
	const FIRSTNAME         = 'firstname';
	const MIDDLENAME        = 'middlename';
	const STARTYEARACTIVE   = 'startyearactive';
	const ENDYEARACTIVE     = 'endyearactive';
	const NOTES             = 'notes';
	const RATING            = 'rating';
	const GUID              = 'guid';
	const PREFERREDRECBYID  = 'preferredrecbyid';
	const INITIALTIMESTAMP  = 'initialtimestamp';
	const BIOGRAPHY         = 'biography';
	const TAXONOMICGROUPS   = 'taxonomicgroups';
	const COLLECTIONSAT     = 'collectionsat';
	const CURATED           = 'curated';
	const NOTOTHERWISESPECIFIED = 'nototherwisespecified';
	const DATELASTMODIFIED  = 'datelastmodified';
	const LASTMODIFIEDBYUID = 'lastmodifiedbyuid';
	const TYPE              = 'type';
	const NAMESTRING        = 'namestring';
	const PREFIX            = 'prefix';
	const SUFFIX            = 'suffix';
	const LIVING            = 'living';
	const YEAROFBIRTH       = 'yearofbirth';
	const YEAROFDEATH       = 'yearofdeath';
	const YEAROFBIRTHMODIFIER = 'yearofbirthmodifier';
	const YEAROFDEATHMODIFIER  = 'yearofdeathmodifier';
	const MBOX_SHA1SUM      = 'mbox_sha1sum';
	// These constants hold the sizes the fields in this table in the database.
	const AGENTID_SIZE    = 11; //INTEGER
	const FAMILYNAME_SIZE      = 45; //45
	const FIRSTNAME_SIZE       = 45; //45
	const MIDDLENAME_SIZE      = 45; //45
	const STARTYEARACTIVE_SIZE = 11; //INTEGER
	const ENDYEARACTIVE_SIZE   = 11; //INTEGER
	const NOTES_SIZE           = 255; //255
	const RATING_SIZE          = 11; //INTEGER
	const GUID_SIZE            = 900; //900 may be a uuid with a resolvable IRI.
	const PREFERREDRECBYID_SIZE = 11; //INTEGER
	const INITIALTIMESTAMP_SIZE = 21; //TIMESTAMP
	const BIOGRAPHY_SIZE       = 65536; //TEXT
	const TAXONOMICGROUPS_SIZE = 900; //900
	const COLLECTIONSAT_SIZE   = 900; //900
	const CURATED_SIZE         = 1; //BIT
	const NOTOTHERWISESPECIFIED_SIZE = 1; //BIT
	const DATELASTMODIFIED_SIZE = 21; //TIMESTAMP
	const LASTMODIFIEDBYUID_SIZE = 11; //INTEGER
	const TYPE_SIZE            = 12; //12
	const NAMESTRING_SIZE      = 65536; //TEXT
	const PREFIX_SIZE          = 32; //32
	const SUFFIX_SIZE          = 32; //32
	const YEAROFBIRTH_SIZE     = 11; //INTEGER
	const YEAROFDEATH_SIZE     = 11; //INTEGER
	const YEAROFBIRTHMODIFIER_SIZE     = 12; //12
	const YEAROFDEATHMODIFIER_SIZE     = 12; //12
	const LIVING_SIZE          = 1;   // enum, 1 char
	const MBOX_SHA1SUM_SIZE    = 40; //40 sha1 hash
	const UUID_SIZE            = 43; //43 for a uuid


	public $LIVING_VALUES = array('Y', 'N', '?');
	public $TYPE_VALUES = array('Individual', 'Team', 'Organization');

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
		$this->agentid = NULL;
		$this->curated = 0;
		$this->living = 'N';
		$this->type = 'Individual';
	}

    /**
     * Given a string (corresponding to dwc:recordedBy containing a list of zero to many people
     * Find the first name in the list, and split it into first, middle, and last elements, assuming
     * that it is the name of a person without a prefix or suffix.
     *
     * @param inStr string containing a list of zero or more names of people.
     * @return an array which may contain elements first, middle, and last.
     */
	public static function parseLeadingNameInList($inStr){
        // Refactored into static method of Agent from private method OccurrenceCleaner.parseCollectorName);
		$name = array();
		$primaryArr = '';
		$primaryArr = explode(';',$inStr);
		$primaryArr = explode('&',$primaryArr[0]);
		$primaryArr = explode(' and ',$primaryArr[0]);
		$primaryArr = explode('|',$primaryArr[0]);   // Expected separator in dwc:recordedBy after TDWG 2014
		$lastNameArr = explode(',',$primaryArr[0]);
		if(count($lastNameArr) > 1){
			//formats: Last, F.I.; Last, First I.; Last, First Initial Last
			$name['last'] = $lastNameArr[0];
			if($pos = strpos($lastNameArr[1],' ')){
				$name['first'] = substr($lastNameArr[1],0,$pos);
				$name['middle'] = substr($lastNameArr[1],$pos);
			}
			elseif($pos = strpos($lastNameArr[1],'.')){
				$name['first'] = substr($lastNameArr[1],0,$pos);
				$name['middle'] = substr($lastNameArr[1],$pos);
			}
			else{
				$name['first'] = $lastNameArr[1];
			}
		}
		else{
			//Formats: F.I. Last; First I. Last; First Initial Last
			$tempArr = explode(' ',$lastNameArr[0]);
			$name['last'] = array_pop($tempArr);
			if($tempArr){
				$arrCnt = count($tempArr);
				if($arrCnt == 1){
					if(preg_match('/(\D+\.+)(\D+\.+)/',$tempArr[0],$m)){
						$name['first'] = $m[1];
						$name['middle'] = $m[2];
					}
					else{
						$name['first'] = $tempArr[0];
					}
				}
				elseif($arrCnt == 2){
					$name['first'] = $tempArr[0];
					$name['middle'] = $tempArr[1];
				}
				else{
					$name['first'] = implode(' ',$tempArr);
				}
			}
		}
		return $name;
	}

    /**
     * Obtain an assembled string representation of the agent's name.
     *
     * @return namestring for a team or organization, concatenated atomic name fields for an individual.
     */
    public function getAssembledName($showCurated=true) {
       if ($this->curated==1 && $showCurated) { $curated="*"; } else { $curated = ""; }
       if (strlen($this->namestring) > 0) {
          return trim($this->namestring . " $curated");
       } else {
          $am = new AgentManager();
          return trim($am->assembleNameBits($this->prefix,$this->firstname,$this->middlename,$this->familyname,$this->suffix,$this->name,'First Middle Last') . " $curated");
       }
    }

    public function getMinimalName($showCurated=true) {
       if ($this->curated==1 && $showCurated) { $curated="*"; } else { $curated = ""; }
       if (strlen($this->namestring) > 0) {
          return trim(substr($this->namestring,0,30). " $curated");
       } else {
          return trim($this->familyname . " $curated");
       }

    }

    /**
     * Given a start year collected and an end year collected, set
     * living, startyearactive, and endyearactive to plausible
     * values given the provided values.  For example, if start or
     * end year are too far back in time, set living to 'N'.
     */
    public function setPlausibleYearValues($startyear,$endyear) {
        $maxacceptedagerange = 50;
        $this->setLiving('?');
        if ($startyear>2000) { $this->setLiving('Y'); }
        if (($startyear!=null && $startyear<1880) || ($endyear!=null &&$endyear < 1910)) { $this->setLiving('N'); }
        if ($startyear!=null && $endyear!=null && (($endyear - $startyear)> $maxacceptedagerange))  {
           // range is too large, possible this represents more than one collector.
           $startyear=null;
           $endyear=null;
        }
        if ($startyear!=null) { $this->setstartyearactive($startyear); }
        if ($endyear!=null) { $this->setendyearactive($endyear); }
    }

	/**
	 * Given a value for recordedByID, load an agent record into the current Agent object.
	 * @param pk the value of the primary key or an array containing the key 'agentid' and the value of the primary key.
	 * @returns false on a failure, true on a successful load.
	 */
	public function load($pk) {
		$returnvalue = false;
		try {
			if (is_array($pk)) {
				$this->setagentid($pk[agentid]);
			} else { ;
			$this->setagentid($pk);
			};
		}
		catch (Exception $e) {
			throw new Exception($e->getMessage());
		}
		if($this->agentid != NULL) {

			$sql = 'SELECT agentid, familyname, firstname, middlename, startyearactive, endyearactive, notes, rating, guid, preferredrecbyid, initialtimestamp, biography, taxonomicgroups, collectionsat, curated, nototherwisespecified, datelastmodified, lastmodifiedbyuid, type, namestring, prefix, suffix, yearofbirth, yearofdeath, yearofbirthmodifier, yearofdeathmodifier, mbox_sha1sum, uuid, living FROM agents WHERE agentid = ? ';

			if ($statement = $this->conn->prepare($sql)) {
				$statement->bind_param("i", $this->agentid);
				$statement->execute();
				$statement->bind_result($this->agentid, $this->familyname, $this->firstname, $this->middlename, $this->startyearactive, $this->endyearactive, $this->notes, $this->rating, $this->guid, $this->preferredrecbyid, $this->initialtimestamp, $this->biography, $this->taxonomicgroups, $this->collectionsat, $this->curated, $this->nototherwisespecified, $this->datelastmodified, $this->lastmodifiedbyuid, $this->type, $this->namestring, $this->prefix, $this->suffix, $this->yearofbirth, $this->yearofdeath, $this->yearofbirthmodifier, $this->yearofdeathmodifier, $this->mbox_sha1sum, $this->uuid ,$this->living);
				$statement->fetch();
				$statement->close();
			} else {
               throw new Exception("Query Error: " . $this->conn->error );
            }
			$this->loaded = true;
			$this->dirty = false;
		} else {
			throw new Exception("Can't use load to on an already existing object, .");
		}
		return $returnvalue;
	}

	/**
	 * Locate a record with a uid matching the provided value, and load it into this agent instance.
	 *
	 * @param uuid the uuid for the agent to load, can be just the uuid, or can be prefixed with urn:uuid.
	 * @returns false on a failure, true on a successful load.
	 */
	public function loadByGUID($uuid) {
		// strip of a leading urn:uuid if present
		$uuid =  str_replace('urn:uuid','',$uuid);
		$id = null;
		// query to find the id of a record with this guid
		$sql = "select agentid from agents where uuid = ? ";
		if ($statement = $this->conn->prepare($sql)) {
			$statement->bind_param("s", $uuid);
			$statement->execute();
			$statement->bind_result($id);
            $statement->fetch();
			$statement->close();
		}
		if (strlen($id)>0) {
			return $this->load($id);
		} else {
			throw new Exception("Unable to find agents record with provided uuid [$uuid]");
		}
	}

    /**
     *  Clear name fields that are associated with a name of a type that wasn't
     *  selected (e.g. clear familyname for organizations and teams.
     */
    function emptyWrongTypeValues() {
       if ($this->type == 'Individual') {
          $this->namestring = '';
       } else {
          $this->familyname = '';
          $this->firstname = '';
          $this->middlename = '';
          $this->prefix = '';
          $this->suffix = '';
       }
       if ($this->type == 'Team') {
          $this->yearofbirth = '';
          $this->yearofbirthmodifier = '';
          $this->yearofdeath = '';
          $this->yearofdeathmodifier = '';
       }
    }

   /** Function save() will either save the current record or insert a new record.
    * Inserts new record if the primary key field in this table is null for this
    * instance of this object.
    * Otherwise updates the record identified by the primary key value of the
    * current instance.
    * @return true on success, false on failure
    */
   public function save() {
        global $SYMB_UID;
        $this->emptyWrongTypeValues();
        // sanity check, make sure that this is not an attempt to save an agent with no name.
        if (strlen(trim($this->getnamestring().$this->getfamilyname().$this->getfirstname().$this->getmiddlename()))==0) {
            $this->error = "Error: Unable to save.  Agent lacks a name.";
            return false;
        }
       if (strlen($this->yearofdeath)>0 && $this->living=='Y' ) {
            $this->error = "Error: Unable to save.  Agent has a year of death and is also living.";
            return false;
       }
        $returnvalue = false;
        $this->setlastmodifiedbyuid($SYMB_UID);
        // Test to see if this is an insert or update.
        if ($this->agentid!= NULL) {
            $sql  = 'UPDATE  agents SET ';
            $isInsert = false;
            $sql .=  "familyname = ? ";
            $sql .=  ", firstname = ? ";
            $sql .=  ", middlename = ? ";
            $sql .=  ", startyearactive = ? ";
            $sql .=  ", endyearactive = ? ";
            $sql .=  ", notes = ? ";
            $sql .=  ", rating = ? ";
            $sql .=  ", guid = ? ";
            if (strlen($this->preferredrecbyid)==0) {
               $sql .=  ", preferredrecbyid = null ";
            } else {
               $sql .=  ", preferredrecbyid = ? ";
            }
            $sql .=  ", biography = ? ";
            $sql .=  ", taxonomicgroups = ? ";
            $sql .=  ", collectionsat = ? ";
            $sql .=  ", curated = ? ";
            $sql .=  ", nototherwisespecified = ? ";
            $sql .=  ", datelastmodified = now() ";
            $sql .=  ", lastmodifiedbyuid = ? ";
            $sql .=  ", type = ? ";
            $sql .=  ", namestring = ? ";
            $sql .=  ", prefix = ? ";
            $sql .=  ", suffix = ? ";
            $sql .=  ", yearofbirth = ? ";
            $sql .=  ", yearofdeath = ? ";
            $sql .=  ", yearofbirthmodifier = ? ";
            $sql .=  ", yearofdeathmodifier = ? ";
            $sql .=  ", mbox_sha1sum = ? ";
            $sql .=  ", uuid = ? ";
            $sql .=  ", living = ? ";

            $sql .= "  WHERE agentid = ? ";
        } else {
            $sql  = 'INSERT INTO agents ';
            $isInsert = true;
            $sql .= '( familyname ,  firstname ,  middlename ,  startyearactive ,  endyearactive ,  notes ,  rating ,  guid ,  preferredrecbyid ,  biography ,  taxonomicgroups ,  collectionsat ,  curated ,  nototherwisespecified ,  datelastmodified ,  lastmodifiedbyuid ,  type ,  namestring ,  prefix ,  suffix ,  yearofbirth ,  yearofdeath , yearofbirthmodifier, yearofdeathmodifier,  mbox_sha1sum ,  uuid, living ) VALUES (';
            $sql .=  "  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? "; // guid
            if (strlen($this->preferredrecbyid)==0) {
               $sql .=  " ,  null ";
            } else {
               $sql .=  " ,  ? ";
            }
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  now() ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .= ')';
            if (strlen($this->uuid)==0) {
               $uf = new UuidFactory();
               $this->setuuid($uf->getUuidV4());
            }
        }
        if ($statement = $this->conn->prepare($sql)) {
           // update
           if ($this->agentid!= NULL ) {
              if (strlen($this->preferredrecbyid)==0) {
                  $statement->bind_param("sssiisissssiiissssiisssssi", $this->familyname , $this->firstname , $this->middlename , $this->startyearactive , $this->endyearactive , $this->notes , $this->rating , $this->guid , $this->biography , $this->taxonomicgroups , $this->collectionsat , $this->curated , $this->nototherwisespecified , $this->lastmodifiedbyuid , $this->type , $this->namestring , $this->prefix , $this->suffix , $this->yearofbirth , $this->yearofdeath , $this->yearofbirthmodifier, $this->yearofdeathmodifier , $this->mbox_sha1sum , $this->uuid, $this->living , $this->agentid );
               } else {
                  $statement->bind_param("sssiisisisssiiissssiisssssi", $this->familyname , $this->firstname , $this->middlename , $this->startyearactive , $this->endyearactive , $this->notes , $this->rating , $this->guid , $this->preferredrecbyid , $this->biography , $this->taxonomicgroups , $this->collectionsat , $this->curated , $this->nototherwisespecified , $this->lastmodifiedbyuid , $this->type , $this->namestring , $this->prefix , $this->suffix , $this->yearofbirth , $this->yearofdeath , $this->yearofbirthmodifier, $this->yearofdeathmodifier , $this->mbox_sha1sum , $this->uuid, $this->living , $this->agentid );
              }
           } else {
              // insert
              if (strlen($this->preferredrecbyid)==0) {
                  $statement->bind_param("sssiisissssiiissssiisssss", $this->familyname , $this->firstname , $this->middlename , $this->startyearactive , $this->endyearactive , $this->notes , $this->rating , $this->guid , $this->biography , $this->taxonomicgroups , $this->collectionsat , $this->curated , $this->nototherwisespecified , $this->lastmodifiedbyuid , $this->type , $this->namestring , $this->prefix , $this->suffix , $this->yearofbirth , $this->yearofdeath , $this->yearofbirthmodifier, $this->yearofdeathmodifier, $this->mbox_sha1sum , $this->uuid, $this->living );
               } else {
                  $statement->bind_param("sssiisisisssiiissssiisssss", $this->familyname , $this->firstname , $this->middlename , $this->startyearactive , $this->endyearactive , $this->notes , $this->rating , $this->guid , $this->preferredrecbyid , $this->biography , $this->taxonomicgroups , $this->collectionsat , $this->curated , $this->nototherwisespecified , $this->lastmodifiedbyuid , $this->type , $this->namestring , $this->prefix , $this->suffix , $this->yearofbirth , $this->yearofdeath , $this->yearofbirthmodifier, $this->yearofdeathmodifier, $this->mbox_sha1sum , $this->uuid, $this->living );
               }
           }
           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $this->error = $statement->error;
           } else {
               if ($this->agentid==NULL ) {
                  // obtain the primary key value set bh the insert statement.
                  $this->setagentid($this->conn->insert_id);
               }
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
        }
        if ($this->error=='') {
            $returnvalue = true;
        };

        $this->loaded = true;
        return $returnvalue;
    }

   public function delete() {
        $returnvalue = false;
        if($this->agentid != NULL) {
           $preparedsql = 'SELECT agentid FROM agents WHERE agentid = ?  ' ;
           if ($statement = $this->conn->prepare($preparedsql)) {
               $statement->bind_param("i", $this->agentid);
               $statement->execute();
               $statement->store_result();
               if ($statement->num_rows()==1) {
                    $sql = 'DELETE FROM agents WHERE agentid = ?  ';
                    if ($stmt_delete = $this->conn->prepare($sql)) {
                       $stmt_delete->bind_param("i", $this->agentid);
                       if ($stmt_delete->execute()) {
                           $returnvalue = true;
                       } else {
                           $this->error = mysqli_error($this->conn);
                       }
                       $stmt_delete->close();
                    }
               } else {
                   $this->error = mysqli_error($this->conn);
               }
               $statement->close();
           } else {
                $this->error = mysqli_error($this->conn);
           }

           $this->loaded = true;
           // record was deleted, so set PK to null
           $this->agentid = NULL;
        } else {
           throw new Exception('Unable to identify which record to delete, primary key is not set');
        }
        return $returnvalue;
    }

    public function findIdByName($name) {
        $returnvalue = false;
        if($this->agentid != NULL) {
           $preparedsql = 'SELECT agentid FROM agents WHERE name = ?  ' ;
           if ($statement = $this->conn->prepare($preparedsql)) {
               $statement->bind_param("s", $name);
               $statement->execute();
               $statement->bind_result($id);
               $statement->store_result();
               if ($statement->fetch()) {
                   $returnvalue = $id;
               }
           }
        }
        return $returnvalue;
    }
    /**
     * Obtain the agents which are bad duplicates of the current agent.
     *
     * @return an array, keyed by agentid of loaded agent objects that
     *   are the bad duplicates of the current agent.
     */
    function getBadDuplicatesForAgent() {
        $result = array();;
		if ($this->agentid!=null) {
           $sql = "select agentid from agents where preferredrecbyid = ? ";
           if ($statement = $this->conn->prepare($sql)) {
               $statement->bind_param("i", $this->agentid);
               $statement->execute();
               $statement->bind_result($dupagentid);
               $statement->store_result();
               while ($statement->fetch()) {
                   $dupagent = new Agent();
                   $dupagent->load($dupagentid);
                   $result[$dupagentid] = $dupagent;
               }
           } else {
              throw new Exception('Unable to load duplicate agents:' . mysqli_error($this->conn));
           }
        }
        return $result;
    }


	/********** Field length aware get/set methods ***********/

	/*agentid*/
	public function getagentid() {
		if ($this->agentid==null) {
			return null;
		} else { ;
		return trim($this->agentid);
		}
	}
	public function setagentid($agentid) {
		if (strlen(preg_replace('/[^0-9]/','',$agentid)) > Agent::AGENTID_SIZE) {
			throw new Exception('Value has too many digits for the field length.');
		}
		$agentid = trim($agentid);
		if (!ctype_digit(strval($agentid)) && trim(strval($agentid))!='' ) {
			throw new Exception("Value must be an integer");
		}
		$this->agentid = $agentid;
		$this->dirty = true;
	}

   /*familyname*/
   public function getfamilyname() {
       if ($this->familyname==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->familyname));
       }
   }
   public function setfamilyname($familyname) {
       if (strlen($familyname) > Agent::FAMILYNAME_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->familyname = $this->cleanInStr($familyname);
       $this->dirty = true;
   }
   /*firstname*/
   public function getfirstname() {
       if ($this->firstname==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->firstname));
       }
   }
   public function setfirstname($firstname) {
       if (strlen($firstname) > Agent::FIRSTNAME_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->firstname = $this->cleanInStr($firstname);
       $this->dirty = true;
   }
   /*middlename*/
   public function getmiddlename() {
       if ($this->middlename==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->middlename));
       }
   }
   public function setmiddlename($middlename) {
       if (strlen($middlename) > Agent::MIDDLENAME_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->middlename = $this->cleanInStr($middlename);
       $this->dirty = true;
   }
   /*startyearactive*/
   public function getstartyearactive() {
       if ($this->startyearactive==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->startyearactive));
       }
   }
   public function setstartyearactive($startyearactive) {
       if (strlen(preg_replace('/[^0-9]/','',$startyearactive)) > Agent::STARTYEARACTIVE_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $startyearactive = trim($startyearactive);
       if (!ctype_digit(strval($startyearactive)) && trim(strval($startyearactive))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->startyearactive = $this->cleanInStr($startyearactive);
       $this->dirty = true;
   }
   /*endyearactive*/
   public function getendyearactive() {
       if ($this->endyearactive==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->endyearactive));
       }
   }
   public function setendyearactive($endyearactive) {
       if (strlen(preg_replace('/[^0-9]/','',$endyearactive)) > Agent::ENDYEARACTIVE_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $endyearactive = trim($endyearactive);
       if (!ctype_digit(strval($endyearactive)) && trim(strval($endyearactive))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->endyearactive = $this->cleanInStr($endyearactive);
       $this->dirty = true;
   }
   /*notes*/
   public function getnotes() {
       if ($this->notes==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->notes));
       }
   }
   public function setnotes($notes) {
       if (strlen($notes) > Agent::NOTES_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->notes = $this->cleanInStr($notes);
       $this->dirty = true;
   }
   /*rating*/
   public function getrating() {
       if ($this->rating==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->rating));
       }
   }
   public function setrating($rating) {
       if (strlen(preg_replace('/[^0-9]/','',$rating)) > Agent::RATING_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $rating = trim($rating);
       if (!ctype_digit(strval($rating)) && trim(strval($rating))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->rating = $this->cleanInStr($rating);
       $this->dirty = true;
   }
   /*guid*/
   public function getguid() {
       if ($this->guid==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->guid));
       }
   }
   public function setguid($guid) {
       if (strlen($guid) > Agent::GUID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->guid = $this->cleanInStr($guid);
       $this->dirty = true;
   }
   /*preferredrecbyid*/
   public function getpreferredrecbyid() {
       if ($this->preferredrecbyid==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->preferredrecbyid));
       }
   }
   public function setpreferredrecbyid($preferredrecbyid) {
       if (strlen(preg_replace('/[^0-9]/','',$preferredrecbyid)) > Agent::PREFERREDRECBYID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $preferredrecbyid = trim($preferredrecbyid);
       if (!ctype_digit(strval($preferredrecbyid)) && trim(strval($preferredrecbyid))!='' ) {
             throw new Exception("Value must be an integer");
       }
       if (strlen($preferredrecbyid)>0 && $preferredrecbyid==$this->agentid) {
           throw new Exception('Record cannot be set to be a bad duplicate of itself.');
       }
       $this->preferredrecbyid = $this->cleanInStr($preferredrecbyid);
       $this->dirty = true;
   }
   /*initialtimestamp*/
   public function getinitialtimestamp() {
       if ($this->initialtimestamp==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->initialtimestamp));
       }
   }
   public function setinitialtimestamp($initialtimestamp) {
       if (strlen($initialtimestamp) > Agent::INITIALTIMESTAMP_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->initialtimestamp = $this->cleanInStr($initialtimestamp);
       $this->dirty = true;
   }
   /*biography*/
   public function getbiography() {
       if ($this->biography==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->biography));
       }
   }
   public function setbiography($biography) {
       if (strlen($biography) > Agent::BIOGRAPHY_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->biography = $this->cleanInStr($biography);
       $this->dirty = true;
   }
   /*taxonomicgroups*/
   public function gettaxonomicgroups() {
       if ($this->taxonomicgroups==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->taxonomicgroups));
       }
   }
   public function settaxonomicgroups($taxonomicgroups) {
       if (strlen($taxonomicgroups) > Agent::TAXONOMICGROUPS_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->taxonomicgroups = $this->cleanInStr($taxonomicgroups);
       $this->dirty = true;
   }
   /*collectionsat*/
   public function getcollectionsat() {
       if ($this->collectionsat==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->collectionsat));
       }
   }
   public function setcollectionsat($collectionsat) {
       if (strlen($collectionsat) > Agent::COLLECTIONSAT_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->collectionsat = $this->cleanInStr($collectionsat);
       $this->dirty = true;
   }
   /*curated*/
   public function getcurated() {
       if ($this->curated==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->curated));
       }
   }
   public function setcurated($curated) {
       if (strlen($curated) > Agent::CURATED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->curated = $this->cleanInStr($curated);
       $this->dirty = true;
   }
   /*living*/
   public function getliving() {
       if ($this->living==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->living));
       }
   }
   public function setliving($living) {
       if (!in_array($living, $this->LIVING_VALUES)) {
           throw new Exception('Value must be one of ' . implode(",",$this->LIVING_VALUES) . '.');
       }
       if (strlen($living) > Agent::LIVING_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->living = $this->cleanInStr($living);
       $this->dirty = true;
   }
   /*nototherwisespecified*/
   public function getnototherwisespecified() {
       if ($this->nototherwisespecified==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->nototherwisespecified));
       }
   }
   public function setnototherwisespecified($nototherwisespecified) {
       if (strlen($nototherwisespecified) > Agent::NOTOTHERWISESPECIFIED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->nototherwisespecified = $this->cleanInStr($nototherwisespecified);
       $this->dirty = true;
   }
   /*datelastmodified*/
   public function getdatelastmodified() {
       if ($this->datelastmodified==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->datelastmodified));
       }
   }
   public function setdatelastmodified($datelastmodified) {
       if (strlen($datelastmodified) > Agent::DATELASTMODIFIED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->datelastmodified = $this->cleanInStr($datelastmodified);
       $this->dirty = true;
   }
   /*lastmodifiedbyuid*/
   public function getlastmodifiedbyuid() {
       if ($this->lastmodifiedbyuid==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->lastmodifiedbyuid));
       }
   }
   public function setlastmodifiedbyuid($lastmodifiedbyuid) {
       if (strlen(preg_replace('/[^0-9]/','',$lastmodifiedbyuid)) > Agent::LASTMODIFIEDBYUID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $lastmodifiedbyuid = trim($lastmodifiedbyuid);
       if (!ctype_digit(strval($lastmodifiedbyuid)) && trim(strval($lastmodifiedbyuid))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->lastmodifiedbyuid = $this->cleanInStr($lastmodifiedbyuid);
       $this->dirty = true;
   }
   /*type*/
   public function gettype() {
       if ($this->type==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->type));
       }
   }
   public function settype($type) {
       if (strlen($type) > Agent::TYPE_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->type = $this->cleanInStr($type);
       $this->dirty = true;
   }
   /*namestring*/
   public function getnamestring() {
       if ($this->namestring==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->namestring));
       }
   }
   public function setnamestring($namestring) {
       if (strlen($namestring) > Agent::NAMESTRING_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->namestring = $this->cleanInStr($namestring);
       $this->dirty = true;
   }
   /*prefix*/
   public function getprefix() {
       if ($this->prefix==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->prefix));
       }
   }
   public function setprefix($prefix) {
       if (strlen($prefix) > Agent::PREFIX_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->prefix = $this->cleanInStr($prefix);
       $this->dirty = true;
   }
   /*suffix*/
   public function getsuffix() {
       if ($this->suffix==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->suffix));
       }
   }
   public function setsuffix($suffix) {
       if (strlen($suffix) > Agent::SUFFIX_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->suffix = $this->cleanInStr($suffix);
       $this->dirty = true;
   }
   /*yearofbirth*/
   public function getyearofbirth() {
       if ($this->yearofbirth==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->yearofbirth));
       }
   }
   public function setyearofbirth($yearofbirth) {
       if (strlen(preg_replace('/[^0-9]/','',$yearofbirth)) > Agent::YEAROFBIRTH_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $yearofbirth = trim($yearofbirth);
       if (!ctype_digit(strval($yearofbirth)) && trim(strval($yearofbirth))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->yearofbirth = $this->cleanInStr($yearofbirth);
       $this->dirty = true;
   }
   /*yearofdeath*/
   public function getyearofdeath() {
       if ($this->yearofdeath==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->yearofdeath));
       }
   }
   public function setyearofdeath($yearofdeath) {
       if (strlen(preg_replace('/[^0-9]/','',$yearofdeath)) > Agent::YEAROFDEATH_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $yearofdeath = trim($yearofdeath);
       if (!ctype_digit(strval($yearofdeath)) && trim(strval($yearofdeath))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->yearofdeath = $this->cleanInStr($yearofdeath);
       $this->dirty = true;
   }
   /*yearofbirthmodifier*/
   public function getyearofbirthmodifier() {
       if ($this->yearofbirthmodifier==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->yearofbirthmodifier));
       }
   }
   public function setyearofbirthmodifier($yearofbirthmodifier) {
       if (strlen(preg_replace('/[^0-9]/','',$yearofbirthmodifier)) > Agent::YEAROFBIRTHMODIFIER_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $this->yearofbirthmodifier = $this->cleanInStr($yearofbirthmodifier);
       $this->dirty = true;
   }
   /*yearofdeathmodifier*/
   public function getyearofdeathmodifier() {
       if ($this->yearofdeathmodifier==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->yearofdeathmodifier));
       }
   }
   public function setyearofdeathmodifier($yearofdeathmodifier) {
       if (strlen(preg_replace('/[^0-9]/','',$yearofdeathmodifier)) > Agent::YEAROFDEATHMODIFIER_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $this->yearofdeathmodifier = $this->cleanInStr($yearofdeathmodifier);
       $this->dirty = true;
   }
   /*mbox_sha1sum*/
   public function getmbox_sha1sum() {
       if ($this->mbox_sha1sum==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->mbox_sha1sum));
       }
   }
   public function setmbox_sha1sum($mbox_sha1sum) {
       if (strlen($mbox_sha1sum) > Agent::MBOX_SHA1SUM_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->mbox_sha1sum = $this->cleanInStr($mbox_sha1sum);
       $this->dirty = true;
   }
   /*uuid*/
   public function getuuid() {
       if ($this->uuid==null) {
          return null;
       } else { ;
          return trim($this->cleanOutStr($this->uuid));
       }
   }
   public function setuuid($uuid) {
       if (!UuidFactory::is_valid(str_replace("urn:uuid:","",$uuid))) {
           throw new Exception("Not a valid uuid [$uuid].");
       }
       if (strlen($uuid) > Agent::UUID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->uuid = $this->cleanInStr($uuid);
       $this->dirty = true;
   }


    protected function cleanOutStr($str){
        $newStr = str_replace('"',"&quot;",$str);
        $newStr = str_replace("'","&apos;",$newStr);
        return $newStr;
    }

    protected function cleanInStr($str){
        $newStr = trim($str);
        $newStr = preg_replace('/\s\s+/', ' ',$newStr);
        //$newStr = $this->conn->real_escape_string($newStr);
        return $newStr;
    }

}


// **************************************************************************************
// ***************** Supporting View Classes ********************************************
// **************************************************************************************

class AgentView {
   var $model = null;
   public function setModel($aModel) {
       $this->model = $aModel;
   }

   // @param $includeRelated default true shows rows from other tables through foreign key relationships.
   // @param $editLinkURL default '' allows adding a link to show this record in an editing form.
   public function getDetailsView($includeRelated=true, $editLinkURL='') {
       global $CLIENT_ROOT;
       $editLinkURL=trim($editLinkURL);
       $model = $this->model;
       $returnvalue  = "<h3>".$model->getAssembledName()."</h3>\n";
       $returnvalue .= '<ul>';
       $primarykeys = array("agentid");
       if ($editLinkURL!='') {
          if (!preg_match('/\&$/',$editLinkURL)) { $editLinkURL .= '&'; }
          $nullpk = false;
          foreach ($primarykeys as $primarykey) {
              // Add fieldname=value pairs for primary key(s) to editLinkURL.
              $editLinkURL .= urlencode($primarykey) . '=' . urlencode($model->keyGet($primarykey));
              if ($model->keyGet($primarykey)=='') { $nullpk = true; }
          }
          if (!$nullpk) { $returnvalue .= "<li>Agent <a href='$editLinkURL'>Edit</a></li>\n";  }
       }
       //$returnvalue .= "<li>".Agent::AGENTID.": ".$model->getagentid()."</li>\n";
       $returnvalue .= "<li>".Agent::TYPE.": ".$model->gettype()."</li>\n";
       $returnvalue .= "<li>".Agent::CURATED.": ".$model->getcurated()."</li>\n";
       $returnvalue .= "<li>".Agent::NOTOTHERWISESPECIFIED.": ".$model->getnototherwisespecified()."</li>\n";
       if ($model->gettype()=='Individual') {
          $returnvalue .= "<li>".Agent::PREFIX.": ".$model->getprefix()."</li>\n";
          $returnvalue .= "<li>".Agent::FIRSTNAME.": ".$model->getfirstname()."</li>\n";
          $returnvalue .= "<li>".Agent::MIDDLENAME.": ".$model->getmiddlename()."</li>\n";
          $returnvalue .= "<li>".Agent::FAMILYNAME.": ".$model->getfamilyname()."</li>\n";
          $returnvalue .= "<li>".Agent::SUFFIX.": ".$model->getsuffix()."</li>\n";
       } else {
          $returnvalue .= "<li>".Agent::NAMESTRING.": ".$model->getnamestring()."</li>\n";
       }
       $returnvalue .= "<li>".Agent::YEAROFBIRTH.": ".$model->getyearofbirth()."</li>\n";
       $returnvalue .= "<li>".Agent::YEAROFBIRTHMODIFIER.": ".$model->getyearofbirthmodifier()."</li>\n";
       $returnvalue .= "<li>".Agent::YEAROFDEATH.": ".$model->getyearofdeath()."</li>\n";
       $returnvalue .= "<li>".Agent::YEAROFDEATHMODIFIER.": ".$model->getyearofdeathmodifier()."</li>\n";
       $returnvalue .= "<li>".Agent::LIVING.": ".$model->getliving()."</li>\n";
       $returnvalue .= "<li>".Agent::STARTYEARACTIVE.": ".$model->getstartyearactive()."</li>\n";
       $returnvalue .= "<li>".Agent::ENDYEARACTIVE.": ".$model->getendyearactive()."</li>\n";
       $returnvalue .= "<li>".Agent::NOTES.": ".$model->getnotes()."</li>\n";
       $returnvalue .= "<li>".Agent::RATING.": ".$model->getrating()."</li>\n";
       $returnvalue .= "<li>".Agent::GUID.": ".$model->getguid()."</li>\n";
       $returnvalue .= "<li>".Agent::INITIALTIMESTAMP.": ".$model->getinitialtimestamp()."</li>\n";
       $returnvalue .= "<li>".Agent::BIOGRAPHY.": ".$model->getbiography()."</li>\n";
       $returnvalue .= "<li>".Agent::TAXONOMICGROUPS.": ".$model->gettaxonomicgroups()."</li>\n";
       $returnvalue .= "<li>".Agent::COLLECTIONSAT.": ".$model->getcollectionsat()."</li>\n";
       $returnvalue .= "<li>".Agent::MBOX_SHA1SUM.": ".$model->getmbox_sha1sum()."</li>\n";
       $returnvalue .= "<li>".Agent::UUID.": <a href='$CLIENT_ROOT/agents/agent.php?uuid=" . htmlspecialchars($model->getuuid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>" . htmlspecialchars($model->getuuid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a></li>\n";
       $returnvalue .= "<li>".Agent::DATELASTMODIFIED.": ".$model->getdatelastmodified()."</li>\n";
       $returnvalue .= "<li>".Agent::LASTMODIFIEDBYUID.": ".$model->getlastmodifiedbyuid()."</li>\n";
       $returnvalue .= "<div id='statusDiv'></div>";
       $returnvalue .= "<li><h3>Names</h3></li>";
       $am = new AgentManager();
       $returnvalue .= $am->getAgentNamesForAgent($model->getagentid());
       $returnvalue .= $am->getTeamMembersForAgent($model->getagentid());
       if ($includeRelated) {
           $t_Agent = new Agent();
           $t_AgentView = new AgentView();
           $t_AgentView->setModel($t_Agent);
           if ($model->getpreferredrecbyid() != '') {
               $dupof = "";
               $dup = new Agent();
               $dupid = $model->getpreferredrecbyid();
               $dup->load($model->getpreferredrecbyid());
               $dupof = $dup->getAssembledName(TRUE);
               $returnvalue .= "<li><h3>Bad Duplicate Of</h3> <a href='$CLIENT_ROOT/agents/agent.php?agentid=$dupid'>$dupof</a></li>";
           }
           $returnvalue .= $am->getBadDuplicatesForAgent($model->getagentid());
           $returnvalue .= $am->getNumberPatternsForAgent($model->getagentid());
           $returnvalue .= $am->getLinksForAgent($model->getagentid());
           $returnvalue .= $am->getRelationsForAgent($model->getagentid());
           $returnvalue .= $am->getTeamMembershipForAgent($model->getagentid());
       }
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   /**
    * Obtain an RDF representation of the agent serialized as Turtle
    *
    * @return a string containing turtle.
    */
   public function getAsTurtle() {
       $model = $this->model;
       $returnvalue  = "@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .\n";
       $returnvalue .= "@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .\n";
       $returnvalue .= "@prefix foaf: <http://xmlns.com/foaf/0.1/> .\n";
       $returnvalue .= "@prefix bio: <http://purl.org/vocab/bio/0.1/> .\n";
       $returnvalue .= "@prefix dc: <http://purl.org/dc/elements/1.1/> . \n";
       $returnvalue .= "<urn:uuid:".$model->getUuid().">\n";
       // Note: when adding fields, make sure to separate with ; and terminate with .
       //    Thus, add new elements prior to Terminator line.
       $l = new agentlinks();
       $links = $l->loadArrayByagentid($this->model->getagentid());
       foreach ($links as $link) {
          if ($link->getisprimarytopicof()==1) {
                $returnvalue .= '   foaf:isPrimaryTopicOf <'. $link->getlink() .'> ;' . "\n";
          }
          if ($link->gettype()=='homepage') {
                $returnvalue .= '   foaf:homepage <'. $link->getlink() .'> ;' . "\n";
          }
          if ($link->gettype()=='image') {
                $returnvalue .= '   foaf:depiction <'. $link->getlink() .'> ;' . "\n";
          }
       }
       if ($model->gettype()=='Individual') {
          $returnvalue .= "   a foaf:Person ; \n";
          if (strlen($model->getbiography()) > 0) {
             $returnvalue .= '   bio:biography "'. $model->getbiography().'" ;'."\n";
          }
          if (strlen($model->getyearofbirth()) > 0 && $model->getliving()=='N') {
             $returnvalue .= '   bio:birth [ '."\n";
             $returnvalue .= '      dc:date "'. $model->getyearofbirth().'" ] ;'." \n";
          }
          if (strlen($model->getyearofdeath()) > 0 && $model->getliving()=='N') {
             $returnvalue .= '   bio:death [ '."\n";
             $returnvalue .= '      dc:date "'. $model->getyearofdeath().'" ] ;'." \n";
          }
          $returnvalue .= '   foaf:name "'. $model->getAssembledName().'" .'."\n";  // ** Terminator
       } elseif ($model->gettype()=='Team') {
          $returnvalue .= "   a foaf:Group ; \n";
          // Note: bio:biography has domain foaf:Person
          $returnvalue .= '   foaf:name "'. $model->getAssembledName().'" .'."\n";  // ** Terminator
       } elseif ($model->gettype()=='Organization') {
          $returnvalue .= "   a foaf:Organization ; \n";
          // Note: bio:biography has domain foaf:Person
          $returnvalue .= '   foaf:name "'. $model->getAssembledName().'" .'."\n";  // ** Terminator
       } else {
          $returnvalue .= "   a foaf:Agent ; \n";
          // Note: bio:biography has domain foaf:Person
          $returnvalue .= '   foaf:name "'. $model->getAssembledName().'" .'."\n";  // ** Terminator
       }

       return  $returnvalue;
   }

   public function getAsRdfXml() {
      $returnvalue  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
      $returnvalue .= "<rdf:RDF \n";
      $returnvalue .= "  xmlns:foaf=\"http://xmlns.com/foaf/0.1/\"\n";
      $returnvalue .= "  xmlns:bio=\"http://purl.org/vocab/bio/0.1/\"\n";
      $returnvalue .= "  xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\"\n";
      $returnvalue .= ">\n";
      $l = new agentlinks();
      $links = $l->loadArrayByagentid($this->model->getagentid());
      $linklist = "";
      foreach ($links as $link) {
          if ($link->getisprimarytopicof()==1) {
                $linklist .= '    <foaf:isPrimaryTopicOf rdf:resource="'. $link->getlink() .'" />' . "\n";
          }
          if ($link->gettype()=='homepage') {
                $linklist .= '    <foaf:homepage rdf:resource="'. $link->getlink() .'" />' . "\n";
          }
          if ($link->gettype()=='image') {
                $linklist .= '    <foaf:depiction rdf:resource="'. $link->getlink() .'" />' . "\n";
          }
      }
      if ($this->model->gettype()=='Individual') {
         $returnvalue .= ' <foaf:Person rdf:about="urn:uuid:' . $this->model->getUuid() .'">' . "\n";
         $returnvalue .= "    <foaf:name>". $this->model->getAssembledName() ."</foaf:name>\n";
         if (strlen($this->model->getbiography()) > 0) {
            $returnvalue .= "    <bio:biography>". $this->model->getbiography() ."</bio:biography>\n";
         }
         $returnvalue .= $linklist;
         $returnvalue .= "  </foaf:Person>\n";
      } elseif ($this->model->gettype()=='Team') {
         $returnvalue .= ' <foaf:Group rdf:about="urn:uuid:' . $this->model->getUuid() .'">' . "\n";
         $returnvalue .= "    <foaf:name>". $this->model->getAssembledName() ."</foaf:name>\n";
         $returnvalue .= "  </foaf:Group>\n";
      } elseif ($this->model->gettype()=='Organization') {
         $returnvalue .= ' <foaf:Organization rdf:about="urn:uuid:' . $this->model->getUuid() .'">' . "\n";
         $returnvalue .= "    <foaf:name>". $this->model->getAssembledName() ."</foaf:name>\n";
         $returnvalue .= $linklist;
         $returnvalue .= "  </foaf:Agent>\n";
      } else {
         $returnvalue .= ' <foaf:Organization rdf:about="urn:uuid:' . $this->model->getUuid() .'">' . "\n";
         $returnvalue .= "    <foaf:name>". $this->model->getAssembledName() ."</foaf:name>\n";
         $returnvalue .= $linklist;
         $returnvalue .= "  </foaf:Agent>\n";
      }
      $returnvalue .= "</rdf:RDF>\n";
      return  $returnvalue;
   }
   public function getJSON() {
       $returnvalue = '{ ';
       $model = $this->model;
       $returnvalue .= '"'.Agent::AGENTID.': "'.$model->getagentid().'",';
       $returnvalue .= '"'.Agent::FAMILYNAME.': "'.$model->getfamilyname().'",';
       $returnvalue .= '"'.Agent::FIRSTNAME.': "'.$model->getfirstname().'",';
       $returnvalue .= '"'.Agent::MIDDLENAME.': "'.$model->getmiddlename().'",';
       $returnvalue .= '"'.Agent::STARTYEARACTIVE.': "'.$model->getstartyearactive().'",';
       $returnvalue .= '"'.Agent::ENDYEARACTIVE.': "'.$model->getendyearactive().'",';
       $returnvalue .= '"'.Agent::NOTES.': "'.$model->getnotes().'",';
       $returnvalue .= '"'.Agent::RATING.': "'.$model->getrating().'",';
       $returnvalue .= '"'.Agent::GUID.': "'.$model->getguid().'",';
       $returnvalue .= '"'.Agent::PREFERREDRECBYID.': "'.$model->getpreferredrecbyid().'",';
       $returnvalue .= '"'.Agent::INITIALTIMESTAMP.': "'.$model->getinitialtimestamp().'",';
       $returnvalue .= '"'.Agent::BIOGRAPHY.': "'.$model->getbiography().'",';
       $returnvalue .= '"'.Agent::TAXONOMICGROUPS.': "'.$model->gettaxonomicgroups().'",';
       $returnvalue .= '"'.Agent::COLLECTIONSAT.': "'.$model->getcollectionsat().'",';
       $returnvalue .= '"'.Agent::CURATED.': "'.$model->getcurated().'",';
       $returnvalue .= '"'.Agent::NOTOTHERWISESPECIFIED.': "'.$model->getnototherwisespecified().'",';
       $returnvalue .= '"'.Agent::DATELASTMODIFIED.': "'.$model->getdatelastmodified().'",';
       $returnvalue .= '"'.Agent::LASTMODIFIEDBYUID.': "'.$model->getlastmodifiedbyuid().'",';
       $returnvalue .= '"'.Agent::LIVING.': "'.$model->getliving().'",';
       $returnvalue .= '"'.Agent::TYPE.': "'.$model->gettype().'",';
       $returnvalue .= '"'.Agent::NAMESTRING.': "'.$model->getnamestring().'",';
       $returnvalue .= '"'.Agent::PREFIX.': "'.$model->getprefix().'",';
       $returnvalue .= '"'.Agent::SUFFIX.': "'.$model->getsuffix().'",';
       $returnvalue .= '"'.Agent::YEAROFBIRTH.': "'.$model->getyearofbirth().'",';
       $returnvalue .= '"'.Agent::YEAROFDEATH.': "'.$model->getyearofdeath().'",';
       $returnvalue .= '"'.Agent::YEAROFBIRTHMODIFIER.': "'.$model->getyearofbirthmodifier().'",';
       $returnvalue .= '"'.Agent::YEAROFDEATHMODIFIER.': "'.$model->getyearofdeathmodifier().'",';
       $returnvalue .= '"'.Agent::MBOX_SHA1SUM.': "'.$model->getmbox_sha1sum().'",';
       $returnvalue .= '"'.Agent::UUID.': "'.$model->getuuid().'" }';
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getTableRowView() {
       $returnvalue = '<tr>';
       $model = $this->model;
       $returnvalue .= "<td>".$model->getagentid()."</td>\n";
       $returnvalue .= "<td>".$model->getfamilyname()."</td>\n";
       $returnvalue .= "<td>".$model->getfirstname()."</td>\n";
       $returnvalue .= "<td>".$model->getmiddlename()."</td>\n";
       $returnvalue .= "<td>".$model->getstartyearactive()."</td>\n";
       $returnvalue .= "<td>".$model->getendyearactive()."</td>\n";
       $returnvalue .= "<td>".$model->getnotes()."</td>\n";
       $returnvalue .= "<td>".$model->getrating()."</td>\n";
       $returnvalue .= "<td>".$model->getguid()."</td>\n";
       $returnvalue .= "<td>".$model->getpreferredrecbyid()."</td>\n";
       $returnvalue .= "<td>".$model->getinitialtimestamp()."</td>\n";
       $returnvalue .= "<td>".$model->getbiography()."</td>\n";
       $returnvalue .= "<td>".$model->gettaxonomicgroups()."</td>\n";
       $returnvalue .= "<td>".$model->getcollectionsat()."</td>\n";
       $returnvalue .= "<td>".$model->getcurated()."</td>\n";
       $returnvalue .= "<td>".$model->getnototherwisespecified()."</td>\n";
       $returnvalue .= "<td>".$model->getdatelastmodified()."</td>\n";
       $returnvalue .= "<td>".$model->getlastmodifiedbyuid()."</td>\n";
       $returnvalue .= "<td>".$model->gettype()."</td>\n";
       $returnvalue .= "<td>".$model->getnamestring()."</td>\n";
       $returnvalue .= "<td>".$model->getprefix()."</td>\n";
       $returnvalue .= "<td>".$model->getsuffix()."</td>\n";
       $returnvalue .= "<td>".$model->getyearofbirth()."</td>\n";
       $returnvalue .= "<td>".$model->getyearofbirthmodifier()."</td>\n";
       $returnvalue .= "<td>".$model->getyearofdeath()."</td>\n";
       $returnvalue .= "<td>".$model->getyearofdeathmodifier()."</td>\n";
       $returnvalue .= "<td>".$model->getliving()."</td>\n";
       $returnvalue .= "<td>".$model->getmbox_sha1sum()."</td>\n";
       $returnvalue .= "<td>".$model->getuuid()."</td>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>".Agent::AGENTID."</th>\n";
       $returnvalue .= "<th>".Agent::FAMILYNAME."</th>\n";
       $returnvalue .= "<th>".Agent::FIRSTNAME."</th>\n";
       $returnvalue .= "<th>".Agent::MIDDLENAME."</th>\n";
       $returnvalue .= "<th>".Agent::STARTYEARACTIVE."</th>\n";
       $returnvalue .= "<th>".Agent::ENDYEARACTIVE."</th>\n";
       $returnvalue .= "<th>".Agent::NOTES."</th>\n";
       $returnvalue .= "<th>".Agent::RATING."</th>\n";
       $returnvalue .= "<th>".Agent::GUID."</th>\n";
       $returnvalue .= "<th>".Agent::PREFERREDRECBYID."</th>\n";
       $returnvalue .= "<th>".Agent::INITIALTIMESTAMP."</th>\n";
       $returnvalue .= "<th>".Agent::BIOGRAPHY."</th>\n";
       $returnvalue .= "<th>".Agent::TAXONOMICGROUPS."</th>\n";
       $returnvalue .= "<th>".Agent::COLLECTIONSAT."</th>\n";
       $returnvalue .= "<th>".Agent::CURATED."</th>\n";
       $returnvalue .= "<th>".Agent::NOTOTHERWISESPECIFIED."</th>\n";
       $returnvalue .= "<th>".Agent::DATELASTMODIFIED."</th>\n";
       $returnvalue .= "<th>".Agent::LASTMODIFIEDBYUID."</th>\n";
       $returnvalue .= "<th>".Agent::TYPE."</th>\n";
       $returnvalue .= "<th>".Agent::NAMESTRING."</th>\n";
       $returnvalue .= "<th>".Agent::PREFIX."</th>\n";
       $returnvalue .= "<th>".Agent::SUFFIX."</th>\n";
       $returnvalue .= "<th>".Agent::YEAROFBIRTH."</th>\n";
       $returnvalue .= "<th>".Agent::YEAROFBIRTHMODIFIER."</th>\n";
       $returnvalue .= "<th>".Agent::YEAROFDEATH."</th>\n";
       $returnvalue .= "<th>".Agent::YEAROFDEATHMODIFIER."</th>\n";
       $returnvalue .= "<th>".Agent::LIVING."</th>\n";
       $returnvalue .= "<th>".Agent::MBOX_SHA1SUM."</th>\n";
       $returnvalue .= "<th>".Agent::UUID."</th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getShortTableRowView() {
       global $CLIENT_ROOT;
       $returnvalue = '<tr>';
       $model = $this->model;
       $dates = "(".$model->getyearofbirth()."-".$model->getyearofdeath().")";
       $returnvalue .= "<td><a href='$CLIENT_ROOT/agents/agent.php?agentid=" . htmlspecialchars($model->getagentid(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "'>" . htmlspecialchars($model->getMinimalName(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a></td>\n";
       $returnvalue .= "<td>$dates</td>\n";
       $returnvalue .= "<td>".$model->gettype()."</td>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getShortHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>Name</th>\n";
       $returnvalue .= "<th>Dates</th>\n";
       $returnvalue .= "<th>Type</th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }

   public function getEditFormView($includeRelated=true) {
       global $CLIENT_ROOT;
       $model = $this->model;
       if (strlen($model->getagentid())==0) {
          $new = TRUE;
       } else {
          $new = FALSE;
       }
       $returnvalue  = "
     <script type='text/javascript'>
        var frm = $('#saveRecordForm');
        frm.submit(function () {
            $('#statusDiv').html('Saving...');
            $('#resultDiv').html('');
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: frm.serialize(),
               dataType : 'html',
               success: function(data){
                  $('#resultDiv').html(data);
                  $('#statusDiv').html('');
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
     </script>
";
       $returnvalue .= "<div id='statusDiv'></div>";
       $returnvalue .= '<form method="POST" id="saveRecordForm" action='.$CLIENT_ROOT.'/agents/rpc/handler.php>';
       if ($new) {
          $returnvalue .= '<input type=hidden name=mode id=mode value=savenew>';
       } else {
          $returnvalue .= '<input type=hidden name=mode id=mode value=save>';
       }
       $returnvalue .= '<input type=hidden name=table id=table value="Agent">';
       $returnvalue .= "<input type=hidden name='agentid' id='agentid' value='".$model->getagentid()."' >\n";
       $returnvalue .= '<ul>';
       if ($new) {
          $returnvalue .= "<li>New Record</li>\n";
       } else {
          $returnvalue .= "<li>agentid: [".$model->getagentid()."]</li>\n";
          $returnvalue .= "<li>urn:uuid:".$model->getuuid()."</li>\n";
       }
       $iselected = "selected";
       $tselected = "";
       $oselected = "";
       if ($model->gettype()=='Team') { $tselected = "selected"; $iselected = ""; $oselected = ""; }
       if ($model->gettype()=='Organization') { $tselected = ""; $iselected = ""; $oselected = "selected"; }
       $returnvalue .= "<li>Agent Type: <select id='TYPEpl' name=".Agent::TYPE." >\n";
       $returnvalue .= "<option value='Individual' $iselected>Individual</option>\n";
       $returnvalue .= "<option value='Team' $tselected>Team</option>\n";
       $returnvalue .= "<option value='Organization' $oselected>Organization</option>\n";
       $returnvalue .= "</select></li>\n";
       $returnvalue .= "<li class='teamgroup' >Name<input type=text name=".Agent::NAMESTRING." id='NAMESTRING' value='".$model->getnamestring()."'  size='50'  maxlength='".Agent::NAMESTRING_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='individual'>Prefix (e.g Dr.) : <input type=text name=".Agent::PREFIX." id=".Agent::PREFIX." value='".$model->getprefix()."'  size='".Agent::PREFIX_SIZE ."'  maxlength='".Agent::PREFIX_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='individual'>Family Name: <input type=text name=".Agent::FAMILYNAME." id=".Agent::FAMILYNAME." value='".$model->getfamilyname()."'  size='".Agent::FAMILYNAME_SIZE ."'  maxlength='".Agent::FAMILYNAME_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='individual'>First name: <input type=text name=".Agent::FIRSTNAME." id=".Agent::FIRSTNAME." value='".$model->getfirstname()."'  size='".Agent::FIRSTNAME_SIZE ."'  maxlength='".Agent::FIRSTNAME_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='individual'>Middle Names<input type=text name=".Agent::MIDDLENAME." id=".Agent::MIDDLENAME." value='".$model->getmiddlename()."'  size='".Agent::MIDDLENAME_SIZE ."'  maxlength='".Agent::MIDDLENAME_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='individual'>Suffix (e.g. III): <input type=text name=".Agent::SUFFIX." id=".Agent::SUFFIX." value='".$model->getsuffix()."'  size='".Agent::SUFFIX_SIZE ."'  maxlength='".Agent::SUFFIX_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='birthdeath'>Year of Birth: <input type=text name=".Agent::YEAROFBIRTH." id=".Agent::YEAROFBIRTH." value='".$model->getyearofbirth()."'  size='".Agent::YEAROFBIRTH_SIZE ."'  maxlength='".Agent::YEAROFBIRTH_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='birthdeath'>Year of Birth modifier: <input type=text name=".Agent::YEAROFBIRTHMODIFIER." id=".Agent::YEAROFBIRTHMODIFIER." value='".$model->getyearofbirthmodifier()."'  size='".Agent::YEAROFBIRTHMODIFIER_SIZE ."'  maxlength='".Agent::YEAROFBIRTHMODIFIER_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='birthdeath'>Year of Death: <input type=text name=".Agent::YEAROFDEATH." id=".Agent::YEAROFDEATH." value='".$model->getyearofdeath()."'  size='".Agent::YEAROFDEATH_SIZE ."'  maxlength='".Agent::YEAROFDEATH_SIZE ."' ></li>\n";
       $returnvalue .= "<li class='birthdeath'>Year of Death modifier: <input type=text name=".Agent::YEAROFDEATHMODIFIER." id=".Agent::YEAROFDEATHMODIFIER." value='".$model->getyearofdeathmodifier()."'  size='".Agent::YEAROFDEATHMODIFIER_SIZE ."'  maxlength='".Agent::YEAROFDEATHMODIFIER_SIZE ."' ></li>\n";
       $yselected = "";
       $nselected = "selected";
       $qselected = "";
       if ($model->getliving()=='Y') { $yselected = "selected"; $nselected = ""; $qselected=""; }
       if ($model->getliving()=='N') { $yselected = ""; $nselected = "selected"; $qselected=""; }
       if ($model->getliving()=='?') { $yselected = ""; $nselected = ""; $qselected="selected"; }
       $returnvalue .= "<li>Living: <select id=".Agent::LIVING." name=".Agent::LIVING." >\n";
       $returnvalue .= "<option value='Y' $yselected>Yes</option>\n";
       $returnvalue .= "<option value='N' $nselected>No</option>\n";
       $returnvalue .= "<option value='?' $qselected>?</option>\n";
       $returnvalue .= "</select></li>\n";
       $returnvalue .= "<li>STARTYEARACTIVE<input type=text name=".Agent::STARTYEARACTIVE." id=".Agent::STARTYEARACTIVE." value='".$model->getstartyearactive()."'  size='".Agent::STARTYEARACTIVE_SIZE ."'  maxlength='".Agent::STARTYEARACTIVE_SIZE ."' ></li>\n";
       $returnvalue .= "<li>ENDYEARACTIVE<input type=text name=".Agent::ENDYEARACTIVE." id=".Agent::ENDYEARACTIVE." value='".$model->getendyearactive()."'  size='".Agent::ENDYEARACTIVE_SIZE ."'  maxlength='".Agent::ENDYEARACTIVE_SIZE ."' ></li>\n";
       $returnvalue .= "<li>RATING<input type=text name=".Agent::RATING." id=".Agent::RATING." value='".$model->getrating()."'  size='".Agent::RATING_SIZE ."'  maxlength='".Agent::RATING_SIZE ."' ></li>\n";
       $returnvalue .= "<li>GUID<input type=text name=".Agent::GUID." id=".Agent::GUID." value='".$model->getguid()."'  size='".Agent::GUID_SIZE ."'  maxlength='".Agent::GUID_SIZE ."' ></li>\n";
       $type = $model->gettype();
       $returnvalue .= "
       <script type='text/javascript'>
          $('#bdagentselect').autocomplete({
              source: '".$CLIENT_ROOT."/agents/rpc/handler.php?mode=listjson&type=$type',
              minLength: 2,
              select: function( event, ui ) {
                    $('#".Agent::PREFERREDRECBYID."').val(ui.item.value);
                    $('#bdagentselect').val(ui.item.label);
                    event.preventDefault();
                 }
              });
       </script>
       ";
       $dupof = "";
       if (strlen($model->getpreferredrecbyid())>0) {
          $dup = new Agent();
          $dup->load($model->getpreferredrecbyid());
          $dupof = $dup->getAssembledName(TRUE);
       }
       $returnvalue .= '<li>
                          <div class="ui-widget">
                             <label for="bdagentselect">Bad Duplicate Of</label>
                             <input id="bdagentselect" value="'.$dupof.'"/>
                             <button onClick=\' $( "#bdagentselect").val(""); $("#'.Agent::PREFERREDRECBYID.'").val(""); \' >-</button>
                             <input type="hidden" id="'.Agent::PREFERREDRECBYID.'" name="'.Agent::PREFERREDRECBYID.'" value="'.$model->getpreferredrecbyid().'"/>
                          </div>
                        </li>';
       $returnvalue .= "<li>Biography<textarea name=".Agent::BIOGRAPHY." id=".Agent::BIOGRAPHY." maxlength='".Agent::BIOGRAPHY_SIZE ."' rows='5' cols='79' >".$model->getbiography()."</textarea></li>\n";
       $returnvalue .= "<li>TAXONOMICGROUPS<input type=text name=".Agent::TAXONOMICGROUPS." id=".Agent::TAXONOMICGROUPS." value='".$model->gettaxonomicgroups()."'  size='51'  maxlength='".Agent::TAXONOMICGROUPS_SIZE ."' ></li>\n";
       $returnvalue .= "<li>COLLECTIONSAT<input type=text name=".Agent::COLLECTIONSAT." id=".Agent::COLLECTIONSAT." value='".$model->getcollectionsat()."'  size='51'  maxlength='".Agent::COLLECTIONSAT_SIZE ."' ></li>\n";
       $returnvalue .= "<li>NOTES<input type=text name=".Agent::NOTES." id=".Agent::NOTES." value='".$model->getnotes()."'  size='51'  maxlength='".Agent::NOTES_SIZE ."' ></li>\n";
       $yselected = "";
       $nselected = "selected";
       if ($model->getcurated()=='1') { $yselected = "selected"; $nselected = ""; }
       if ($model->getcurated()=='0') { $yselected = ""; $nselected = "selected"; }
       $returnvalue .= "<li>Record Is Curated: <select id=".Agent::CURATED." name=".Agent::CURATED." >\n";
       $returnvalue .= "<option value='1' $yselected>Yes</option>\n";
       $returnvalue .= "<option value='0' $nselected>No</option>\n";
       $returnvalue .= "</select></li>\n";
       $returnvalue .= "<li>NOTOTHERWISESPECIFIED<input type=text name=".Agent::NOTOTHERWISESPECIFIED." id=".Agent::NOTOTHERWISESPECIFIED." value='".$model->getnototherwisespecified()."'  size='".Agent::NOTOTHERWISESPECIFIED_SIZE ."'  maxlength='".Agent::NOTOTHERWISESPECIFIED_SIZE ."' ></li>\n";
       $returnvalue .= "<li>Date Last Modified: ".$model->getdatelastmodified()." </li>\n";
       $returnvalue .= "<li>LASTMODIFIEDBYUID: ".$model->getlastmodifiedbyuid()."</li>\n";
       $returnvalue .= "<li>MBOX_SHA1SUM<input type=text name=".Agent::MBOX_SHA1SUM." id=".Agent::MBOX_SHA1SUM." value='".$model->getmbox_sha1sum()."'  size='".Agent::MBOX_SHA1SUM_SIZE ."'  maxlength='".Agent::MBOX_SHA1SUM_SIZE ."' ></li>\n";
       $returnvalue .= "<li>INITIALTIMESTAMP ".$model->getinitialtimestamp()."</li>\n";

       $returnvalue .= '<li><input type=submit value="Save"></li>';
       $returnvalue .= '</ul>';
       $returnvalue .= '</form>';
       $returnvalue .= "<div id='resultDiv'></div>";
       $returnvalue .= "
       <script type='text/javascript'>
           function showHideByType() {
               var selection =  $( '#TYPEpl' ).val() ;
               if (selection=='Individual') {
                  $('.teamgroup').hide();
                  $('.individual').show();
               } else {
                  $('.teamgroup').show();
                  $('.individual').hide();
               }
               if (selection=='Team') {
                  $('.birthdeath').hide();
               } else {
                  $('.birthdeath').show();
               }
           }
           $('#TYPEpl').change(showHideByType);
           showHideByType();
       </script>
";
       return  $returnvalue;
   }
}

class ctnametypes {
   protected $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
    }

   /**
    * Obtain a list of the name types from code table ctnametypes.
    *
    * @return an array of strings, one entry for each valid name type.
    */
   public function listNameTypes() {
        $result = array();
        $sql = "select type from ctnametypes ";
        if ($statement = $this->conn->prepare($sql)) {
            $statement->execute();
            $statement->bind_result($nametype);
            while ($statement->fetch()) {
               $result[] = $nametype;
            }
            $statement->close();
        } else {
            throw new Exception("Query preparation failed for '$sql'");
        }
        return $result;
   }

}

class agentteams {
	protected $conn;
   // These constants hold the sizes the fields in this table in the database.
   const AGENTTEAMID_SIZE     = 20; //BIGINT
   const TEAMAGENTID_SIZE = 20; //BIGINT
   const MEMBERAGENTID_SIZE = 20; //BIGINT
   const ORDINAL_SIZE         = 11; //INTEGER
    // These constants hold the field names of the table in the database.
   const AGENTTEAMID       = 'agentteamid';
   const TEAMAGENTID  = 'teamagentid';
   const MEMBERAGENTID = 'memberagentid';
   const ORDINAL           = 'ordinal';

   public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
		$this->agentteamid = NULL;
		$this->ordinal = 0;
   }

   //---------------------------------------------------------------------------

   // interface tableSchema implementation
   // schemaPK returns array of primary key field names
   public function schemaPK() {
       return $this->primaryKeyArray;
   }
   // schemaHaveDistinct returns array of field names for which selectDistinct{fieldname} methods are available.
   public function schemaHaveDistinct() {
       return $this->selectDistinctFieldsArray;
   }
   // schemaFields returns array of all field names
   public function schemaFields() {
       return $this->allFieldsArray;
   }
/*  Example sanitized retrieval of variable matching object variables from $_GET
/*  Customize these to limit each variable to narrowest possible set of known good values.

  $agentteamid = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['agentteamid']), 0, 20);
  $teamagentid = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['teamagentid']), 0, 20);
  $memberagentid = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['memberagentid']), 0, 20);
  $ordinal = substr(preg_replace('/[^0-9\-\[NULL\]]/','',$_GET['ordinal']), 0, 11);
*/

   //---------------------------------------------------------------------------

   private $agentteamid; // PK BIGINT
   private $teamagentid; // BIGINT
   private $memberagentid; // BIGINT
   private $ordinal; // INTEGER
   private $dirty;
   private $loaded;
   private $error;
   const FIELDLIST = ' agentteamid, teamagentid, memberagentid, ordinal, ';
   const PKFIELDLIST = ' agentteamid, ';
   const NUMBER_OF_PRIMARY_KEYS = 1;
   private $primaryKeyArray = array( 1 => 'agentteamid'  ) ;
   private $allFieldsArray = array( 0 => 'agentteamid' , 1 => 'teamagentid' , 2 => 'memberagentid' , 3 => 'ordinal'  ) ;
   private $selectDistinctFieldsArray = array() ;

   //---------------------------------------------------------------------------

   // constructor
   function agentteams(){
       $this->agentteamid = NULL;
       $this->teamagentid = '';
       $this->memberagentid = '';
       $this->ordinal = '';
       $this->dirty = false;
       $this->loaded = false;
       $this->error = '';
   }

   private function l_addslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars_decode($value, ENT_QUOTES);
      return $retval;
   }
   private function l_stripslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars($value, ENT_QUOTES);
      return $retval;
   }
   public function isDirty() {
       return $this->dirty;
   }
   public function isLoaded() {
       return $this->loaded;
   }
   public function errorMessage() {
       return $this->error;
   }

   //---------------------------------------------------------------------------

   public function keyValueSet($fieldname,$value) {
       $returnvalue = false;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentteamid') { $returnvalue = $this->setagentteamid($value); }
             if ($fieldname=='teamagentid') { $returnvalue = $this->setteamagentid($value); }
             if ($fieldname=='memberagentid') { $returnvalue = $this->setmemberagentid($value); }
             if ($fieldname=='ordinal') { $returnvalue = $this->setordinal($value); }
             $returnvalue = true;
          }
          catch (exception $e) { ;
              $returnvalue = false;
              throw new Exception('Field Set Error'.$e->getMessage());
          }
       } else {
          throw new Exception('No Such field');
       }
       return $returnvalue;
   }
   public function keyGet($fieldname) {
       $returnvalue = null;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentteamid') { $returnvalue = $this->getagentteamid(); }
             if ($fieldname=='teamagentid') { $returnvalue = $this->getteamagentid(); }
             if ($fieldname=='memberagentid') { $returnvalue = $this->getmemberagentid(); }
             if ($fieldname=='ordinal') { $returnvalue = $this->getordinal(); }
          }
          catch (exception $e) { ;
              $returnvalue = null;
          }
       }
       return $returnvalue;
   }
/*agentteamid*/
   public function getagentteamid() {
       if ($this->agentteamid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentteamid));
       }
   }
   public function setagentteamid($agentteamid) {
       if (strlen($agentteamid) > agentteams::AGENTTEAMID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->agentteamid = $this->l_addslashes($agentteamid);
       $this->dirty = true;
   }
/*teamagentid*/
   public function getteamagentid() {
       if ($this->teamagentid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->teamagentid));
       }
   }
   public function setteamagentid($teamagentid) {
       if (strlen($teamagentid) > agentteams::TEAMAGENTID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->teamagentid = $this->l_addslashes($teamagentid);
       $this->dirty = true;
   }
/*memberagentid*/
   public function getmemberagentid() {
       if ($this->memberagentid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->memberagentid));
       }
   }
   public function setmemberagentid($memberagentid) {
       if (strlen($memberagentid) > agentteams::MEMBERAGENTID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->memberagentid = $this->l_addslashes($memberagentid);
       $this->dirty = true;
   }
/*ordinal*/
   public function getordinal() {
       if ($this->ordinal==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->ordinal));
       }
   }
   public function setordinal($ordinal) {
       if (strlen(preg_replace('/[^0-9]/','',$ordinal)) > agentteams::ORDINAL_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $ordinal = trim($ordinal);
       if (!ctype_digit(strval($ordinal)) && trim(strval($ordinal))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->ordinal = $this->l_addslashes($ordinal);
       $this->dirty = true;
   }
   public function PK() { // get value of primary key
        $returnvalue = '';
        $returnvalue .= $this->getagentteamid();
        return $returnvalue;
   }
   public function PKArray() { // get name and value of primary key fields
        $returnvalue = array();
        $returnvalue['agentteamid'] = $this->getagentteamid();
        return $returnvalue;
   }
   public function NumberOfPrimaryKeyFields() { // returns the number of primary key fields defined for this table
        return 1;
   }

   // Constants holding the mysqli field type character (s,i,d) for each field
   const C_agentteamidMYSQLI_TYPE = 'i';
   const C_teamagentidMYSQLI_TYPE = 'i';
   const C_memberagentidMYSQLI_TYPE = 'i';
   const C_ordinalMYSQLI_TYPE = 'i';

   // function to obtain the mysqli field type character from a fieldname
   public function MySQLiFieldType($aFieldname) {
      $retval = '';
      if ($aFieldname=='agentteamid') { $retval = self::C_agentteamidMYSQLI_TYPE; }
      if ($aFieldname=='teamagentid') { $retval = self::C_teamagentidMYSQLI_TYPE; }
      if ($aFieldname=='memberagentid') { $retval = self::C_memberagentidMYSQLI_TYPE; }
      if ($aFieldname=='ordinal') { $retval = self::C_ordinalMYSQLI_TYPE; }
      return $retval;
   }

   // Function load() can take either the value of the primary key which uniquely identifies a particular row
   // or an array of array('primarykeyfieldname'=>'value') in the case of a single field primary key
   // or an array of fieldname value pairs in the case of multiple field primary key.
   public function load($pk) {
        $returnvalue = false;
        try {
             if (is_array($pk)) {
                 $this->setagentteamid($pk[agentteamid]);
             } else { ;
                 $this->setagentteamid($pk);
             };
        }
        catch (Exception $e) {
             throw new Exception($e->getMessage());
        }
        if($this->agentteamid != NULL) {
           $preparesql = 'SELECT agentteamid, teamagentid, memberagentid, ordinal FROM agentteams WHERE agentteamid = ? ';
           if ($statement = $this->conn->prepare($preparesql)) {
              $statement->bind_param("i", $this->agentteamid);
              $statement->execute();
              $statement->bind_result($this->agentteamid, $this->teamagentid, $this->memberagentid, $this->ordinal);
              $statement->fetch();
              $statement->close();
           }

            $this->loaded = true;
            $this->dirty = false;
        } else {
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   // Function save() will either save the current record or insert a new record.
   // Inserts new record if the primary key field in this table is null
   // for this instance of this object.
   // Otherwise updates the record identified by the primary key value.
   public function save() {
        $returnvalue = false;
        // Test to see if this is an insert or update.
        if ($this->agentteamid!= NULL) {
            $sql  = 'UPDATE  agentteams SET ';
            $isInsert = false;
            $sql .=  "teamagentid = ? ";
            $sql .=  ", memberagentid = ? ";
            $sql .=  ", ordinal = ? ";

            $sql .= "  WHERE agentteamid = ? ";
        } else {
            $sql  = 'INSERT INTO agentteams ';
            $isInsert = true;
            $sql .= '(  teamagentid ,  memberagentid ,  ordinal ) VALUES (';
            $sql .=  "  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .= ')';

        }
        if ($statement = $this->conn->prepare($sql)) {
           if ($this->agentteamid!= NULL ) {
              $statement->bind_param("iiii",  $this->teamagentid , $this->memberagentid , $this->ordinal , $this->agentteamid );
           } else {
              $statement->bind_param("iii",  $this->teamagentid , $this->memberagentid , $this->ordinal );
           }
           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $this->error = $statement->error;
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
        }
        if ($this->error=='') {
            $returnvalue = true;
        };

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function delete() {
        $returnvalue = false;
        if($this->agentteamid != NULL) {

           $preparedsql = 'SELECT agentteamid FROM agentteams WHERE agentteamid = ?  ' ;
            if ($statement = $this->conn->prepare($preparedsql)) {
               $statement->bind_param("i", $this->agentteamid);
               $statement->execute();
               $statement->store_result();
               if ($statement->num_rows()==1) {
                    $sql = 'DELETE FROM agentteams WHERE agentteamid = ?  ';
                    if ($stmt_delete = $this->conn->prepare($sql)) {
                       $stmt_delete->bind_param("i", $this->agentteamid);
                       if ($stmt_delete->execute()) {
                           $returnvalue = true;
                       } else {
                           $this->error = mysqli_error($this->conn);
                       }
                       $stmt_delete->close();
                    }
               } else {
                   $this->error = mysqli_error($this->conn);
               }
               $statement->close();
            } else {
                $this->error = mysqli_error($this->conn);
            }

            $this->loaded = true;
            // record was deleted, so set PK to null
            $this->agentteamid = NULL;
        } else {
           throw new Exception('Unable to identify which record to delete, primary key is not set');
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function count() {
        $returnvalue = false;
        $sql = 'SELECT count(*)  FROM agentteams';
        if ($result = $this->conn->query($sql)) {
           if ($result->num_rows()==1) {
             $row = $result->fetch_row();
             if ($row) {
                $returnvalue = $row[0];
             }
           }
        } else {
           $this->error = mysqli_error($this->conn);
        }
        mysqli_free_result($result);

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function loadArrayKeyValueSearch($searchTermArray) {
       $returnvalue = array();
       $and = '';
       $wherebit = 'WHERE ';
       foreach($searchTermArray as $fieldname => $searchTerm) {
           if ($this->hasField($fieldname)) {
               $operator = '=';
               // change to a like search if a wildcard character is present
               if (!(strpos($searchTerm,'%')===false)) { $operator = 'like'; }
               if (!(strpos($searchTerm,'_')===false)) { $operator = 'like'; }
               if ($searchTerm=='[NULL]') {
                   $wherebit .= "$and ($fieldname is null or $fieldname='') ";
               } else {
                   $wherebit .= "$and $fieldname $operator ? ";
                   $types = $types . $this->MySQLiFieldType($fieldname);
               }
               $and = ' and ';
           }
       }
       $sql = "SELECT agentteamid FROM agentteams $wherebit";
       if ($wherebit=='') {
             $this->error = 'Error: No search terms provided';
       } else {
          $statement = $this->conn->prepare($sql);
          $vars = Array();
          $vars[] = $types;
          $i = 0;
          foreach ($searchTermArray as $value) {
               $varname = 'bind'.$i;  // create a variable name
               $$varname = $value;    // using that variable name store the value
               $vars[] = &$$varname;  // add a reference to the variable to the array
               $i++;
           }
           //$vars[] contains $types followed by references to variables holding each value in $searchTermArray.
          call_user_func_array(array($statement,'bind_param'),$vars);
          //$statement->bind_param($types,$names);
          $statement->execute();
          $statement->bind_result($id);
          $ids = array();
          while ($statement->fetch()) {
              $ids[] = $id;
          } // double loop to allow all data to be retrieved before preparing a new statement.
          $statement->close();
          for ($i=0;$i<count($ids);$i++) {
              $obj = new agentteams();
              $obj->load($ids[$i]);
              $returnvalue[] = $obj;
              $result=true;
          }
          if ($result===false) { $this->error = mysqli_error($this->conn); }
       }
       return $returnvalue;
   }

   //---------------------------------------------------------------------------

   public function loadLinkedTo() {
     $returnvalue = array();
       // fk: teamagentid
      $t = new Agent();
      $t->load(getteamagentid());
      $returnvalue[teamagentid] = $t;
       // fk: memberagentid
      $t = new Agent();
      $t->load(getmemberagentid());
      $returnvalue[memberagentid] = $t;
     return $returnvalue;
   }

   //---------------------------------------------------------------------------

   public function hasField($fieldname) {
       $returnvalue = false;
       if (trim($fieldname)!='' && trim($fieldname)!=',') {
            if (strpos(self::FIELDLIST," $fieldname, ")!==false) {
               $returnvalue = true;
            }
       }
       return $returnvalue;
    }
   //---------------------------------------------------------------------------

}


class agentteamsView {
   var $model = null;
   public function setModel($aModel) {
       $this->model = $aModel;
   }

   public function getSummaryLine() {
       $returnvalue = "";
       $member = new Agent();
       $member->load($model->getmemberagentid());
       $returnvalue .= $member->getAssembledName();
       $returnvalue .= " (".$model->getordinal().")\n";
       return $returnvalue;
   }
   // @param $includeRelated default true shows rows from other tables through foreign key relationships.
   // @param $editLinkURL default '' allows adding a link to show this record in an editing form.
   public function getDetailsView($includeRelated=true, $editLinkURL='') {
       $returnvalue = '<ul>';
       $editLinkURL=trim($editLinkURL);
       $model = $this->model;
       $primarykeys = $model->schemaPK();
       if ($editLinkURL!='') {
          if (!preg_match('/\&$/',$editLinkURL)) { $editLinkURL .= '&'; }
          $nullpk = false;
          foreach ($primarykeys as $primarykey) {
              // Add fieldname=value pairs for primary key(s) to editLinkURL.
              $editLinkURL .= urlencode($primarykey) . '=' . urlencode($model->keyGet($primarykey));
              if ($model->keyGet($primarykey)=='') { $nullpk = true; }
          }
          if (!$nullpk) { $returnvalue .= "<li>agentteams <a href='$editLinkURL'>Edit</a></li>\n";  }
       }
       $team = new Agent();
       $team->load($model->getteamagentid());
       $member = new Agent();
       $member->load($model->getmemberagentid());
       $returnvalue .= "<li>".agentteams::TEAMAGENTID.": ".$team->getAssembledName()."</li>\n";
       $returnvalue .= "<li>".agentteams::MEMBERAGENTID.": ".$member->getAssembledName()."</li>\n";
       $returnvalue .= "<li>".agentteams::ORDINAL.": ".$model->getordinal()."</li>\n";
       if ($includeRelated) {
           // note that $includeRelated is provided as false in calls out to
           // related tables to prevent infinite loops between related objects.
           $returnvalue .= "<li>Team</li>";
           $t_agents = new agents();
           $t_agentsView = new agentsView();
           $t_agentsView->setModel($t_agents);
           if ($model->getteamagentid() != '') {
               $t_agents->load($model->getteamagentid());
               $returnvalue .= $t_agentsView->getDetailsView(false);
           }
           $returnvalue .= "<li>Members</li>";
           $t_agents = new agents();
           $t_agentsView = new agentsView();
           $t_agentsView->setModel($t_agents);
           if ($model->getmemberagentid() != '') {
               $t_agents->load($model->getmemberagentid());
               $returnvalue .= $t_agentsView->getDetailsView(false);
           }

        }
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getJSON() {
       $returnvalue = '{ ';
       $model = $this->model;
       $returnvalue .= '"'.agentteams::AGENTTEAMID.': "'.$model->getagentteamid().'",';
       $returnvalue .= '"'.agentteams::TEAMAGENTID.': "'.$model->getteamagentid().'",';
       $returnvalue .= '"'.agentteams::MEMBERAGENTID.': "'.$model->getmemberagentid().'",';
       $returnvalue .= '"'.agentteams::ORDINAL.': "'.$model->getordinal().'" }';
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getTableRowView() {
       $returnvalue = '<tr>';
       $model = $this->model;
       $returnvalue .= "<td>".$model->getagentteamid()."</td>\n";
       $returnvalue .= "<td>".$model->getteamagentid()."</td>\n";
       $returnvalue .= "<td>".$model->getmemberagentid()."</td>\n";
       $returnvalue .= "<td>".$model->getordinal()."</td>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>".agentteams::AGENTTEAMID."</th>\n";
       $returnvalue .= "<th>".agentteams::TEAMAGENTID."</th>\n";
       $returnvalue .= "<th>".agentteams::MEMBERAGENTID."</th>\n";
       $returnvalue .= "<th>".agentteams::ORDINAL."</th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getEditFormView() {
       global $CLIENT_ROOT;
       $model = $this->model;
       if (strlen($model->getagentteamid())==0) {
          $new = TRUE;
       } else {
          $new = FALSE;
       }

       $returnvalue  = "
     <script type='text/javascript'>
        var frm = $('#saveATeamRecordForm');
        frm.submit(function () {
            $('#statusDiv').html('Saving...');
            $('#saveATeamResultDiv').html('');
            $.ajax({
               type: 'POST',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: frm.serialize(),
               dataType : 'html',
               success: function(data){
                  $('#saveATeamResultDiv').html(data);
                  $('#statusDiv').html('');
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
     </script>
";
       $returnvalue .= '<div id="saveATeamResultDiv"></div>';
       $returnvalue .= '<form id="saveATeamRecordForm">';
       if ($new) {
          $returnvalue .= '<input type=hidden name=mode id=mode value=savenew>';
       } else {
          $returnvalue .= '<input type=hidden name=mode id=mode value=save>';
       }
       $returnvalue .= '<input type=hidden name=table id=table value="AgentTeams">';
       $returnvalue .= '<input type=hidden name=agentteamid value="'.$model->getagentteamid().'">';
       $returnvalue .= '<input type=hidden name=teamagentid value="'.$model->getteamagentid().'">';
       $returnvalue .= '<ul>';
       // $returnvalue .= "<li>TEAMAGENTID<input type=text name=".agentteams::TEAMAGENTID." id=".agentteams::TEAMAGENTID." value='".$model->getteamagentid()."'  size='".agentteams::TEAMAGENTID_SIZE ."'  maxlength='".agentteams::TEAMAGENTID_SIZE ."' ></li>\n";
       $team = new Agent();
       $team->load($model->getteamagentid());
       $returnvalue .= "<li>Team: ".$team->getAssembledName()."</li>\n";
       $am = new AgentManager();
       $returnvalue .= $am->createAgentPicker(agentteams::MEMBERAGENTID,'Member',$model->getmemberagentid());
       //$returnvalue .= "<li>Member<input type=text name=".agentteams::MEMBERAGENTID." id=".agentteams::MEMBERAGENTID." value='".$model->getmemberagentid()."'  size='".agentteams::MEMBERAGENTID_SIZE ."'  maxlength='".agentteams::MEMBERAGENTID_SIZE ."' ></li>\n";
       $returnvalue .= "<li>Order<input type=text name=".agentteams::ORDINAL." id=".agentteams::ORDINAL." value='".$model->getordinal()."'  size='".agentteams::ORDINAL_SIZE ."'  maxlength='".agentteams::ORDINAL_SIZE ."' ></li>\n";
       $returnvalue .= '<li><input type=submit value="Save"></li>';
       $returnvalue .= '</ul>';
       $returnvalue .= '</form>';
       return  $returnvalue;
   }
}


class agentnames {
   protected $conn;

   // These constants hold the sizes the fields in this table in the database.
   const OMCOLLECTORNAMESID_SIZE = 20; //BIGINT
   const AGENTID_SIZE    = 11; //INTEGER
   const TYPE_SIZE            = 32; //32
   const NAME_SIZE            = 255; //255
   const LANGUAGE_SIZE        = 6; //6
   const TIMESTAMPCREATED_SIZE = 21; //TIMESTAMP
   const CREATEDBYUID_SIZE    = 11; //INTEGER
   const DATELASTMODIFIED_SIZE = 21; //TIMESTAMP
   const LASTMODIFIEDBYUID_SIZE = 11; //INTEGER
    // These constants hold the field names of the table in the database.
   const OMCOLLECTORNAMESID = 'agentnamesid';
   const AGENTID      = 'agentid';
   const TYPE              = 'type';
   const NAME              = 'name';
   const LANGUAGE          = 'language';
   const TIMESTAMPCREATED  = 'timestampcreated';
   const CREATEDBYUID      = 'createdbyuid';
   const DATELASTMODIFIED  = 'datelastmodified';
   const LASTMODIFIEDBYUID = 'lastmodifiedbyuid';

   //---------------------------------------------------------------------------
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
    }

/*  Example sanitized retrieval of variable matching object variables from $_GET
/*  Customize these to limit each variable to narrowest possible set of known good values.

  $agentnamesid = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['agentnamesid']), 0, 20);
  $agentid = substr(preg_replace('/[^0-9\-\[NULL\]]/','',$_GET['agentid']), 0, 11);
  $type = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['type']), 0, 32);
  $name = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['name']), 0, 255);
  $language = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['language']), 0, 6);
  $timestampcreated = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['timestampcreated']), 0, 21);
  $createdbyuid = substr(preg_replace('/[^0-9\-\[NULL\]]/','',$_GET['createdbyuid']), 0, 11);
  $datelastmodified = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['datelastmodified']), 0, 21);
  $lastmodifiedbyuid = substr(preg_replace('/[^0-9\-\[NULL\]]/','',$_GET['lastmodifiedbyuid']), 0, 11);
*/

   //---------------------------------------------------------------------------

   private $agentnamesid; // PK BIGINT
   private $agentid; // INTEGER
   private $type; // VARCHAR(32)
   private $name; // VARCHAR(255)
   private $language; // VARCHAR(6)
   private $timestampcreated; // TIMESTAMP
   private $createdbyuid; // INTEGER
   private $datelastmodified; // TIMESTAMP
   private $lastmodifiedbyuid; // INTEGER
   private $dirty;
   private $loaded;
   private $error;

   const FIELDLIST = ' agentnamesid, agentid, type, name, language, timestampcreated, createdbyuid, datelastmodified, lastmodifiedbyuid, ';
   const PKFIELDLIST = ' agentnamesid, ';
   const NUMBER_OF_PRIMARY_KEYS = 1;
   private $primaryKeyArray = array( 1 => 'agentnamesid'  ) ;
   private $allFieldsArray = array( 0 => 'agentnamesid' , 1 => 'agentid' , 2 => 'type' , 3 => 'name' , 4 => 'language' , 5 => 'timestampcreated' , 6 => 'createdbyuid' , 7 => 'datelastmodified' , 8 => 'lastmodifiedbyuid'  ) ;
   private $selectDistinctFieldsArray = array( 3 => 'name'  ) ;

   //---------------------------------------------------------------------------

   // constructor
   function agentnames(){
       $this->agentnamesid = NULL;
       $this->agentid = '';
       $this->type = '';
       $this->name = '';
       $this->language = '';
       $this->timestampcreated = '';
       $this->createdbyuid = '';
       $this->datelastmodified = '';
       $this->lastmodifiedbyuid = '';
       $this->dirty = false;
       $this->loaded = false;
       $this->error = '';
   }

   private function l_addslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars_decode($value, ENT_QUOTES);
      return $retval;
   }
   private function l_stripslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars($value, ENT_QUOTES);
      return $retval;
   }
   public function isDirty() {
       return $this->dirty;
   }
   public function isLoaded() {
       return $this->loaded;
   }
   public function errorMessage() {
       return $this->error;
   }

   public function hasField($fieldname) {
       $returnvalue = false;
       if (trim($fieldname)!='' && trim($fieldname)!=',') {
            if (strpos(self::FIELDLIST," $fieldname, ")!==false) {
               $returnvalue = true;
            }
       }
       return $returnvalue;
    }


   //---------------------------------------------------------------------------

   public function keyValueSet($fieldname,$value) {
       $returnvalue = false;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentnamesid') { $returnvalue = $this->setagentnamesid($value); }
             if ($fieldname=='agentid') { $returnvalue = $this->setagentid($value); }
             if ($fieldname=='type') { $returnvalue = $this->settype($value); }
             if ($fieldname=='name') { $returnvalue = $this->setname($value); }
             if ($fieldname=='language') { $returnvalue = $this->setlanguage($value); }
             if ($fieldname=='timestampcreated') { $returnvalue = $this->settimestampcreated($value); }
             if ($fieldname=='createdbyuid') { $returnvalue = $this->setcreatedbyuid($value); }
             if ($fieldname=='datelastmodified') { $returnvalue = $this->setdatelastmodified($value); }
             if ($fieldname=='lastmodifiedbyuid') { $returnvalue = $this->setlastmodifiedbyuid($value); }
             $returnvalue = true;
          }
          catch (exception $e) { ;
              $returnvalue = false;
              throw new Exception('Field Set Error'.$e->getMessage());
          }
       } else {
          throw new Exception('No Such field');
       }
       return $returnvalue;
   }
   public function keyGet($fieldname) {
       $returnvalue = null;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentnamesid') { $returnvalue = $this->getagentnamesid(); }
             if ($fieldname=='agentid') { $returnvalue = $this->getagentid(); }
             if ($fieldname=='type') { $returnvalue = $this->gettype(); }
             if ($fieldname=='name') { $returnvalue = $this->getname(); }
             if ($fieldname=='language') { $returnvalue = $this->getlanguage(); }
             if ($fieldname=='timestampcreated') { $returnvalue = $this->gettimestampcreated(); }
             if ($fieldname=='createdbyuid') { $returnvalue = $this->getcreatedbyuid(); }
             if ($fieldname=='datelastmodified') { $returnvalue = $this->getdatelastmodified(); }
             if ($fieldname=='lastmodifiedbyuid') { $returnvalue = $this->getlastmodifiedbyuid(); }
          }
          catch (exception $e) { ;
              $returnvalue = null;
          }
       }
       return $returnvalue;
   }
/*agentnamesid*/
   public function getagentnamesid() {
       if ($this->agentnamesid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentnamesid));
       }
   }
   public function setagentnamesid($agentnamesid) {
       if (strlen($agentnamesid) > agentnames::OMCOLLECTORNAMESID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->agentnamesid = $this->l_addslashes($agentnamesid);
       $this->dirty = true;
   }
/*agentid*/
   public function getagentid() {
       if ($this->agentid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentid));
       }
   }
   public function setagentid($agentid) {
       if (strlen(preg_replace('/[^0-9]/','',$agentid)) > agentnames::AGENTID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $agentid = trim($agentid);
       if (!ctype_digit(strval($agentid))) {
             throw new Exception("Value must be an integer");
       }
       $this->agentid = $this->l_addslashes($agentid);
       $this->dirty = true;
   }
/*type*/
   public function gettype() {
       if ($this->type==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->type));
       }
   }
   public function settype($type) {
       if (strlen($type) > agentnames::TYPE_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->type = $this->l_addslashes($type);
       $this->dirty = true;
   }
/*name*/
   public function getname() {
       if ($this->name==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->name));
       }
   }
   public function setname($name) {
       if (strlen($name) > agentnames::NAME_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->name = $this->l_addslashes($name);
       $this->dirty = true;
   }
/*language*/
   public function getlanguage() {
       if ($this->language==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->language));
       }
   }
   public function setlanguage($language) {
       if (strlen($language) > agentnames::LANGUAGE_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->language = $this->l_addslashes($language);
       $this->dirty = true;
   }
/*timestampcreated*/
   public function gettimestampcreated() {
       if ($this->timestampcreated==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->timestampcreated));
       }
   }
   public function settimestampcreated($timestampcreated) {
       if (strlen($timestampcreated) > agentnames::TIMESTAMPCREATED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->timestampcreated = $this->l_addslashes($timestampcreated);
       $this->dirty = true;
   }
/*createdbyuid*/
   public function getcreatedbyuid() {
       if ($this->createdbyuid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->createdbyuid));
       }
   }
   public function setcreatedbyuid($createdbyuid) {
       if (strlen(preg_replace('/[^0-9]/','',$createdbyuid)) > agentnames::CREATEDBYUID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $createdbyuid = trim($createdbyuid);
       if (!ctype_digit(strval($createdbyuid))) {
             throw new Exception("Value must be an integer");
       }
       $this->createdbyuid = $this->l_addslashes($createdbyuid);
       $this->dirty = true;
   }
/*datelastmodified*/
   public function getdatelastmodified() {
       if ($this->datelastmodified==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->datelastmodified));
       }
   }
   public function setdatelastmodified($datelastmodified) {
       if (strlen($datelastmodified) > agentnames::DATELASTMODIFIED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->datelastmodified = $this->l_addslashes($datelastmodified);
       $this->dirty = true;
   }
/*lastmodifiedbyuid*/
   public function getlastmodifiedbyuid() {
       if ($this->lastmodifiedbyuid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->lastmodifiedbyuid));
       }
   }
   public function setlastmodifiedbyuid($lastmodifiedbyuid) {
       if (strlen(preg_replace('/[^0-9]/','',$lastmodifiedbyuid)) > agentnames::LASTMODIFIEDBYUID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $lastmodifiedbyuid = trim($lastmodifiedbyuid);
       if (!ctype_digit(strval($lastmodifiedbyuid)) && trim(strval($lastmodifiedbyuid))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->lastmodifiedbyuid = $this->l_addslashes($lastmodifiedbyuid);
       $this->dirty = true;
   }
   public function PK() { // get value of primary key
        $returnvalue = '';
        $returnvalue .= $this->getagentnamesid();
        return $returnvalue;
   }
   public function PKArray() { // get name and value of primary key fields
        $returnvalue = array();
        $returnvalue['agentnamesid'] = $this->getagentnamesid();
        return $returnvalue;
   }
   public function NumberOfPrimaryKeyFields() { // returns the number of primary key fields defined for this table
        return 1;
   }

   /**
    * Given an id for an agent, return the list of agent names associated with that agent.
    *
    * @param agentid the agentid for the agent to find names for.
    * @return an array of agentnames containing the names for the specified agent.
    */
   public function findAgentNamesByAgentId($agentid) {
      $result = array();
      $sql = "select agentnamesid from agentnames where agentid = ? ";
      if ($statement = $this->conn->prepare($sql)) {
         $statement->bind_param("i", $agentid);
         $statement->execute();
         $statement->bind_result($agentnameid);
         while ($statement->fetch()) {
             $an = new agentnames();
             $an->load($agentnameid);
             $result[] = $an;
         }
         $statement->close();
      } else {
            throw new Exception("Query preparation failed for '$sql'");
      }
      return $result;
   }

   /**
    * Given a name, find any agentids that match that name.
    */
   public function findAgentIdByName($name) {
        $result = array();
        $sql = "select distinct agentid from agentnames where name = ? ";
        if ($statement = $this->conn->prepare($sql)) {
            $statement->bind_param("s", $name);
            $statement->execute();
            $statement->bind_result($agentid);
            while ($statement->fetch()) {
               $result[] = $agentid;
            }
            $statement->close();
        } else {
            throw new Exception("Query preparation failed for '$sql'");
        }
        return $result;
   }

   public function load($pk) {
        $returnvalue = false;
        try {
           $this->setagentnamesid($pk);
        }
        catch (Exception $e) {
             throw new Exception($e->getMessage());
        }
        if($this->agentnamesid != NULL) {

           $sql = 'SELECT agentnamesid, agentid, type, name, language, timestampcreated, createdbyuid, datelastmodified, lastmodifiedbyuid FROM agentnames WHERE agentnamesid = ? ';
           if ($statement = $this->conn->prepare($sql)) {
              $statement->bind_param("i", $this->agentnamesid);
              $statement->execute();
              $statement->bind_result($this->agentnamesid, $this->agentid, $this->type, $this->name, $this->language, $this->timestampcreated, $this->createdbyuid, $this->datelastmodified, $this->lastmodifiedbyuid);
              if ($statement->fetch()) {
                 $returnvalue = true;
              }
              $statement->close();
           } else {
            throw new Exception("Query preparation failed for '$sql'");
           }

            $this->loaded = true;
            $this->dirty = false;
        } else {
            throw new Exception("Unable to find collector name by id, no id provided.");
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------


   /**
    * Function save() will either save the current record or insert a new record.
    * Inserts new record if the primary key field agentnamesid is null
    * for this instance of this object.
    * Otherwise updates the record identified by the primary key value.
    *
    * @returns boolean true on success, false on failure, also setting $this->error
    * on failure.
    * @see agentnames->errorMessage()
    */
   public function save() {
        global $SYMB_UID;
        $returnvalue = false;
        $this->error = '';
        $this->setlastmodifiedbyuid($SYMB_UID);
        $returnvalue = false;
        // Test to see if this is an insert or update.
        if ($this->agentnamesid!= NULL) {
            $sql  = 'UPDATE  agentnames SET ';
            $isInsert = false;
            $sql .=  "agentid = ? ";
            $sql .=  ", type = ? ";
            $sql .=  ", name = ? ";
            $sql .=  ", language = ? ";
            $sql .=  ", datelastmodified = now() ";
            $sql .=  ", lastmodifiedbyuid = ? ";

            $sql .= "  WHERE agentnamesid = ? ";
            $this->setlastmodifiedbyuid($SYMB_UID);
        } else {
            $sql  = 'INSERT INTO agentnames ';
            $isInsert = true;
            $sql .= '( agentid ,  type ,  name ,  language ,  timestampcreated ,  createdbyuid ,  datelastmodified ,  lastmodifiedbyuid ) VALUES (';
            $sql .=  "  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  now() ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  null ";
            $sql .=  " ,  null ";
            $sql .= ')';
            $this->createdbyuid = $SYMB_UID;
        }
        if ($statement = $this->conn->prepare($sql)) {
           if ($this->agentnamesid!= NULL ) {
              $statement->bind_param("isssii", $this->agentid , $this->type , $this->name , $this->language , $this->lastmodifiedbyuid , $this->agentnamesid );
           } else {
              $statement->bind_param("isssi", $this->agentid , $this->type , $this->name , $this->language, $this->createdbyuid );
           }
           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $this->error = $statement->error;
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
        }
        if ($this->error=='') {
            $returnvalue = true;
        };

        $this->loaded = true;
        return $returnvalue;
    }

   public function delete() {
        $returnvalue = false;
        if($this->agentnamesid != NULL) {

           $preparedsql = 'SELECT agentid, agentnamesid FROM agentnames WHERE agentnamesid = ?  ' ;
           if ($statement = $this->conn->prepare($preparedsql)) {
               $statement->bind_param("i", $this->agentnamesid);
               $statement->execute();
               $statement->bind_result($foundagentid, $foundagentnameid);
               $statement->store_result();
               if ($statement->num_rows()==1) {
                    $statement->fetch();
                    // Do not allow deletion if only one agent name record exists for this agent.
                    $okToDelete = false;
                    $sql = 'SELECT count(*) FROM agentnames WHERE agentid = ?  ' ;
                    if ($stmt_check = $this->conn->prepare($sql)) {
                       $stmt_check->bind_param("i", $foundagentid);
                       $stmt_check->execute();
                       $stmt_check->bind_result($agentnamecount);
                       $stmt_check->fetch();
                       if ($agentnamecount>1) {
                           $okToDelete = true;
                       }
                       $stmt_check->close();
                    } else {
                       throw new Exception('Unable to identify which record to delete, primary key is not set');
                    }
                    if (!$okToDelete) {
                         $this->error = "Can't delete last remaining name from this Agent.";
                    } else {
                       $sql = 'DELETE FROM agentnames WHERE agentnamesid = ?  ';
                       if ($stmt_delete = $this->conn->prepare($sql)) {
                          $stmt_delete->bind_param("i", $this->agentnamesid);
                          if ($stmt_delete->execute()) {
                              $returnvalue = true;
                          } else {
                              $this->error = mysqli_error($this->conn);
                          }
                          $stmt_delete->close();
                       } else {
                          throw new Exception( "Unable to prepare query $sql " .  mysqli_error($this->conn));
                       }
                    }
               } else {
                   $this->error = mysqli_error($this->conn);
               }
               $statement->close();
           } else {
                throw new Exception( "Unable to prepare query $preparedsql " .  mysqli_error($this->conn));
           }

           $this->loaded = true;
           // record was deleted, so set PK to null
           $this->agentnamesid = NULL;
        } else {
           throw new Exception('Unable to identify which record to delete, primary key is not set');
        }
        return $returnvalue;
    }

}

class agentnamesView
{
   var $model = null;
   public function setModel($aModel) {
       $this->model = $aModel;
   }
   // @param $includeRelated default true shows rows from other tables through foreign key relationships.
   // @param $editLinkURL default '' allows adding a link to show this record in an editing form.
   public function getDetailsView($includeRelated=true, $editLinkURL='') {
       $returnvalue = '<ul>';
       $editLinkURL=trim($editLinkURL);
       $model = $this->model;
       $primarykeys = $model->schemaPK();
       if ($editLinkURL!='') {
          if (!preg_match('/\&$/',$editLinkURL)) { $editLinkURL .= '&'; }
          $nullpk = false;
          foreach ($primarykeys as $primarykey) {
              // Add fieldname=value pairs for primary key(s) to editLinkURL.
              $editLinkURL .= urlencode($primarykey) . '=' . urlencode($model->keyGet($primarykey));
              if ($model->keyGet($primarykey)=='') { $nullpk = true; }
          }
          if (!$nullpk) { $returnvalue .= "<li>agentnames <a href='$editLinkURL'>Edit</a></li>\n";  }
       }
       $returnvalue .= "<li>".agentnames::OMCOLLECTORNAMESID.": ".$model->getagentnamesid()."</li>\n";
       $returnvalue .= "<li>".agentnames::AGENTID.": ".$model->getagentid()."</li>\n";
       $returnvalue .= "<li>".agentnames::TYPE.": ".$model->gettype()."</li>\n";
       $returnvalue .= "<li>".agentnames::NAME.": ".$model->getname()."</li>\n";
       $returnvalue .= "<li>".agentnames::LANGUAGE.": ".$model->getlanguage()."</li>\n";
       $returnvalue .= "<li>".agentnames::TIMESTAMPCREATED.": ".$model->gettimestampcreated()."</li>\n";
       $returnvalue .= "<li>".agentnames::CREATEDBYUID.": ".$model->getcreatedbyuid()."</li>\n";
       $returnvalue .= "<li>".agentnames::DATELASTMODIFIED.": ".$model->getdatelastmodified()."</li>\n";
       $returnvalue .= "<li>".agentnames::LASTMODIFIEDBYUID.": ".$model->getlastmodifiedbyuid()."</li>\n";
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getJSON() {
       $returnvalue = '{ ';
       $model = $this->model;
       $returnvalue .= '"'.agentnames::OMCOLLECTORNAMESID.': "'.$model->getagentnamesid().'",';
       $returnvalue .= '"'.agentnames::AGENTID.': "'.$model->getagentid().'",';
       $returnvalue .= '"'.agentnames::TYPE.': "'.$model->gettype().'",';
       $returnvalue .= '"'.agentnames::NAME.': "'.$model->getname().'",';
       $returnvalue .= '"'.agentnames::LANGUAGE.': "'.$model->getlanguage().'",';
       $returnvalue .= '"'.agentnames::TIMESTAMPCREATED.': "'.$model->gettimestampcreated().'",';
       $returnvalue .= '"'.agentnames::CREATEDBYUID.': "'.$model->getcreatedbyuid().'",';
       $returnvalue .= '"'.agentnames::DATELASTMODIFIED.': "'.$model->getdatelastmodified().'",';
       $returnvalue .= '"'.agentnames::LASTMODIFIEDBYUID.': "'.$model->getlastmodifiedbyuid().'" }';
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getTableRowView() {
       $returnvalue = '<tr>';
       $model = $this->model;
       $returnvalue .= "<td>".$model->getagentnamesid()."</td>\n";
       $returnvalue .= "<td>".$model->getagentid()."</td>\n";
       $returnvalue .= "<td>".$model->gettype()."</td>\n";
       $returnvalue .= "<td>".$model->getname()."</td>\n";
       $returnvalue .= "<td>".$model->getlanguage()."</td>\n";
       $returnvalue .= "<td>".$model->gettimestampcreated()."</td>\n";
       $returnvalue .= "<td>".$model->getcreatedbyuid()."</td>\n";
       $returnvalue .= "<td>".$model->getdatelastmodified()."</td>\n";
       $returnvalue .= "<td>".$model->getlastmodifiedbyuid()."</td>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>".agentnames::OMCOLLECTORNAMESID."</th>\n";
       $returnvalue .= "<th>".agentnames::AGENTID."</th>\n";
       $returnvalue .= "<th>".agentnames::TYPE."</th>\n";
       $returnvalue .= "<th>".agentnames::NAME."</th>\n";
       $returnvalue .= "<th>".agentnames::LANGUAGE."</th>\n";
       $returnvalue .= "<th>".agentnames::TIMESTAMPCREATED."</th>\n";
       $returnvalue .= "<th>".agentnames::CREATEDBYUID."</th>\n";
       $returnvalue .= "<th>".agentnames::DATELASTMODIFIED."</th>\n";
       $returnvalue .= "<th>".agentnames::LASTMODIFIEDBYUID."</th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }

   public function getEditFormView() {
       global $CLIENT_ROOT;
       $model = $this->model;
       if (strlen($model->getagentnamesid())==0) {
          $new = TRUE;
       } else {
          $new = FALSE;
       }
       $returnvalue  .= "
     <script type='text/javascript'>
        var frm = $('#saveNameRecordForm');
        frm.submit(function () {
            $('#nameStatusDiv".$model->getagentnamesid()."').html('Saving...');
            $('#nameResultDiv".$model->getagentnamesid()."').html('');
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: frm.serialize(),
               dataType : 'html',
               success: function(data){
                  $('#nameDetailDiv_".$model->getagentid()."_".$model->getagentnamesid()."').html(data);
                  $('#nameStatusDiv".$model->getagentnamesid()."').html();
               },
               error: function( xhr, status, errorThrown ) {
                  $('#nameStatusDiv".$model->getagentnamesid()."').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
     </script>
";
       $returnvalue .= "<div id='nameStatusDiv".$model->getagentnamesid()."'></div>";
       $returnvalue .= '<form method="POST" id="saveNameRecordForm" action='.$CLIENT_ROOT.'/agents/rpc/handler.php>';
       if ($new) {
          $returnvalue .= '<input type=hidden name=mode id=mode value=savenew>';
       } else {
          $returnvalue .= '<input type=hidden name=mode id=mode value=save>';
       }
       $returnvalue .= '<input type=hidden name=table id=table value="AgentName">';

       $returnvalue .= "<input type=hidden name=".agentnames::OMCOLLECTORNAMESID." id=".agentnames::OMCOLLECTORNAMESID." value='".$model->getagentnamesid()."' >\n";
       $returnvalue .= "<input type=hidden name=".agentnames::AGENTID." id=".agentnames::AGENTID." value='".$model->getagentid()."' >\n";
       $returnvalue .= '<ul>';

       $returnvalue .= "<li>Type of name: <select id=".agentnames::TYPE." name=".agentnames::TYPE." >\n";

       $ct_nt = new ctnametypes();
       $nametypes = $ct_nt->listNameTypes();

       foreach ($nametypes as $nt) {
           if ($model->gettype()==$nt) { $isselected = 'selected'; } else { $isselected = ''; }
           $returnvalue .= "<option value='$nt' $isselected>$nt</option>\n";
       }
       $returnvalue .="</select></li>\n";

       $returnvalue .= "<li>Name<input type=text name=".agentnames::NAME." id=".agentnames::NAME." value='".$model->getname()."'  size='51'  maxlength='".agentnames::NAME_SIZE ."' ></li>\n";
       $returnvalue .= "<li>LANGUAGE<input type=text name=".agentnames::LANGUAGE." id=".agentnames::LANGUAGE." value='".$model->getlanguage()."'  size='".agentnames::LANGUAGE_SIZE ."'  maxlength='".agentnames::LANGUAGE_SIZE ."' ></li>\n";
       $returnvalue .= "<li>TIMESTAMPCREATED ".$model->gettimestampcreated(). "</li>\n";
       $returnvalue .= "<li>CREATEDBYUID ".$model->getcreatedbyuid()."</li>\n";
       $returnvalue .= "<li>DATELASTMODIFIED ".$model->getdatelastmodified()."</li>\n";
       $returnvalue .= "<li>LASTMODIFIEDBYUID ".$model->getlastmodifiedbyuid()."</li>\n";
       $returnvalue .= '<li><input type=submit value="Save"></li>';
       $returnvalue .= '</ul>';
       $returnvalue .= '</form>';
       $returnvalue .= "<div id='nameResultDiv".$model->getagentnamesid()."'></div>";
       return  $returnvalue;
   }
}


class agentnumberpattern
{
   protected $conn;

   private $agentnumberpatternid; // PK BIGINT
   private $agentid; // BIGINT
   private $numbertype; // YEAR
   private $numberpattern; // VARCHAR(255)
   private $numberpatterndescription; // VARCHAR(900)
   private $startyear; // INTEGER
   private $endyear; // INTEGER
   private $integerincrement; // INTEGER
   private $notes; // LONGVARCHAR

   private $dirty;
   private $loaded;
   private $error;

   // These constants hold the sizes the fields in this table in the database.
   const AGENTNUMBERPATTERNID_SIZE = 20; //BIGINT
   const AGENTID_SIZE         = 20; //BIGINT
   const NUMBERTYPE_SIZE      = 20; //YEAR
   const NUMBERPATTERN_SIZE   = 255; //255
   const NUMBERPATTERNDESCRIPTION_SIZE = 900; //900
   const STARTYEAR_SIZE       = 11; //INTEGER
   const ENDYEAR_SIZE         = 11; //INTEGER
   const INTEGERINCREMENT_SIZE = 11; //INTEGER
   const NOTES_SIZE           = 255; //LONGVARCHAR
    // These constants hold the field names of the table in the database.
   const AGENTNUMBERPATTERNID = 'agentnumberpatternid';
   const AGENTID           = 'agentid';
   const NUMBERTYPE        = 'numbertype';
   const NUMBERPATTERN     = 'numberpattern';
   const NUMBERPATTERNDESCRIPTION = 'numberpatterndescription';
   const STARTYEAR         = 'startyear';
   const ENDYEAR           = 'endyear';
   const INTEGERINCREMENT  = 'integerincrement';
   const NOTES             = 'notes';

   //---------------------------------------------------------------------------

   // interface tableSchema implementation
   // schemaPK returns array of primary key field names
   public function schemaPK() {
       return $this->primaryKeyArray;
   }
   // schemaHaveDistinct returns array of field names for which selectDistinct{fieldname} methods are available.
   public function schemaHaveDistinct() {
       return $this->selectDistinctFieldsArray;
   }
   // schemaFields returns array of all field names
   public function schemaFields() {
       return $this->allFieldsArray;
   }

   //---------------------------------------------------------------------------

   const FIELDLIST = ' agentnumberpatternid, agentid, numbertype, numberpattern, numberpatterndescription, startyear, endyear, integerincrement, notes, ';
   const PKFIELDLIST = ' agentnumberpatternid, ';
   const NUMBER_OF_PRIMARY_KEYS = 1;
   private $primaryKeyArray = array( 1 => 'agentnumberpatternid'  ) ;
   private $allFieldsArray = array( 0 => 'agentnumberpatternid' , 1 => 'agentid' , 2 => 'numbertype' , 3 => 'numberpattern' , 4 => 'numberpatterndescription' , 5 => 'startyear' , 6 => 'endyear' , 7 => 'integerincrement' , 8 => 'notes'  ) ;
   private $selectDistinctFieldsArray = array( 1 => 'agentid'  ) ;

   //---------------------------------------------------------------------------

   public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
		$this->agentid = NULL;
		$this->curated = 0;
		$this->living = 'N';
		$this->type = 'Individual';
   }

   // constructor
   function agentnumberpattern(){
       $this->agentnumberpatternid = NULL;
       $this->agentid = '';
       $this->numbertype = '';
       $this->numberpattern = '';
       $this->numberpatterndescription = '';
       $this->startyear = '';
       $this->endyear = '';
       $this->integerincrement = '';
       $this->notes = '';
       $this->dirty = false;
       $this->loaded = false;
       $this->error = '';
   }

   private function l_addslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars_decode($value, ENT_QUOTES);
      return $retval;
   }
   private function l_stripslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars($value, ENT_QUOTES);
      return $retval;
   }
   public function isDirty() {
       return $this->dirty;
   }
   public function isLoaded() {
       return $this->loaded;
   }
   public function errorMessage() {
       return $this->error;
   }

   //---------------------------------------------------------------------------

   public function keyValueSet($fieldname,$value) {
       $returnvalue = false;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentnumberpatternid') { $returnvalue = $this->setagentnumberpatternid($value); }
             if ($fieldname=='agentid') { $returnvalue = $this->setagentid($value); }
             if ($fieldname=='numbertype') { $returnvalue = $this->setnumbertype($value); }
             if ($fieldname=='numberpattern') { $returnvalue = $this->setnumberpattern($value); }
             if ($fieldname=='numberpatterndescription') { $returnvalue = $this->setnumberpatterndescription($value); }
             if ($fieldname=='startyear') { $returnvalue = $this->setstartyear($value); }
             if ($fieldname=='endyear') { $returnvalue = $this->setendyear($value); }
             if ($fieldname=='integerincrement') { $returnvalue = $this->setintegerincrement($value); }
             if ($fieldname=='notes') { $returnvalue = $this->setnotes($value); }
             $returnvalue = true;
          }
          catch (exception $e) { ;
              $returnvalue = false;
              throw new Exception('Field Set Error'.$e->getMessage());
          }
       } else {
          throw new Exception('No Such field');
       }
       return $returnvalue;
   }
   public function keyGet($fieldname) {
       $returnvalue = null;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentnumberpatternid') { $returnvalue = $this->getagentnumberpatternid(); }
             if ($fieldname=='agentid') { $returnvalue = $this->getagentid(); }
             if ($fieldname=='numbertype') { $returnvalue = $this->getnumbertype(); }
             if ($fieldname=='numberpattern') { $returnvalue = $this->getnumberpattern(); }
             if ($fieldname=='numberpatterndescription') { $returnvalue = $this->getnumberpatterndescription(); }
             if ($fieldname=='startyear') { $returnvalue = $this->getstartyear(); }
             if ($fieldname=='endyear') { $returnvalue = $this->getendyear(); }
             if ($fieldname=='integerincrement') { $returnvalue = $this->getintegerincrement(); }
             if ($fieldname=='notes') { $returnvalue = $this->getnotes(); }
          }
          catch (exception $e) { ;
              $returnvalue = null;
          }
       }
       return $returnvalue;
   }
/*agentnumberpatternid*/
   public function getagentnumberpatternid() {
       if ($this->agentnumberpatternid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentnumberpatternid));
       }
   }
   public function setagentnumberpatternid($agentnumberpatternid) {
       if (strlen($agentnumberpatternid) > agentnumberpattern::AGENTNUMBERPATTERNID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->agentnumberpatternid = $this->l_addslashes($agentnumberpatternid);
       $this->dirty = true;
   }
/*agentid*/
   public function getagentid() {
       if ($this->agentid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentid));
       }
   }
   public function setagentid($agentid) {
       if (strlen($agentid) > agentnumberpattern::AGENTID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->agentid = $this->l_addslashes($agentid);
       $this->dirty = true;
   }
/*numbertype*/
   public function getnumbertype() {
       if ($this->numbertype==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->numbertype));
       }
   }
   public function setnumbertype($numbertype) {
       if (strlen($numbertype) > agentnumberpattern::NUMBERTYPE_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->numbertype = $this->l_addslashes($numbertype);
       $this->dirty = true;
   }
/*numberpattern*/
   public function getnumberpattern() {
       if ($this->numberpattern==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->numberpattern));
       }
   }
   public function setnumberpattern($numberpattern) {
       if (strlen($numberpattern) > agentnumberpattern::NUMBERPATTERN_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->numberpattern = $this->l_addslashes($numberpattern);
       $this->dirty = true;
   }
/*numberpatterndescription*/
   public function getnumberpatterndescription() {
       if ($this->numberpatterndescription==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->numberpatterndescription));
       }
   }
   public function setnumberpatterndescription($numberpatterndescription) {
       if (strlen($numberpatterndescription) > agentnumberpattern::NUMBERPATTERNDESCRIPTION_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->numberpatterndescription = $this->l_addslashes($numberpatterndescription);
       $this->dirty = true;
   }
/*startyear*/
   public function getstartyear() {
       if ($this->startyear==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->startyear));
       }
   }
   public function setstartyear($startyear) {
       if (strlen(preg_replace('/[^0-9]/','',$startyear)) > agentnumberpattern::STARTYEAR_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $startyear = trim($startyear);
       if (!ctype_digit(strval($startyear)) && trim(strval($startyear))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->startyear = $this->l_addslashes($startyear);
       $this->dirty = true;
   }
/*endyear*/
   public function getendyear() {
       if ($this->endyear==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->endyear));
       }
   }
   public function setendyear($endyear) {
       if (strlen(preg_replace('/[^0-9]/','',$endyear)) > agentnumberpattern::ENDYEAR_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $endyear = trim($endyear);
       if (!ctype_digit(strval($endyear)) && trim(strval($endyear))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->endyear = $this->l_addslashes($endyear);
       $this->dirty = true;
   }
/*integerincrement*/
   public function getintegerincrement() {
       if ($this->integerincrement==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->integerincrement));
       }
   }
   public function setintegerincrement($integerincrement) {
       if (strlen(preg_replace('/[^0-9]/','',$integerincrement)) > agentnumberpattern::INTEGERINCREMENT_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $integerincrement = trim($integerincrement);
       if (!ctype_digit(strval($integerincrement)) && trim(strval($integerincrement))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->integerincrement = $this->l_addslashes($integerincrement);
       $this->dirty = true;
   }
/*notes*/
   public function getnotes() {
       if ($this->notes==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->notes));
       }
   }
   public function setnotes($notes) {
       if (strlen($notes) > agentnumberpattern::NOTES_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->notes = $this->l_addslashes($notes);
       $this->dirty = true;
   }
   public function PK() { // get value of primary key
        $returnvalue = '';
        $returnvalue .= $this->getagentnumberpatternid();
        return $returnvalue;
   }
   public function PKArray() { // get name and value of primary key fields
        $returnvalue = array();
        $returnvalue['agentnumberpatternid'] = $this->getagentnumberpatternid();
        return $returnvalue;
   }
   public function NumberOfPrimaryKeyFields() { // returns the number of primary key fields defined for this table
        return 1;
   }

   // Constants holding the mysqli field type character (s,i,d) for each field
  const C_agentnumberpatternidMYSQLI_TYPE = 'i';
  const C_agentidMYSQLI_TYPE = 'i';
  const C_numbertypeMYSQLI_TYPE = 's';
  const C_numberpatternMYSQLI_TYPE = 's';
  const C_numberpatterndescriptionMYSQLI_TYPE = 's';
  const C_startyearMYSQLI_TYPE = 'i';
  const C_endyearMYSQLI_TYPE = 'i';
  const C_integerincrementMYSQLI_TYPE = 'i';
  const C_notesMYSQLI_TYPE = 's';

   // function to obtain the mysqli field type character from a fieldname
   public function MySQLiFieldType($aFieldname) {
      $retval = '';
      if ($aFieldname=='agentnumberpatternid') { $retval = self::C_agentnumberpatternidMYSQLI_TYPE; }
      if ($aFieldname=='agentid') { $retval = self::C_agentidMYSQLI_TYPE; }
      if ($aFieldname=='numbertype') { $retval = self::C_numbertypeMYSQLI_TYPE; }
      if ($aFieldname=='numberpattern') { $retval = self::C_numberpatternMYSQLI_TYPE; }
      if ($aFieldname=='numberpatterndescription') { $retval = self::C_numberpatterndescriptionMYSQLI_TYPE; }
      if ($aFieldname=='startyear') { $retval = self::C_startyearMYSQLI_TYPE; }
      if ($aFieldname=='endyear') { $retval = self::C_endyearMYSQLI_TYPE; }
      if ($aFieldname=='integerincrement') { $retval = self::C_integerincrementMYSQLI_TYPE; }
      if ($aFieldname=='notes') { $retval = self::C_notesMYSQLI_TYPE; }
      return $retval;
   }

   // Function load() can take either the value of the primary key which uniquely identifies a particular row
   // or an array of array('primarykeyfieldname'=>'value') in the case of a single field primary key
   // or an array of fieldname value pairs in the case of multiple field primary key.
   public function load($pk) {
        $returnvalue = false;
        try {
             if (is_array($pk)) {
                 $this->setagentnumberpatternid($pk[agentnumberpatternid]);
             } else { ;
                 $this->setagentnumberpatternid($pk);
             };
        }
        catch (Exception $e) {
             throw new Exception($e->getMessage());
        }
        if($this->agentnumberpatternid != NULL) {

           $preparesql = 'SELECT agentnumberpatternid, agentid, numbertype, numberpattern, numberpatterndescription, startyear, endyear, integerincrement, notes FROM agentnumberpattern WHERE agentnumberpatternid = ? ';

           if ($statement = $this->conn->prepare($preparesql)) {
              $statement->bind_param("i", $this->agentnumberpatternid);
              $statement->execute();
              $statement->bind_result($this->agentnumberpatternid, $this->agentid, $this->numbertype, $this->numberpattern, $this->numberpatterndescription, $this->startyear, $this->endyear, $this->integerincrement, $this->notes);
              $statement->fetch();
              $statement->close();
           }

            $this->loaded = true;
            $this->dirty = false;
        } else {
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   // Function save() will either save the current record or insert a new record.
   // Inserts new record if the primary key field in this table is null
   // for this instance of this object.
   // Otherwise updates the record identified by the primary key value.
   public function save() {
        $returnvalue = false;
        // Test to see if this is an insert or update.
        if ($this->agentnumberpatternid!= NULL) {
            $isInsert = false;
            $sql  = 'UPDATE  agentnumberpattern SET ';
            $sql .=  "agentid = ? ";
            $sql .=  ", numbertype = ? ";
            $sql .=  ", numberpattern = ? ";
            $sql .=  ", numberpatterndescription = ? ";
            $sql .=  ", startyear = ? ";
            $sql .=  ", endyear = ? ";
            $sql .=  ", integerincrement = ? ";
            $sql .=  ", notes = ? ";

            $sql .= "  WHERE agentnumberpatternid = ? ";
        } else {
            $isInsert = true;
            $sql  = 'INSERT INTO agentnumberpattern ';
            $sql .= '( agentid ,  numbertype ,  numberpattern ,  numberpatterndescription ,  startyear ,  endyear ,  integerincrement ,  notes ) VALUES (';
            $sql .=  "    ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .= ')';

        }
        if ($statement = $this->conn->prepare($sql)) {
           if ($this->agentnumberpatternid!= NULL ) {
              $statement->bind_param("isssiiisi", $this->agentid , $this->numbertype , $this->numberpattern , $this->numberpatterndescription , $this->startyear , $this->endyear , $this->integerincrement , $this->notes , $this->agentnumberpatternid );
           } else {
              $statement->bind_param("isssiiis", $this->agentid , $this->numbertype , $this->numberpattern , $this->numberpatterndescription , $this->startyear , $this->endyear , $this->integerincrement , $this->notes );
           }
           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $this->error = $statement->error;
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
        }
        if ($this->error=='') {
            $returnvalue = true;
        };

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function delete() {
        $returnvalue = false;
        if($this->agentnumberpatternid != NULL) {

           $preparedsql = 'SELECT agentnumberpatternid FROM agentnumberpattern WHERE agentnumberpatternid = ?  ' ;
            if ($statement = $this->conn->prepare($preparedsql)) {
               $statement->bind_param("i", $this->agentnumberpatternid);
               $statement->execute();
               $statement->store_result();
               if ($statement->num_rows()==1) {
                    $sql = 'DELETE FROM agentnumberpattern WHERE agentnumberpatternid = ?  ';
                    if ($stmt_delete = $this->conn->prepare($sql)) {
                       $stmt_delete->bind_param("i", $this->agentnumberpatternid);
                       if ($stmt_delete->execute()) {
                           $returnvalue = true;
                       } else {
                           $this->error = mysqli_error($this->conn);
                       }
                       $stmt_delete->close();
                    }
               } else {
                   $this->error = mysqli_error($this->conn);
               }
               $statement->close();
            } else {
                $this->error = mysqli_error($this->conn);
            }

            $this->loaded = true;
            // record was deleted, so set PK to null
            $this->agentnumberpatternid = NULL;
        } else {
           throw new Exception('Unable to identify which record to delete, primary key is not set');
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function count() {
        $returnvalue = false;
        $sql = 'SELECT count(*)  FROM agentnumberpattern';
        if ($result = $this->conn->query($sql)) {
           if ($result->num_rows()==1) {
             $row = $result->fetch_row();
             if ($row) {
                $returnvalue = $row[0];
             }
           }
        } else {
           $this->error = mysqli_error($this->conn);
        }
        mysqli_free_result($result);

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function loadArrayKeyValueSearch($searchTermArray) {
       $returnvalue = array();
       $and = '';
       $wherebit = 'WHERE ';
       foreach($searchTermArray as $fieldname => $searchTerm) {
           if ($this->hasField($fieldname)) {
               $operator = '=';
               // change to a like search if a wildcard character is present
               if (!(strpos($searchTerm,'%')===false)) { $operator = 'like'; }
               if (!(strpos($searchTerm,'_')===false)) { $operator = 'like'; }
               if ($searchTerm=='[NULL]') {
                   $wherebit .= "$and ($fieldname is null or $fieldname='') ";
               } else {
                   $wherebit .= "$and $fieldname $operator ? ";
                   $types = $types . $this->MySQLiFieldType($fieldname);
               }
               $and = ' and ';
           }
       }
       $sql = "SELECT agentnumberpatternid FROM agentnumberpattern $wherebit";
       if ($wherebit=='') {
             $this->error = 'Error: No search terms provided';
       } else {
          $statement = $this->conn->prepare($sql);
          $vars = Array();
          $vars[] = $types;
          $i = 0;
          foreach ($searchTermArray as $value) {
               $varname = 'bind'.$i;  // create a variable name
               $$varname = $value;    // using that variable name store the value
               $vars[] = &$$varname;  // add a reference to the variable to the array
               $i++;
           }
           //$vars[] contains $types followed by references to variables holding each value in $searchTermArray.
          call_user_func_array(array($statement,'bind_param'),$vars);
          //$statement->bind_param($types,$names);
          $statement->execute();
          $statement->bind_result($id);
          $ids = array();
          while ($statement->fetch()) {
              $ids[] = $id;
          } // double loop to allow all data to be retrieved before preparing a new statement.
          $statement->close();
          for ($i=0;$i<count($ids);$i++) {
              $obj = new agentnumberpattern();
              $obj->load($ids[$i]);
              $returnvalue[] = $obj;
              $result=true;
          }
          if ($result===false) { $this->error = mysqli_error($this->conn); }
       }
       return $returnvalue;
   }

   //---------------------------------------------------------------------------

   // Each field with an index has a load array method generated for it.
   public function loadArrayByagentid($searchTerm) {
        $returnvalue = array();
        $operator = "=";
        // change to a like search if a wildcard character is present
        if (!(strpos($searchTerm,"%")===false)) { $operator = "like"; }
        if (!(strpos($searchTerm,"_")===false)) { $operator = "like"; }
        $sql = "SELECT agentnumberpatternid FROM agentnumberpattern WHERE agentid $operator '$searchTerm'";
        $preparedsql = "SELECT agentnumberpatternid FROM agentnumberpattern WHERE agentid $operator ? ";
        if ($statement = $this->conn->prepare($preparedsql)) {
            $statement->bind_param("s", $searchTerm);
            $statement->execute();
            $statement->bind_result($id);
            while ($statement->fetch()) { ;
                $obj = new agentnumberpattern();
                $obj->load($id);
                $returnvalue[] = $obj;
            }
            $statement->close();
        }
        return $returnvalue;
   }

   //---------------------------------------------------------------------------

   public function hasField($fieldname) {
       $returnvalue = false;
       if (trim($fieldname)!='' && trim($fieldname)!=',') {
            if (strpos(self::FIELDLIST," $fieldname, ")!==false) {
               $returnvalue = true;
            }
       }
       return $returnvalue;
    }
   //---------------------------------------------------------------------------

}


class agentnumberpatternView
{
   var $model = null;
   public function setModel($aModel) {
       $this->model = $aModel;
   }
   // @param $includeRelated default true shows rows from other tables through foreign key relationships.
   // @param $editLinkURL default '' allows adding a link to show this record in an editing form.
   public function getDetailsView($includeRelated=false, $editLinkURL='') {
       $returnvalue = '<ul>';
       $editLinkURL=trim($editLinkURL);
       $model = $this->model;
       $primarykeys = $model->schemaPK();
       if ($editLinkURL!='') {
          if (!preg_match('/\&$/',$editLinkURL)) { $editLinkURL .= '&'; }
          $nullpk = false;
          foreach ($primarykeys as $primarykey) {
              // Add fieldname=value pairs for primary key(s) to editLinkURL.
              $editLinkURL .= urlencode($primarykey) . '=' . urlencode($model->keyGet($primarykey));
              if ($model->keyGet($primarykey)=='') { $nullpk = true; }
          }
          if (!$nullpk) { $returnvalue .= "<li>agentnumberpattern <a href='$editLinkURL'>Edit</a></li>\n";  }
       }
       $returnvalue .= "<li>".agentnumberpattern::AGENTNUMBERPATTERNID.": ".$model->getagentnumberpatternid()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::AGENTID.": ".$model->getagentid()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::NUMBERTYPE.": ".$model->getnumbertype()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::NUMBERPATTERN.": ".$model->getnumberpattern()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::NUMBERPATTERNDESCRIPTION.": ".$model->getnumberpatterndescription()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::STARTYEAR.": ".$model->getstartyear()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::ENDYEAR.": ".$model->getendyear()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::INTEGERINCREMENT.": ".$model->getintegerincrement()."</li>\n";
       $returnvalue .= "<li>".agentnumberpattern::NOTES.": ".$model->getnotes()."</li>\n";
       if ($includeRelated) {

       }
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getJSON() {
       $returnvalue = '{ ';
       $model = $this->model;
       $returnvalue .= '"'.agentnumberpattern::AGENTNUMBERPATTERNID.': "'.$model->getagentnumberpatternid().'",';
       $returnvalue .= '"'.agentnumberpattern::AGENTID.': "'.$model->getagentid().'",';
       $returnvalue .= '"'.agentnumberpattern::NUMBERTYPE.': "'.$model->getnumbertype().'",';
       $returnvalue .= '"'.agentnumberpattern::NUMBERPATTERN.': "'.$model->getnumberpattern().'",';
       $returnvalue .= '"'.agentnumberpattern::NUMBERPATTERNDESCRIPTION.': "'.$model->getnumberpatterndescription().'",';
       $returnvalue .= '"'.agentnumberpattern::STARTYEAR.': "'.$model->getstartyear().'",';
       $returnvalue .= '"'.agentnumberpattern::ENDYEAR.': "'.$model->getendyear().'",';
       $returnvalue .= '"'.agentnumberpattern::INTEGERINCREMENT.': "'.$model->getintegerincrement().'",';
       $returnvalue .= '"'.agentnumberpattern::NOTES.': "'.$model->getnotes().'" }';
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getTableRowView() {
       $returnvalue = '<tr>';
       $model = $this->model;
       $returnvalue .= "<td>".$model->getagentnumberpatternid()."</td>\n";
       $returnvalue .= "<td>".$model->getagentid()."</td>\n";
       $returnvalue .= "<td>".$model->getnumbertype()."</td>\n";
       $returnvalue .= "<td>".$model->getnumberpattern()."</td>\n";
       $returnvalue .= "<td>".$model->getnumberpatterndescription()."</td>\n";
       $returnvalue .= "<td>".$model->getstartyear()."</td>\n";
       $returnvalue .= "<td>".$model->getendyear()."</td>\n";
       $returnvalue .= "<td>".$model->getintegerincrement()."</td>\n";
       $returnvalue .= "<td>".$model->getnotes()."</td>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>".agentnumberpattern::AGENTNUMBERPATTERNID."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::AGENTID."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::NUMBERTYPE."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::NUMBERPATTERN."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::NUMBERPATTERNDESCRIPTION."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::STARTYEAR."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::ENDYEAR."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::INTEGERINCREMENT."</th>\n";
       $returnvalue .= "<th>".agentnumberpattern::NOTES."</th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }

   public function getSummaryLine($editable=false) {
       global $CLIENT_ROOT;
       $model = $this->model;
       $id = $model->getagentnumberpatternid();
       $returnvalue = "<span id='numpatDetailDiv$id'>";
       $returnvalue .= $model->getnumbertype()." ";
       $returnvalue .= $model->getnumberpattern()." (";
       $returnvalue .= $model->getstartyear()."-";
       $returnvalue .= $model->getendyear().") ";
       $returnvalue .= $model->getnumberpatterndescription()." ";
       if ($editable) {
          $returnvalue .= " <a id='editNPLink$id'>Edit</a> <a id='deleteNPLink$id'>Delete</a>";
          $returnvalue .= "
     <script type='text/javascript'>
        function handlerNPEdit$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=edit&table=AgentNumberPattern&agentnumberpatternid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#numpatDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#editNPLink$id').bind('click',handlerNPEdit$id);
        function handlerNPDelete$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=delete&table=AgentNumberPattern&agentnumberpatternid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#numpatDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#deleteNPLink$id').bind('click',handlerNPDelete$id);
     </script>
                ";
       }
       $returnvalue .= '</span>';
       return  $returnvalue;

   }

   public function getShortTableRowView() {
       global $CLIENT_ROOT;
       $model = $this->model;
       $id = $model->getagentnumberpatternid();
       $returnvalue = "<tr><div id='numpatDetailDiv$id'>";
       $returnvalue .= "<td>".$model->getnumbertype()."</td>\n";
       $returnvalue .= "<td>".$model->getnumberpattern()."</td>\n";
       $returnvalue .= "<td>".$model->getnumberpatterndescription()."</td>\n";
       $returnvalue .= "<td>".$model->getstartyear()."</td>\n";
       $returnvalue .= "<td>".$model->getendyear()."</td>\n";
       $returnvalue .= "<td><a id='editNPLink$id'>Edit</a> <a id='deleteNPLink$id'>Delete</a></td>";
       $returnvalue .= "
     <script type='text/javascript'>
        function handlerNPEdit$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=edit&table=AgentNumberPattern&agentnumberpatternid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#numpatDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#editNPLink$id').bind('click',handlerNPEdit$id);
        function handlerNPDelete$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=delete&table=AgentNumberPattern&agentnumberpatternid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#numpatDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#deleteNPLink$id').bind('click',handlerNPDelete$id);
     </script>
                ";

       $returnvalue .= '</div></tr>';
       return  $returnvalue;
   }
   public function getShortHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>Type</th>\n";
       $returnvalue .= "<th>Pattern</th>\n";
       $returnvalue .= "<th>Description</th>\n";
       $returnvalue .= "<th>Used From</th>\n";
       $returnvalue .= "<th>To</th>\n";
       $returnvalue .= "<th></th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getEditFormView($includeRelated=false) {
       global $CLIENT_ROOT;
       $model = $this->model;

       if (strlen($model->getagentid())==0) {
          $new = TRUE;
       } else {
          $new = FALSE;
       }
       $returnvalue  = "
     <script type='text/javascript'>
        var frm = $('#saveANRecordForm');
        frm.submit(function () {
            $('#statusDiv').html('Saving...');
            $('#saveANresultDiv').html('');
            $.ajax({
               type: 'POST',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: frm.serialize(),
               dataType : 'html',
               success: function(data){
                  $('#saveANresultDiv').html(data);
                  $('#statusDiv').html('');
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
     </script>
";
       $returnvalue .= "<div id='saveANresultDiv'>";
       $returnvalue .= '</div>';
       $returnvalue .= "<div id='statusDiv'>";
       $returnvalue .= '</div>';
       $returnvalue .= '<form id="saveANRecordForm">';
       if ($new) {
          $returnvalue .= '<input type=hidden name=mode id=mode value=savenew>';
       } else {
          $returnvalue .= '<input type=hidden name=mode id=mode value=save>';
       }
       $returnvalue .= '<input type=hidden name=table id=table value="AgentNumberPattern">';
       $returnvalue .= "<input type=hidden name='agentnumberpatternid' id='agentnumberpatternid' value='".$model->getagentnumberpatternid()."' >\n";
       $returnvalue .= "<input type=hidden name='agentid' id='agentid' value='".$model->getagentid()."' >\n";
       $returnvalue .= '<ul>';
       $cnselected = "selected";
       if ($model->getnumbertype()=='Collector number') { $cnselected = "selected"; $snselected = ""; }
       if ($model->getnumbertype()=='Site number') {      $cnselected = "";  $snselected = "selected"; }
       $returnvalue .= "<li>Number Type: <select id=".agentnumberpattern::NUMBERTYPE." name=".agentnumberpattern::NUMBERTYPE." >\n";
       $returnvalue .= "<option value='Collector number' $cnselected>Collector Number</option>\n";
       $returnvalue .= "<option value='Site number' $snselected>Site Number</option>\n";
       $returnvalue .= "</select></li>\n";
       $returnvalue .= "<li>NUMBERPATTERN<input type=text name=".agentnumberpattern::NUMBERPATTERN." id=".agentnumberpattern::NUMBERPATTERN." value='".$model->getnumberpattern()."'  size='51'  maxlength='".agentnumberpattern::NUMBERPATTERN_SIZE ."' ></li>\n";
       $returnvalue .= "<li>NUMBERPATTERNDESCRIPTION<input type=text name=".agentnumberpattern::NUMBERPATTERNDESCRIPTION." id=".agentnumberpattern::NUMBERPATTERNDESCRIPTION." value='".$model->getnumberpatterndescription()."'  size='51'  maxlength='".agentnumberpattern::NUMBERPATTERNDESCRIPTION_SIZE ."' ></li>\n";
       $returnvalue .= "<li>STARTYEAR<input type=text name=".agentnumberpattern::STARTYEAR." id=".agentnumberpattern::STARTYEAR." value='".$model->getstartyear()."'  size='".agentnumberpattern::STARTYEAR_SIZE ."'  maxlength='".agentnumberpattern::STARTYEAR_SIZE ."' ></li>\n";
       $returnvalue .= "<li>ENDYEAR<input type=text name=".agentnumberpattern::ENDYEAR." id=".agentnumberpattern::ENDYEAR." value='".$model->getendyear()."'  size='".agentnumberpattern::ENDYEAR_SIZE ."'  maxlength='".agentnumberpattern::ENDYEAR_SIZE ."' ></li>\n";
       $yselected = "";
       $nselected = "selected";
       $qselected = "";
       if ($model->getintegerincrement()=='1') { $yselected = "selected"; $nselected = "";  }
       if ($model->getintegerincrement()=='0') { $yselected = ""; $nselected = "selected";  }
       $returnvalue .= "<li>Increments by one? <select id=".agentnumberpattern::INTEGERINCREMENT." name=".agentnumberpattern::INTEGERINCREMENT." >\n";
       $returnvalue .= "<option value='1' $yselected>Yes</option>\n";
       $returnvalue .= "<option value='0' $nselected>No</option>\n";
       $returnvalue .= "</select></li>\n";
       $returnvalue .= "<li>NOTES<input type=text name=".agentnumberpattern::NOTES." id=".agentnumberpattern::NOTES." value='".$model->getnotes()."'  size='51'  maxlength='".agentnumberpattern::NOTES_SIZE ."' ></li>\n";
       if ($includeRelated) {

        }
       $returnvalue .= '<li><input type=submit value="Save"></li>';
       $returnvalue .= '</ul>';
       $returnvalue .= '</form>';
       return  $returnvalue;
   }
}


class agentlinks {
   protected $conn;

   // These constants hold the sizes the fields in this table in the database.
   const AGENTLINKSID_SIZE    = 20; //BIGINT
   const AGENTID_SIZE         = 20; //BIGINT
   const TYPE_SIZE            = 50; //50
   const LINK_SIZE            = 900; //900
   const ISPRIMARYTOPICOF_SIZE = 1; //BIT
   const TEXT_SIZE            = 50; //50
   const TIMESTAMPCREATED_SIZE = 21; //TIMESTAMP
   const CREATEDBYUID_SIZE    = 11; //INTEGER
   const DATELASTMODIFIED_SIZE = 21; //TIMESTAMP
   const LASTMODIFIEDBYUID_SIZE = 11; //INTEGER

   const AGENTLINKSID      = 'agentlinksid';
   const AGENTID           = 'agentid';
   const TYPE              = 'type';
   const LINK              = 'link';
   const ISPRIMARYTOPICOF  = 'isprimarytopicof';
   const TEXT              = 'text';
   const TIMESTAMPCREATED  = 'timestampcreated';
   const CREATEDBYUID      = 'createdbyuid';
   const DATELASTMODIFIED  = 'datelastmodified';
   const LASTMODIFIEDBYUID = 'lastmodifiedbyuid';

   private $agentlinksid; // PK BIGINT
   private $agentid; // BIGINT
   private $type; // CHAR(50)
   private $link; // VARCHAR(900)
   private $isprimarytopicof; // BIT
   private $text; // CHAR(50)
   private $timestampcreated; // TIMESTAMP
   private $createdbyuid; // INTEGER
   private $datelastmodified; // TIMESTAMP
   private $lastmodifiedbyuid; // INTEGER
   private $dirty;
   private $loaded;
   private $error;
   const FIELDLIST = ' agentlinksid, agentid, type, link, isprimarytopicof, text, timestampcreated, createdbyuid, datelastmodified, lastmodifiedbyuid, ';
   const PKFIELDLIST = ' agentlinksid, ';
   const NUMBER_OF_PRIMARY_KEYS = 1;
   private $primaryKeyArray = array( 1 => 'agentlinksid'  ) ;
   private $allFieldsArray = array( 0 => 'agentlinksid' , 1 => 'agentid' , 2 => 'type' , 3 => 'link' , 4 => 'isprimarytopicof' , 5 => 'text' , 6 => 'timestampcreated' , 7 => 'createdbyuid' , 8 => 'datelastmodified' , 9 => 'lastmodifiedbyuid'  ) ;
   private $selectDistinctFieldsArray = array(  ) ;

   //---------------------------------------------------------------------------
	public function __construct(){
	   $this->conn = MySQLiConnectionFactory::getCon('write');
	   $this->agentid = NULL;
       $this->agentlinksid = NULL;
       $this->agentid = '';
       $this->type = '';
       $this->link = '';
       $this->isprimarytopicof = '';
       $this->text = '';
       $this->timestampcreated = '';
       $this->createdbyuid = '';
       $this->datelastmodified = '';
       $this->lastmodifiedbyuid = '';
       $this->dirty = false;
       $this->loaded = false;
       $this->error = '';
   }

   private function l_addslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars_decode($value, ENT_QUOTES);
      return $retval;
   }
   private function l_stripslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars($value, ENT_QUOTES);
      return $retval;
   }
   public function isDirty() {
       return $this->dirty;
   }
   public function isLoaded() {
       return $this->loaded;
   }
   public function errorMessage() {
       return $this->error;
   }

   public function hasField($fieldname) {
       $returnvalue = false;
       if (trim($fieldname)!='' && trim($fieldname)!=',') {
            if (strpos(self::FIELDLIST," $fieldname, ")!==false) {
               $returnvalue = true;
            }
       }
       return $returnvalue;
    }

   // Constants holding the mysqli field type character (s,i,d) for each field
  const C_agentlinksidMYSQLI_TYPE = 'i';
  const C_agentidMYSQLI_TYPE = 'i';
  const C_typeMYSQLI_TYPE = 's';
  const C_linkMYSQLI_TYPE = 's';
  const C_isprimarytopicofMYSQLI_TYPE = 'i';
  const C_textMYSQLI_TYPE = 's';
  const C_timestampcreatedMYSQLI_TYPE = 's';
  const C_createdbyuidMYSQLI_TYPE = 'i';
  const C_datelastmodifiedMYSQLI_TYPE = 's';
  const C_lastmodifiedbyuidMYSQLI_TYPE = 'i';

   // function to obtain the mysqli field type character from a fieldname
   public function MySQLiFieldType($aFieldname) {
      $retval = '';
      if ($aFieldname=='agentlinksid') { $retval = self::C_agentlinksidMYSQLI_TYPE; }
      if ($aFieldname=='agentid') { $retval = self::C_agentidMYSQLI_TYPE; }
      if ($aFieldname=='type') { $retval = self::C_typeMYSQLI_TYPE; }
      if ($aFieldname=='link') { $retval = self::C_linkMYSQLI_TYPE; }
      if ($aFieldname=='isprimarytopicof') { $retval = self::C_isprimarytopicofMYSQLI_TYPE; }
      if ($aFieldname=='text') { $retval = self::C_textMYSQLI_TYPE; }
      if ($aFieldname=='timestampcreated') { $retval = self::C_timestampcreatedMYSQLI_TYPE; }
      if ($aFieldname=='createdbyuid') { $retval = self::C_createdbyuidMYSQLI_TYPE; }
      if ($aFieldname=='datelastmodified') { $retval = self::C_datelastmodifiedMYSQLI_TYPE; }
      if ($aFieldname=='lastmodifiedbyuid') { $retval = self::C_lastmodifiedbyuidMYSQLI_TYPE; }
      return $retval;
   }


   //---------------------------------------------------------------------------

   public function keyValueSet($fieldname,$value) {
       $returnvalue = false;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentlinksid') { $returnvalue = $this->setagentlinksid($value); }
             if ($fieldname=='agentid') { $returnvalue = $this->setagentid($value); }
             if ($fieldname=='type') { $returnvalue = $this->settype($value); }
             if ($fieldname=='link') { $returnvalue = $this->setlink($value); }
             if ($fieldname=='isprimarytopicof') { $returnvalue = $this->setisprimarytopicof($value); }
             if ($fieldname=='text') { $returnvalue = $this->settext($value); }
             if ($fieldname=='timestampcreated') { $returnvalue = $this->settimestampcreated($value); }
             if ($fieldname=='createdbyuid') { $returnvalue = $this->setcreatedbyuid($value); }
             if ($fieldname=='datelastmodified') { $returnvalue = $this->setdatelastmodified($value); }
             if ($fieldname=='lastmodifiedbyuid') { $returnvalue = $this->setlastmodifiedbyuid($value); }
             $returnvalue = true;
          }
          catch (exception $e) { ;
              $returnvalue = false;
              throw new Exception('Field Set Error'.$e->getMessage());
          }
       } else {
          throw new Exception('No Such field');
       }
       return $returnvalue;
   }
   public function keyGet($fieldname) {
       $returnvalue = null;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentlinksid') { $returnvalue = $this->getagentlinksid(); }
             if ($fieldname=='agentid') { $returnvalue = $this->getagentid(); }
             if ($fieldname=='type') { $returnvalue = $this->gettype(); }
             if ($fieldname=='link') { $returnvalue = $this->getlink(); }
             if ($fieldname=='isprimarytopicof') { $returnvalue = $this->getisprimarytopicof(); }
             if ($fieldname=='text') { $returnvalue = $this->gettext(); }
             if ($fieldname=='timestampcreated') { $returnvalue = $this->gettimestampcreated(); }
             if ($fieldname=='createdbyuid') { $returnvalue = $this->getcreatedbyuid(); }
             if ($fieldname=='datelastmodified') { $returnvalue = $this->getdatelastmodified(); }
             if ($fieldname=='lastmodifiedbyuid') { $returnvalue = $this->getlastmodifiedbyuid(); }
          }
          catch (exception $e) { ;
              $returnvalue = null;
          }
       }
       return $returnvalue;
   }
/*agentlinksid*/
   public function getagentlinksid() {
       if ($this->agentlinksid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentlinksid));
       }
   }
   public function setagentlinksid($agentlinksid) {
       if (strlen($agentlinksid) > agentlinks::AGENTLINKSID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->agentlinksid = $this->l_addslashes($agentlinksid);
       $this->dirty = true;
   }
/*agentid*/
   public function getagentid() {
       if ($this->agentid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentid));
       }
   }
   public function setagentid($agentid) {
       if (strlen($agentid) > agentlinks::AGENTID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->agentid = $this->l_addslashes($agentid);
       $this->dirty = true;
   }
/*type*/
   public function gettype() {
       if ($this->type==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->type));
       }
   }
   public function settype($type) {
       if (strlen($type) > agentlinks::TYPE_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->type = $this->l_addslashes($type);
       $this->dirty = true;
   }
/*link*/
   public function getlink() {
       if ($this->link==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->link));
       }
   }
   public function setlink($link) {
       if (strlen($link) > agentlinks::LINK_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->link = $this->l_addslashes($link);
       $this->dirty = true;
   }
/*isprimarytopicof*/
   public function getisprimarytopicof() {
       if ($this->isprimarytopicof==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->isprimarytopicof));
       }
   }
   public function setisprimarytopicof($isprimarytopicof) {
       if (strlen($isprimarytopicof) > agentlinks::ISPRIMARYTOPICOF_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->isprimarytopicof = $this->l_addslashes($isprimarytopicof);
       $this->dirty = true;
   }
/*text*/
   public function gettext() {
       if ($this->text==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->text));
       }
   }
   public function settext($text) {
       if (strlen($text) > agentlinks::TEXT_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->text = $this->l_addslashes($text);
       $this->dirty = true;
   }
/*timestampcreated*/
   public function gettimestampcreated() {
       if ($this->timestampcreated==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->timestampcreated));
       }
   }
   public function settimestampcreated($timestampcreated) {
       if (strlen($timestampcreated) > agentlinks::TIMESTAMPCREATED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->timestampcreated = $this->l_addslashes($timestampcreated);
       $this->dirty = true;
   }
/*createdbyuid*/
   public function getcreatedbyuid() {
       if ($this->createdbyuid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->createdbyuid));
       }
   }
   public function setcreatedbyuid($createdbyuid) {
       if (strlen(preg_replace('/[^0-9]/','',$createdbyuid)) > agentlinks::CREATEDBYUID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $createdbyuid = trim($createdbyuid);
       if (!ctype_digit(strval($createdbyuid))) {
             throw new Exception("Value must be an integer");
       }
       $this->createdbyuid = $this->l_addslashes($createdbyuid);
       $this->dirty = true;
   }
/*datelastmodified*/
   public function getdatelastmodified() {
       if ($this->datelastmodified==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->datelastmodified));
       }
   }
   public function setdatelastmodified($datelastmodified) {
       if (strlen($datelastmodified) > agentlinks::DATELASTMODIFIED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->datelastmodified = $this->l_addslashes($datelastmodified);
       $this->dirty = true;
   }
/*lastmodifiedbyuid*/
   public function getlastmodifiedbyuid() {
       if ($this->lastmodifiedbyuid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->lastmodifiedbyuid));
       }
   }
   public function setlastmodifiedbyuid($lastmodifiedbyuid) {
       if (strlen(preg_replace('/[^0-9]/','',$lastmodifiedbyuid)) > agentlinks::LASTMODIFIEDBYUID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $lastmodifiedbyuid = trim($lastmodifiedbyuid);
       if (!ctype_digit(strval($lastmodifiedbyuid)) && trim(strval($lastmodifiedbyuid))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->lastmodifiedbyuid = $this->l_addslashes($lastmodifiedbyuid);
       $this->dirty = true;
   }
   public function PK() { // get value of primary key
        $returnvalue = '';
        $returnvalue .= $this->getagentlinksid();
        return $returnvalue;
   }
   public function PKArray() { // get name and value of primary key fields
        $returnvalue = array();
        $returnvalue['agentlinksid'] = $this->getagentlinksid();
        return $returnvalue;
   }
   public function NumberOfPrimaryKeyFields() { // returns the number of primary key fields defined for this table
        return 1;
   }

   public function load($pk) {
        $returnvalue = false;
        try {
           $this->setagentlinksid($pk);
        }
        catch (Exception $e) {
             throw new Exception($e->getMessage());
        }
        if($this->agentlinksid != NULL) {

           $preparesql = 'SELECT agentlinksid, agentid, type, link, isprimarytopicof, text, timestampcreated, createdbyuid, datelastmodified, lastmodifiedbyuid FROM agentlinks WHERE agentlinksid = ? ';

           if ($statement = $this->conn->prepare($preparesql)) {
              $statement->bind_param("i", $this->agentlinksid);
              $statement->execute();
              $statement->bind_result($this->agentlinksid, $this->agentid, $this->type, $this->link, $this->isprimarytopicof, $this->text, $this->timestampcreated, $this->createdbyuid, $this->datelastmodified, $this->lastmodifiedbyuid);
              $statement->fetch();
              $statement->close();
           }

            $this->loaded = true;
            $this->dirty = false;
        } else {
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   // Function save() will either save the current record or insert a new record.
   // Inserts new record if the primary key field in this table is null
   // for this instance of this object.
   // Otherwise updates the record identified by the primary key value.
   public function save() {
        $returnvalue = false;
        // Test to see if this is an insert or update.
        if ($this->agentlinksid!= NULL) {
            $sql  = 'UPDATE  agentlinks SET ';
            $isInsert = false;
            $sql .=  "agentid = ? ";
            $sql .=  ", type = ? ";
            $sql .=  ", link = ? ";
            $sql .=  ", isprimarytopicof = ? ";
            $sql .=  ", text = ? ";
            $sql .=  ", timestampcreated = ? ";
            $sql .=  ", createdbyuid = ? ";
            $sql .=  ", datelastmodified = ? ";
            $sql .=  ", lastmodifiedbyuid = ? ";

            $sql .= "  WHERE agentlinksid = ? ";
        } else {
            $sql  = 'INSERT INTO agentlinks ';
            $isInsert = true;
            $sql .= '(  agentid ,  type ,  link ,  isprimarytopicof ,  text ,  timestampcreated ,  createdbyuid ,  datelastmodified ,  lastmodifiedbyuid ) VALUES (';
            $sql .=  "  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .= ')';

        }
        if ($statement = $this->conn->prepare($sql)) {
           if ($this->agentlinksid!= NULL ) {
              $statement->bind_param("ississisii",  $this->agentid , $this->type , $this->link , $this->isprimarytopicof , $this->text , $this->timestampcreated , $this->createdbyuid , $this->datelastmodified , $this->lastmodifiedbyuid , $this->agentlinksid );
           } else {
              $statement->bind_param("ississisi",  $this->agentid , $this->type , $this->link , $this->isprimarytopicof , $this->text , $this->timestampcreated , $this->createdbyuid , $this->datelastmodified , $this->lastmodifiedbyuid );
           }
           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $this->error = $statement->error;
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
        }
        if ($this->error=='') {
            $returnvalue = true;
        };

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function delete() {
        $returnvalue = false;
        if($this->agentlinksid != NULL) {

           $preparedsql = 'SELECT agentlinksid FROM agentlinks WHERE agentlinksid = ?  ' ;
            if ($statement = $this->conn->prepare($preparedsql)) {
               $statement->bind_param("i", $this->agentlinksid);
               $statement->execute();
               $statement->store_result();
               if ($statement->num_rows()==1) {
                    $sql = 'DELETE FROM agentlinks WHERE agentlinksid = ?  ';
                    if ($stmt_delete = $this->conn->prepare($sql)) {
                       $stmt_delete->bind_param("i", $this->agentlinksid);
                       if ($stmt_delete->execute()) {
                           $returnvalue = true;
                       } else {
                           $this->error = mysqli_error($this->conn);
                       }
                       $stmt_delete->close();
                    }
               } else {
                   $this->error = mysqli_error($this->conn);
               }
               $statement->close();
            } else {
                $this->error = mysqli_error($this->conn);
            }

            $this->loaded = true;
            // record was deleted, so set PK to null
            $this->agentlinksid = NULL;
        } else {
           throw new Exception('Unable to identify which record to delete, primary key is not set');
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function count() {
        $returnvalue = false;
        $sql = 'SELECT count(*)  FROM agentlinks';
        if ($result = $this->conn->query($sql)) {
           if ($result->num_rows()==1) {
             $row = $result->fetch_row();
             if ($row) {
                $returnvalue = $row[0];
             }
           }
        } else {
           $this->error = mysqli_error($this->conn);
        }
        mysqli_free_result($result);

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function loadArrayByagentid($agentid) {
      $search = Array();
      $search['agentid'] = $agentid;
      return $this->loadArrayKeyValueSearch($search);
   }

   public function loadArrayKeyValueSearch($searchTermArray) {
       $returnvalue = array();
       $and = '';
       $wherebit = 'WHERE ';
       foreach($searchTermArray as $fieldname => $searchTerm) {
           if ($this->hasField($fieldname)) {
               $operator = '=';
               // change to a like search if a wildcard character is present
               if (!(strpos($searchTerm,'%')===false)) { $operator = 'like'; }
               if (!(strpos($searchTerm,'_')===false)) { $operator = 'like'; }
               if ($searchTerm=='[NULL]') {
                   $wherebit .= "$and ($fieldname is null or $fieldname='') ";
               } else {
                   $wherebit .= "$and $fieldname $operator ? ";
                   $types = $types . $this->MySQLiFieldType($fieldname);
               }
               $and = ' and ';
           }
       }
       $sql = "SELECT agentlinksid FROM agentlinks $wherebit";
       if ($wherebit=='') {
             $this->error = 'Error: No search terms provided';
       } else {
          $statement = $this->conn->prepare($sql);
          $vars = Array();
          $vars[] = $types;
          $i = 0;
          foreach ($searchTermArray as $value) {
               $varname = 'bind'.$i;  // create a variable name
               $$varname = $value;    // using that variable name store the value
               $vars[] = &$$varname;  // add a reference to the variable to the array
               $i++;
           }
           //$vars[] contains $types followed by references to variables holding each value in $searchTermArray.
          call_user_func_array(array($statement,'bind_param'),$vars);
          //$statement->bind_param($types,$names);
          $statement->execute();
          $statement->bind_result($id);
          $ids = array();
          while ($statement->fetch()) {
              $ids[] = $id;
          } // double loop to allow all data to be retrieved before preparing a new statement.
          $statement->close();
          for ($i=0;$i<count($ids);$i++) {
              $obj = new agentlinks();
              $obj->load($ids[$i]);
              $returnvalue[] = $obj;
              $result=true;
          }
          if ($result===false) { $this->error = mysqli_error($this->conn); }
       }
       return $returnvalue;
   }

   //---------------------------------------------------------------------------

  public function loadLinkedTo() {
     $returnvalue = array();
       // fk: agentid
      $t = new agents();
      $t->load(getagentid());
      $returnvalue[agentid] = $t;
     return $returnvalue;
  }

}

class agentlinksView
{
   var $model = null;
   public function setModel($aModel) {
       $this->model = $aModel;
   }

   public function getSummaryLine($editable=false) {
       global $CLIENT_ROOT;
       $model = $this->model;
       $id = $model->getagentlinksid();
       $returnvalue  = "<span id='agentlinksDiv$id'>";
       $link = $model->getlink();
       $text = $model->gettext();
       $type = $model->gettype();
       if (strlen($text)==0) {
          if (strlen($type)==0) {
             $text = $link;
          } else {
		     $text = $type;
          }
       }
       $returnvalue .= "<a href='$link'> $text</a>";
       if ($editable) {
          $returnvalue .= " <a id='editALinkLink$id'>Edit</a> <a id='deleteALinkLink$id'>Delete</a>";
          $returnvalue .= "
     <script type='text/javascript'>
        function handlerALinkEdit$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=edit&table=AgentLinks&agentlinksid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#agentlinksDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#editALinkLink$id').bind('click',handlerALinkEdit$id);
        function handlerALinkDelete$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=delete&table=AgentLinks&agentlinksid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#agentlinksDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#deleteALinkLink$id').bind('click',handlerALinkDelete$id);
     </script>
                ";
       }
       $returnvalue .= "</span>";

       return $returnvalue;
   }

   public function getDetailsView() {
       $returnvalue = '<ul>';
       $model = $this->model;
       if ($editable) {
          $returnvalue .= "<a id='editALLink$id'>Edit</a> <a id='deleteALLink$id'>Delete</a>";
          $returnvalue .= "
     <script type='text/javascript'>
        function handlerALEdit$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=edit&table=AgentLinks&agentlinksid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#agentlinkDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#editALLink$id').bind('click',handlerALEdit$id);
        function handlerALDelete$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=delete&table=AgentLinks&agentlinksid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#agentlinkDetailDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#deleteALLink$id').bind('click',handlerALDelete$id);
     </script>
                ";
       }
       $returnvalue .= "<li>".agentlinks::AGENTLINKSID.": ".$model->getagentlinksid()."</li>\n";
       $returnvalue .= "<li>".agentlinks::AGENTID.": ".$model->getagentid()."</li>\n";
       $returnvalue .= "<li>".agentlinks::TYPE.": ".$model->gettype()."</li>\n";
       $returnvalue .= "<li>".agentlinks::LINK.": ".$model->getlink()."</li>\n";
       $returnvalue .= "<li>".agentlinks::ISPRIMARYTOPICOF.": ".$model->getisprimarytopicof()."</li>\n";
       $returnvalue .= "<li>".agentlinks::TEXT.": ".$model->gettext()."</li>\n";
       $returnvalue .= "<li>".agentlinks::TIMESTAMPCREATED.": ".$model->gettimestampcreated()."</li>\n";
       $returnvalue .= "<li>".agentlinks::CREATEDBYUID.": ".$model->getcreatedbyuid()."</li>\n";
       $returnvalue .= "<li>".agentlinks::DATELASTMODIFIED.": ".$model->getdatelastmodified()."</li>\n";
       $returnvalue .= "<li>".agentlinks::LASTMODIFIEDBYUID.": ".$model->getlastmodifiedbyuid()."</li>\n";
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getJSON() {
       $returnvalue = '{ ';
       $model = $this->model;
       $returnvalue .= '"'.agentlinks::AGENTLINKSID.': "'.$model->getagentlinksid().'",';
       $returnvalue .= '"'.agentlinks::AGENTID.': "'.$model->getagentid().'",';
       $returnvalue .= '"'.agentlinks::TYPE.': "'.$model->gettype().'",';
       $returnvalue .= '"'.agentlinks::LINK.': "'.$model->getlink().'",';
       $returnvalue .= '"'.agentlinks::ISPRIMARYTOPICOF.': "'.$model->getisprimarytopicof().'",';
       $returnvalue .= '"'.agentlinks::TEXT.': "'.$model->gettext().'",';
       $returnvalue .= '"'.agentlinks::TIMESTAMPCREATED.': "'.$model->gettimestampcreated().'",';
       $returnvalue .= '"'.agentlinks::CREATEDBYUID.': "'.$model->getcreatedbyuid().'",';
       $returnvalue .= '"'.agentlinks::DATELASTMODIFIED.': "'.$model->getdatelastmodified().'",';
       $returnvalue .= '"'.agentlinks::LASTMODIFIEDBYUID.': "'.$model->getlastmodifiedbyuid().'" }';
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getTableRowView() {
       $returnvalue = '<tr>';
       $model = $this->model;
       $returnvalue .= "<td>".$model->getagentlinksid()."</td>\n";
       $returnvalue .= "<td>".$model->getagentid()."</td>\n";
       $returnvalue .= "<td>".$model->gettype()."</td>\n";
       $returnvalue .= "<td>".$model->getlink()."</td>\n";
       $returnvalue .= "<td>".$model->getisprimarytopicof()."</td>\n";
       $returnvalue .= "<td>".$model->gettext()."</td>\n";
       $returnvalue .= "<td>".$model->gettimestampcreated()."</td>\n";
       $returnvalue .= "<td>".$model->getcreatedbyuid()."</td>\n";
       $returnvalue .= "<td>".$model->getdatelastmodified()."</td>\n";
       $returnvalue .= "<td>".$model->getlastmodifiedbyuid()."</td>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>".agentlinks::AGENTLINKSID."</th>\n";
       $returnvalue .= "<th>".agentlinks::AGENTID."</th>\n";
       $returnvalue .= "<th>".agentlinks::TYPE."</th>\n";
       $returnvalue .= "<th>".agentlinks::LINK."</th>\n";
       $returnvalue .= "<th>".agentlinks::ISPRIMARYTOPICOF."</th>\n";
       $returnvalue .= "<th>".agentlinks::TEXT."</th>\n";
       $returnvalue .= "<th>".agentlinks::TIMESTAMPCREATED."</th>\n";
       $returnvalue .= "<th>".agentlinks::CREATEDBYUID."</th>\n";
       $returnvalue .= "<th>".agentlinks::DATELASTMODIFIED."</th>\n";
       $returnvalue .= "<th>".agentlinks::LASTMODIFIEDBYUID."</th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getEditFormView() {
       global $CLIENT_ROOT;
       $model = $this->model;
       if (strlen($model->getagentlinksid())==0) {
          $new = TRUE;
       } else {
          $new = FALSE;
       }
       $returnvalue  = "
     <script type='text/javascript'>
        var frm = $('#saveALinkRecordForm');
        frm.submit(function () {
            $('#statusDiv').html('Saving...');
            $('#saveALinkResultDiv').html('');
            $.ajax({
               type: 'POST',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: frm.serialize(),
               dataType : 'html',
               success: function(data){
                  $('#saveALinkResultDiv').html(data);
                  $('#statusDiv').html('');
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
     </script>
";
       $returnvalue .= '<form id="saveALinkRecordForm">';
       if ($new) {
          $returnvalue .= '<input type=hidden name=mode id=mode value=savenew>';
       } else {
          $returnvalue .= '<input type=hidden name=mode id=mode value=save>';
       }
       $returnvalue .= '<input type=hidden name=table id=table value="AgentLinks">';
       $returnvalue .= "<input type=hidden name='agentid' id='agentid' value='".$model->getagentid()."' >\n";
       $returnvalue .= '<input type=hidden name=agentlinksid value="'.$model->getagentlinksid().'">';
       $returnvalue .= '<ul>';
       $returnvalue .= "<li>TYPE<input type=text name=".agentlinks::TYPE." id=".agentlinks::TYPE." value='".$model->gettype()."'  size='".agentlinks::TYPE_SIZE ."'  maxlength='".agentlinks::TYPE_SIZE ."' ></li> (image, homepage, biography, other)\n";
       $returnvalue .= "<li>LINK<input type=text name=".agentlinks::LINK." id=".agentlinks::LINK." value='".$model->getlink()."'  size='51'  maxlength='".agentlinks::LINK_SIZE ."' ></li>\n";
       $yselected = "";
       $nselected = "selected";
       $qselected = "";
       if ($model->getisprimarytopicof()=='1') { $yselected = "selected"; $nselected = "";  }
       if ($model->getisprimarytopicof()=='0') { $yselected = ""; $nselected = "selected";  }
       $returnvalue .= "<li>Is Primary Topic Of<select id=".agentlinks::ISPRIMARYTOPICOF." name=".agentlinks::ISPRIMARYTOPICOF." >\n";
       $returnvalue .= "<option value='1' $yselected>Yes</option>\n";
       $returnvalue .= "<option value='0' $nselected>No</option>\n";
       $returnvalue .= "</select></li>\n";
       $returnvalue .= "<li>TEXT<input type=text name=".agentlinks::TEXT." id=".agentlinks::TEXT." value='".$model->gettext()."'  size='".agentlinks::TEXT_SIZE ."'  maxlength='".agentlinks::TEXT_SIZE ."' ></li>\n";
       $returnvalue .= '<li><input type=submit value="Save"></li>';
       $returnvalue .= '</ul>';
       $returnvalue .= '</form>';
       $returnvalue .= '<div id="saveALinkResultDiv"></div>';
       return  $returnvalue;
   }
}

class ctrelationshiptypes {
   protected $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('write');
    }

   /**
    * Obtain a list of the relation types from code table ctrelationshiptypes.
    *
    * @return an array of strings, one entry for each valid relation type.
    */
   public function listRelationTypes() {
        $result = array();
        $sql = "select relationship from ctrelationshiptypes ";
        if ($statement = $this->conn->prepare($sql)) {
            $statement->execute();
            $statement->bind_result($nametype);
            while ($statement->fetch()) {
               $result[] = $nametype;
            }
            $statement->close();
        } else {
            throw new Exception("Query preparation failed for '$sql'");
        }
        return $result;
   }
   /**
    * Obtain a list of the relation types from code table ctrelationshiptypes
    *   including names of inverse relationships.
    *
    * @return an array of strings, one entry for each relation type including
    *   inverses.
    */
   public function listRelationTypesFB() {
        $result = array();
        $statements[] = "select relationship from ctrelationshiptypes ";
        $statements[] = "select inverse from ctrelationshiptypes ";
        foreach ($statements as $sql) {
           if ($statement = $this->conn->prepare($sql)) {
               $statement->execute();
               $statement->bind_result($nametype);
               while ($statement->fetch()) {
                  $result[] = $nametype;
               }
               $statement->close();
           } else {
               throw new Exception("Query preparation failed for '$sql'");
           }
        }
        return $result;
   }

   /**
    * Test to see if a provided relationship type is an inverse relationship.
    *
    * @param type to check against list of inverse relationships.
    * @return true if relationship is an inverse, false otherwise.
    */
   public function isInverse($type) {
        $result = false;
        $sql = "select count(*) from ctrelationshiptypes where inverse = ? ";
        if ($statement = $this->conn->prepare($sql)) {
            $statement->bind_param('s',$type);
            $statement->execute();
            $statement->bind_result($count);
            while ($statement->fetch()) {
               if ($count>0) {
                  $result = TRUE;
               }
            }
            $statement->close();
        } else {
            throw new Exception("Query preparation failed for '$sql'");
        }
        return $result;
   }
   /**
    * Test to see if a provided relationship type is an inverse relationship.
    *
    * @param type to check against list of inverse relationships.
    * @return true if relationship is an inverse, false otherwise.
    */
   public function flipToForward($type) {
        $result = false;
        if (!$this->isInverse($type)) {
            throw new Exception("Provided relationship type '$type' is not an inverse relationship");
        }
        $sql = "select relationship from ctrelationshiptypes where inverse = ? ";
        if ($statement = $this->conn->prepare($sql)) {
            $statement->bind_param('s',$type);
            $statement->execute();
            $statement->bind_result($forward);
            while ($statement->fetch()) {
                $result = $forward;
            }
            $statement->close();
        } else {
            throw new Exception("Query preparation failed for '$sql'");
        }
        return $result;
   }

}

class agentrelations
{
   protected $conn;
   // These constants hold the sizes the fields in this table in the database.
   const AGENTRELATIONSID_SIZE = 20; //BIGINT
   const FROMAGENTID_SIZE     = 20; //BIGINT
   const TOAGENTID_SIZE       = 20; //BIGINT
   const RELATIONSHIP_SIZE    = 50; //50
   const NOTES_SIZE           = 900; //900
   const TIMESTAMPCREATED_SIZE = 21; //TIMESTAMP
   const CREATEDBYUID_SIZE    = 11; //INTEGER
   const DATELASTMODIFIED_SIZE = 21; //TIMESTAMP
   const LASTMODIFIEDBYUID_SIZE = 11; //INTEGER
    // These constants hold the field names of the table in the database.
   const AGENTRELATIONSID  = 'agentrelationsid';
   const FROMAGENTID       = 'fromagentid';
   const TOAGENTID         = 'toagentid';
   const RELATIONSHIP      = 'relationship';
   const NOTES             = 'notes';
   const TIMESTAMPCREATED  = 'timestampcreated';
   const CREATEDBYUID      = 'createdbyuid';
   const DATELASTMODIFIED  = 'datelastmodified';
   const LASTMODIFIEDBYUID = 'lastmodifiedbyuid';

   //---------------------------------------------------------------------------

   // interface tableSchema implementation
   // schemaPK returns array of primary key field names
   public function schemaPK() {
       return $this->primaryKeyArray;
   }
   // schemaHaveDistinct returns array of field names for which selectDistinct{fieldname} methods are available.
   public function schemaHaveDistinct() {
       return $this->selectDistinctFieldsArray;
   }
   // schemaFields returns array of all field names
   public function schemaFields() {
       return $this->allFieldsArray;
   }
/*  Example sanitized retrieval of variable matching object variables from $_GET
/*  Customize these to limit each variable to narrowest possible set of known good values.

  $agentrelationsid = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['agentrelationsid']), 0, 20);
  $fromagentid = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['fromagentid']), 0, 20);
  $toagentid = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['toagentid']), 0, 20);
  $relationship = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['relationship']), 0, 50);
  $notes = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['notes']), 0, 900);
  $timestampcreated = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['timestampcreated']), 0, 21);
  $createdbyuid = substr(preg_replace('/[^0-9\-\[NULL\]]/','',$_GET['createdbyuid']), 0, 11);
  $datelastmodified = substr(preg_replace('/[^A-Za-z0-9\.\.\ \[NULL\]]/','',$_GET['datelastmodified']), 0, 21);
  $lastmodifiedbyuid = substr(preg_replace('/[^0-9\-\[NULL\]]/','',$_GET['lastmodifiedbyuid']), 0, 11);
*/

   //---------------------------------------------------------------------------

   private $agentrelationsid; // PK BIGINT
   private $fromagentid; // BIGINT
   private $toagentid; // BIGINT
   private $relationship; // CHAR(50)
   private $notes; // VARCHAR(900)
   private $timestampcreated; // TIMESTAMP
   private $createdbyuid; // INTEGER
   private $datelastmodified; // TIMESTAMP
   private $lastmodifiedbyuid; // INTEGER
   private $dirty;
   private $loaded;
   private $error;
   const FIELDLIST = ' agentrelationsid, fromagentid, toagentid, relationship, notes, timestampcreated, createdbyuid, datelastmodified, lastmodifiedbyuid, ';
   private $allFieldsArray = array( 0 => 'agentrelationsid' , 1 => 'fromagentid' , 2 => 'toagentid' , 3 => 'relationship' , 4 => 'notes' , 5 => 'timestampcreated' , 6 => 'createdbyuid' , 7 => 'datelastmodified' , 8 => 'lastmodifiedbyuid'  ) ;
   private $selectDistinctFieldsArray = array( 1 => 'fromagentid' , 2 => 'toagentid' , 3 => 'relationship'  ) ;

   //---------------------------------------------------------------------------

   function __construct(){
       $this->conn = MySQLiConnectionFactory::getCon('write');
       $this->agentrelationsid = NULL;
       $this->fromagentid = '';
       $this->toagentid = '';
       $this->relationship = '';
       $this->notes = '';
       $this->timestampcreated = '';
       $this->createdbyuid = '';
       $this->datelastmodified = '';
       $this->lastmodifiedbyuid = '';
       $this->dirty = false;
       $this->loaded = false;
       $this->error = '';
   }

   private function l_addslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars_decode($value, ENT_QUOTES);
      return $retval;
   }
   private function l_stripslashes($value) {
      $retval = $value;
      $retval = htmlspecialchars($value, ENT_QUOTES);
      return $retval;
   }
   public function isDirty() {
       return $this->dirty;
   }
   public function isLoaded() {
       return $this->loaded;
   }
   public function errorMessage() {
       return $this->error;
   }

   public function getInverseRelationship() {
       $returnvalue = "";
       $sql = "select inverse from ctrelationshiptypes where relationship = ? ";
       if ($statement = $this->conn->prepare($sql)) {
              $statement->bind_param("s", $this->relationship);
              $statement->execute();
              $statement->bind_result($inverse);
              if ($statement->fetch()) {
                 $returnvalue = $inverse;
              }
              $statement->close();
       }
       return $returnvalue;
   }

   //---------------------------------------------------------------------------

   public function keyValueSet($fieldname,$value) {
       $returnvalue = false;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentrelationsid') { $returnvalue = $this->setagentrelationsid($value); }
             if ($fieldname=='fromagentid') { $returnvalue = $this->setfromagentid($value); }
             if ($fieldname=='toagentid') { $returnvalue = $this->settoagentid($value); }
             if ($fieldname=='relationship') { $returnvalue = $this->setrelationship($value); }
             if ($fieldname=='notes') { $returnvalue = $this->setnotes($value); }
             if ($fieldname=='timestampcreated') { $returnvalue = $this->settimestampcreated($value); }
             if ($fieldname=='createdbyuid') { $returnvalue = $this->setcreatedbyuid($value); }
             if ($fieldname=='datelastmodified') { $returnvalue = $this->setdatelastmodified($value); }
             if ($fieldname=='lastmodifiedbyuid') { $returnvalue = $this->setlastmodifiedbyuid($value); }
             $returnvalue = true;
          }
          catch (exception $e) { ;
              $returnvalue = false;
              throw new Exception('Field Set Error'.$e->getMessage());
          }
       } else {
          throw new Exception('No Such field');
       }
       return $returnvalue;
   }
   public function keyGet($fieldname) {
       $returnvalue = null;
       if ($this->hasField($fieldname)) {
          try {
             if ($fieldname=='agentrelationsid') { $returnvalue = $this->getagentrelationsid(); }
             if ($fieldname=='fromagentid') { $returnvalue = $this->getfromagentid(); }
             if ($fieldname=='toagentid') { $returnvalue = $this->gettoagentid(); }
             if ($fieldname=='relationship') { $returnvalue = $this->getrelationship(); }
             if ($fieldname=='notes') { $returnvalue = $this->getnotes(); }
             if ($fieldname=='timestampcreated') { $returnvalue = $this->gettimestampcreated(); }
             if ($fieldname=='createdbyuid') { $returnvalue = $this->getcreatedbyuid(); }
             if ($fieldname=='datelastmodified') { $returnvalue = $this->getdatelastmodified(); }
             if ($fieldname=='lastmodifiedbyuid') { $returnvalue = $this->getlastmodifiedbyuid(); }
          }
          catch (exception $e) { ;
              $returnvalue = null;
          }
       }
       return $returnvalue;
   }
/*agentrelationsid*/
   public function getagentrelationsid() {
       if ($this->agentrelationsid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->agentrelationsid));
       }
   }
   public function setagentrelationsid($agentrelationsid) {
       if (strlen($agentrelationsid) > agentrelations::AGENTRELATIONSID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->agentrelationsid = $this->l_addslashes($agentrelationsid);
       $this->dirty = true;
   }
/*fromagentid*/
   public function getfromagentid() {
       if ($this->fromagentid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->fromagentid));
       }
   }
   public function setfromagentid($fromagentid) {
       if (strlen($fromagentid) > agentrelations::FROMAGENTID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->fromagentid = $this->l_addslashes($fromagentid);
       $this->dirty = true;
   }
/*toagentid*/
   public function gettoagentid() {
       if ($this->toagentid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->toagentid));
       }
   }
   public function settoagentid($toagentid) {
       if (strlen($toagentid) > agentrelations::TOAGENTID_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->toagentid = $this->l_addslashes($toagentid);
       $this->dirty = true;
   }
/*relationship*/
   public function getrelationship() {
       if ($this->relationship==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->relationship));
       }
   }
   public function setrelationship($relationship) {
       if (strlen($relationship) > agentrelations::RELATIONSHIP_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->relationship = $this->l_addslashes($relationship);
       $this->dirty = true;
   }
/*notes*/
   public function getnotes() {
       if ($this->notes==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->notes));
       }
   }
   public function setnotes($notes) {
       if (strlen($notes) > agentrelations::NOTES_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->notes = $this->l_addslashes($notes);
       $this->dirty = true;
   }
/*timestampcreated*/
   public function gettimestampcreated() {
       if ($this->timestampcreated==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->timestampcreated));
       }
   }
   public function settimestampcreated($timestampcreated) {
       if (strlen($timestampcreated) > agentrelations::TIMESTAMPCREATED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->timestampcreated = $this->l_addslashes($timestampcreated);
       $this->dirty = true;
   }
/*createdbyuid*/
   public function getcreatedbyuid() {
       if ($this->createdbyuid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->createdbyuid));
       }
   }
   public function setcreatedbyuid($createdbyuid) {
       if (strlen(preg_replace('/[^0-9]/','',$createdbyuid)) > agentrelations::CREATEDBYUID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $createdbyuid = trim($createdbyuid);
       if (!ctype_digit(strval($createdbyuid)) && trim(strval($createdbyuid))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->createdbyuid = $this->l_addslashes($createdbyuid);
       $this->dirty = true;
   }
/*datelastmodified*/
   public function getdatelastmodified() {
       if ($this->datelastmodified==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->datelastmodified));
       }
   }
   public function setdatelastmodified($datelastmodified) {
       if (strlen($datelastmodified) > agentrelations::DATELASTMODIFIED_SIZE) {
           throw new Exception('Value exceeds field length.');
       }
       $this->datelastmodified = $this->l_addslashes($datelastmodified);
       $this->dirty = true;
   }
/*lastmodifiedbyuid*/
   public function getlastmodifiedbyuid() {
       if ($this->lastmodifiedbyuid==null) {
          return null;
       } else { ;
          return trim($this->l_stripslashes($this->lastmodifiedbyuid));
       }
   }
   public function setlastmodifiedbyuid($lastmodifiedbyuid) {
       if (strlen(preg_replace('/[^0-9]/','',$lastmodifiedbyuid)) > agentrelations::LASTMODIFIEDBYUID_SIZE) {
           throw new Exception('Value has too many digits for the field length.');
       }
       $lastmodifiedbyuid = trim($lastmodifiedbyuid);
       if (!ctype_digit(strval($lastmodifiedbyuid)) && trim(strval($lastmodifiedbyuid))!='' ) {
             throw new Exception("Value must be an integer");
       }
       $this->lastmodifiedbyuid = $this->l_addslashes($lastmodifiedbyuid);
       $this->dirty = true;
   }
   public function PK() { // get value of primary key
        $returnvalue = '';
        $returnvalue .= $this->getagentrelationsid();
        return $returnvalue;
   }
   public function PKArray() { // get name and value of primary key fields
        $returnvalue = array();
        $returnvalue['agentrelationsid'] = $this->getagentrelationsid();
        return $returnvalue;
   }
   public function NumberOfPrimaryKeyFields() { // returns the number of primary key fields defined for this table
        return 1;
   }

   // Constants holding the mysqli field type character (s,i,d) for each field
  const C_agentrelationsidMYSQLI_TYPE = 'i';
  const C_fromagentidMYSQLI_TYPE = 'i';
  const C_toagentidMYSQLI_TYPE = 'i';
  const C_relationshipMYSQLI_TYPE = 's';
  const C_notesMYSQLI_TYPE = 's';
  const C_timestampcreatedMYSQLI_TYPE = 's';
  const C_createdbyuidMYSQLI_TYPE = 'i';
  const C_datelastmodifiedMYSQLI_TYPE = 's';
  const C_lastmodifiedbyuidMYSQLI_TYPE = 'i';

   // function to obtain the mysqli field type character from a fieldname
   public function MySQLiFieldType($aFieldname) {
      $retval = '';
      if ($aFieldname=='agentrelationsid') { $retval = self::C_agentrelationsidMYSQLI_TYPE; }
      if ($aFieldname=='fromagentid') { $retval = self::C_fromagentidMYSQLI_TYPE; }
      if ($aFieldname=='toagentid') { $retval = self::C_toagentidMYSQLI_TYPE; }
      if ($aFieldname=='relationship') { $retval = self::C_relationshipMYSQLI_TYPE; }
      if ($aFieldname=='notes') { $retval = self::C_notesMYSQLI_TYPE; }
      if ($aFieldname=='timestampcreated') { $retval = self::C_timestampcreatedMYSQLI_TYPE; }
      if ($aFieldname=='createdbyuid') { $retval = self::C_createdbyuidMYSQLI_TYPE; }
      if ($aFieldname=='datelastmodified') { $retval = self::C_datelastmodifiedMYSQLI_TYPE; }
      if ($aFieldname=='lastmodifiedbyuid') { $retval = self::C_lastmodifiedbyuidMYSQLI_TYPE; }
      return $retval;
   }

   // Function load() can take either the value of the primary key which uniquely identifies a particular row
   // or an array of array('primarykeyfieldname'=>'value') in the case of a single field primary key
   // or an array of fieldname value pairs in the case of multiple field primary key.
   public function load($pk) {
        $returnvalue = false;
        $this->setagentrelationsid($pk);
        if($this->agentrelationsid != NULL) {
           $preparesql = 'SELECT agentrelationsid, fromagentid, toagentid, relationship, notes, timestampcreated, createdbyuid, datelastmodified, lastmodifiedbyuid FROM agentrelations WHERE agentrelationsid = ? ';
           if ($statement = $this->conn->prepare($preparesql)) {
              $statement->bind_param("i", $this->agentrelationsid);
              $statement->execute();
              $statement->bind_result($this->agentrelationsid, $this->fromagentid, $this->toagentid, $this->relationship, $this->notes, $this->timestampcreated, $this->createdbyuid, $this->datelastmodified, $this->lastmodifiedbyuid);
              $statement->fetch();
              $statement->close();
           }
           $this->loaded = true;
           $this->dirty = false;
        } else {
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   // Function save() will either save the current record or insert a new record.
   // Inserts new record if the primary key field in this table is null
   // for this instance of this object.
   // Otherwise updates the record identified by the primary key value.
   public function save() {
        $returnvalue = false;
        // Test to see if this is an insert or update.
        if ($this->agentrelationsid!= NULL) {
            $sql  = 'UPDATE  agentrelations SET ';
            $isInsert = false;
            $sql .=  "fromagentid = ? ";
            $sql .=  ", toagentid = ? ";
            $sql .=  ", relationship = ? ";
            $sql .=  ", notes = ? ";
            $sql .=  ", datelastmodified = ? ";
            $sql .=  ", lastmodifiedbyuid = ? ";

            $sql .= "  WHERE agentrelationsid = ? ";
        } else {
            $sql  = 'INSERT INTO agentrelations ';
            $isInsert = true;
            $sql .= '(  fromagentid ,  toagentid ,  relationship ,  notes ,  createdbyuid ,  datelastmodified ,  lastmodifiedbyuid ) VALUES (';
            $sql .=  "  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .=  " ,  ? ";
            $sql .= ')';

        }
        if ($statement = $this->conn->prepare($sql)) {
           if ($this->agentrelationsid!= NULL ) {
              $statement->bind_param("iisssii",  $this->fromagentid , $this->toagentid , $this->relationship , $this->notes , $this->datelastmodified , $this->lastmodifiedbyuid , $this->agentrelationsid );
           } else {
              $statement->bind_param("iissisi",  $this->fromagentid , $this->toagentid , $this->relationship , $this->notes , $this->createdbyuid , $this->datelastmodified , $this->lastmodifiedbyuid );
           }
           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $this->error = $statement->error;
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
        }
        if ($this->error=='') {
            $returnvalue = true;
        };

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function delete() {
        $returnvalue = false;
        if($this->agentrelationsid != NULL) {
           $preparedsql = 'SELECT agentrelationsid FROM agentrelations WHERE agentrelationsid = ?  ' ;
            if ($statement = $this->conn->prepare($preparedsql)) {
               $statement->bind_param("i", $this->agentrelationsid);
               $statement->execute();
               $statement->store_result();
               if ($statement->num_rows()==1) {
                    $sql = 'DELETE FROM agentrelations WHERE agentrelationsid = ?  ';
                    if ($stmt_delete = $this->conn->prepare($sql)) {
                       $stmt_delete->bind_param("i", $this->agentrelationsid);
                       if ($stmt_delete->execute()) {
                           $returnvalue = true;
                       } else {
                           $this->error = mysqli_error($this->conn);
                       }
                       $stmt_delete->close();
                    }
               } else {
                   $this->error = mysqli_error($this->conn);
               }
               $statement->close();
            } else {
                $this->error = mysqli_error($this->conn);
            }

            $this->loaded = true;
            // record was deleted, so set PK to null
            $this->agentrelationsid = NULL;
        } else {
           throw new Exception('Unable to identify which record to delete, primary key is not set');
        }
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function count() {
        $returnvalue = false;
        $sql = 'SELECT count(*)  FROM agentrelations';
        if ($result = $this->conn->query($sql)) {
           if ($result->num_rows()==1) {
             $row = $result->fetch_row();
             if ($row) {
                $returnvalue = $row[0];
             }
           }
        } else {
           $this->error = mysqli_error($this->conn);
        }
        mysqli_free_result($result);

        $this->loaded = true;
        return $returnvalue;
    }
   //---------------------------------------------------------------------------

   public function loadArrayKeyValueSearch($searchTermArray) {
       $returnvalue = array();
       $and = '';
       $wherebit = 'WHERE ';
       foreach($searchTermArray as $fieldname => $searchTerm) {
           if ($this->hasField($fieldname)) {
               $operator = '=';
               // change to a like search if a wildcard character is present
               if (!(strpos($searchTerm,'%')===false)) { $operator = 'like'; }
               if (!(strpos($searchTerm,'_')===false)) { $operator = 'like'; }
               if ($searchTerm=='[NULL]') {
                   $wherebit .= "$and ($fieldname is null or $fieldname='') ";
               } else {
                   $wherebit .= "$and $fieldname $operator ? ";
                   $types = $types . $this->MySQLiFieldType($fieldname);
               }
               $and = ' and ';
           }
       }
       $sql = "SELECT agentrelationsid FROM agentrelations $wherebit";
       if ($wherebit=='') {
             $this->error = 'Error: No search terms provided';
       } else {
          $statement = $this->conn->prepare($sql);
          $vars = Array();
          $vars[] = $types;
          $i = 0;
          foreach ($searchTermArray as $value) {
               $varname = 'bind'.$i;  // create a variable name
               $$varname = $value;    // using that variable name store the value
               $vars[] = &$$varname;  // add a reference to the variable to the array
               $i++;
           }
           //$vars[] contains $types followed by references to variables holding each value in $searchTermArray.
          call_user_func_array(array($statement,'bind_param'),$vars);
          //$statement->bind_param($types,$names);
          $statement->execute();
          $statement->bind_result($id);
          $ids = array();
          while ($statement->fetch()) {
              $ids[] = $id;
          } // double loop to allow all data to be retrieved before preparing a new statement.
          $statement->close();
          for ($i=0;$i<count($ids);$i++) {
              $obj = new agentrelations();
              $obj->load($ids[$i]);
              $returnvalue[] = $obj;
              $result=true;
          }
          if ($result===false) { $this->error = mysqli_error($this->conn); }
       }
       return $returnvalue;
   }

   //---------------------------------------------------------------------------

  public function loadLinkedTo() {
     $returnvalue = array();
       // fk: fromagentid
      $t = new agents();
      $t->load(getfromagentid());
      $returnvalue[fromagentid] = $t;
       // fk: toagentid
      $t = new agents();
      $t->load(gettoagentid());
      $returnvalue[toagentid] = $t;
     return $returnvalue;
  }

   //---------------------------------------------------------------------------

   // Each field with an index has a load array method generated for it.
   public function loadArrayByfromagentid($searchTerm) {
        $returnvalue = array();
        $operator = "=";
        // change to a like search if a wildcard character is present
        if (!(strpos($searchTerm,"%")===false)) { $operator = "like"; }
        if (!(strpos($searchTerm,"_")===false)) { $operator = "like"; }
        $sql = "SELECT agentrelationsid FROM agentrelations WHERE fromagentid $operator '$searchTerm'";
        $preparedsql = "SELECT agentrelationsid FROM agentrelations WHERE fromagentid $operator ? ";
        if ($statement = $this->conn->prepare($preparedsql)) {
            $statement->bind_param("s", $searchTerm);
            $statement->execute();
            $statement->bind_result($id);
            while ($statement->fetch()) { ;
                $obj = new agentrelations();
                $obj->load($id);
                $returnvalue[] = $obj;
            }
            $statement->close();
        }
        return $returnvalue;
   }
   public function loadArrayBytoagentid($searchTerm) {
        $returnvalue = array();
        $operator = "=";
        // change to a like search if a wildcard character is present
        if (!(strpos($searchTerm,"%")===false)) { $operator = "like"; }
        if (!(strpos($searchTerm,"_")===false)) { $operator = "like"; }
        $sql = "SELECT agentrelationsid FROM agentrelations WHERE toagentid $operator '$searchTerm'";
        $preparedsql = "SELECT agentrelationsid FROM agentrelations WHERE toagentid $operator ? ";
        if ($statement = $this->conn->prepare($preparedsql)) {
            $statement->bind_param("s", $searchTerm);
            $statement->execute();
            $statement->bind_result($id);
            while ($statement->fetch()) { ;
                $obj = new agentrelations();
                $obj->load($id);
                $returnvalue[] = $obj;
            }
            $statement->close();
        }
        return $returnvalue;
   }
   public function loadArrayByrelationship($searchTerm) {
        $returnvalue = array();
        $operator = "=";
        // change to a like search if a wildcard character is present
        if (!(strpos($searchTerm,"%")===false)) { $operator = "like"; }
        if (!(strpos($searchTerm,"_")===false)) { $operator = "like"; }
        $sql = "SELECT agentrelationsid FROM agentrelations WHERE relationship $operator '$searchTerm'";
        $preparedsql = "SELECT agentrelationsid FROM agentrelations WHERE relationship $operator ? ";
        if ($statement = $this->conn->prepare($preparedsql)) {
            $statement->bind_param("s", $searchTerm);
            $statement->execute();
            $statement->bind_result($id);
            while ($statement->fetch()) { ;
                $obj = new agentrelations();
                $obj->load($id);
                $returnvalue[] = $obj;
            }
            $statement->close();
        }
        return $returnvalue;
   }

   //---------------------------------------------------------------------------

   // Each field with an index has a select distinct method generated for it.
   public function selectDistinctfromagentid($startline,$link,$endline,$includecount=false,$orderbycount=false) {
        $returnvalue = '';
        $order = ' fromagentid ';
        if ($orderbycount) { $order = ' druid_ct DESC '; }
        $sql = "SELECT count(*) as druid_ct, fromagentid FROM agentrelations group by fromagentid order by $order ";
        if ($result = $this->conn->query($sql)) {
           while ($row = $result->fetch_row()) {
              if ($row) {
                  $count = $row[0];
                  $val = $row[1];
                  $escaped = urlencode($row[1]);
                  if ($val=='') {
                     $val = '[NULL]';
                     $escaped = urlencode('[NULL]');
                  }
                  if ($link=='') {
                     $returnvalue .= "$startline$val&nbsp;($count)$endline";
                  } else {
                     $returnvalue .= "$startline<a href='$link&fromagentid=$escaped'>$val</a>&nbsp;($count)$endline";
                  }
              }
           }
           $result->close();
        }
        return $returnvalue;
    }
   public function selectDistincttoagentid($startline,$link,$endline,$includecount=false,$orderbycount=false) {
        $returnvalue = '';
        $order = ' toagentid ';
        if ($orderbycount) { $order = ' druid_ct DESC '; }
        $sql = "SELECT count(*) as druid_ct, toagentid FROM agentrelations group by toagentid order by $order ";
        if ($result = $this->conn->query($sql)) {
           while ($row = $result->fetch_row()) {
              if ($row) {
                  $count = $row[0];
                  $val = $row[1];
                  $escaped = urlencode($row[1]);
                  if ($val=='') {
                     $val = '[NULL]';
                     $escaped = urlencode('[NULL]');
                  }
                  if ($link=='') {
                     $returnvalue .= "$startline$val&nbsp;($count)$endline";
                  } else {
                     $returnvalue .= "$startline<a href='$link&toagentid=$escaped'>$val</a>&nbsp;($count)$endline";
                  }
              }
           }
           $result->close();
        }
        return $returnvalue;
    }
   public function selectDistinctrelationship($startline,$link,$endline,$includecount=false,$orderbycount=false) {
        $returnvalue = '';
        $order = ' relationship ';
        if ($orderbycount) { $order = ' druid_ct DESC '; }
        $sql = "SELECT count(*) as druid_ct, relationship FROM agentrelations group by relationship order by $order ";
        if ($result = $this->conn->query($sql)) {
           while ($row = $result->fetch_row()) {
              if ($row) {
                  $count = $row[0];
                  $val = $row[1];
                  $escaped = urlencode($row[1]);
                  if ($val=='') {
                     $val = '[NULL]';
                     $escaped = urlencode('[NULL]');
                  }
                  if ($link=='') {
                     $returnvalue .= "$startline$val&nbsp;($count)$endline";
                  } else {
                     $returnvalue .= "$startline<a href='$link&relationship=$escaped'>$val</a>&nbsp;($count)$endline";
                  }
              }
           }
           $result->close();
        }
        return $returnvalue;
    }

   public function keySelectDistinct($fieldname,$startline,$link,$endline,$includecount=false,$orderbycount=false) {
       $returnvalue = '';
       switch ($fieldname) {
          case 'fromagentid':
             $returnvalue = $this->selectDistinctfromagentid($startline,$link,$endline,$includecount,$orderbycount);
             break;
          case 'toagentid':
             $returnvalue = $this->selectDistincttoagentid($startline,$link,$endline,$includecount,$orderbycount);
             break;
          case 'relationship':
             $returnvalue = $this->selectDistinctrelationship($startline,$link,$endline,$includecount,$orderbycount);
             break;
       }
       return $returnvalue;
    }

   //---------------------------------------------------------------------------

   public function hasField($fieldname) {
       $returnvalue = false;
       if (trim($fieldname)!='' && trim($fieldname)!=',') {
            if (strpos(self::FIELDLIST," $fieldname, ")!==false) {
               $returnvalue = true;
            }
       }
       return $returnvalue;
    }
   //---------------------------------------------------------------------------


}


// Write your own views by extending this class.
// Place your extended views in a separate file and you
// can use Druid to regenerate the agentrelations.php file to reflect changes
// in the underlying database without overwriting your custom views of the data.
//
class agentrelationsView
{
   var $model = null;
   public function setModel($aModel) {
       $this->model = $aModel;
   }
   // @param $includeRelated default true shows rows from other tables through foreign key relationships.
   // @param $editLinkURL default '' allows adding a link to show this record in an editing form.
   public function getDetailsView($includeRelated=true, $editLinkURL='') {
       $returnvalue = '<ul>';
       $editLinkURL=trim($editLinkURL);
       $model = $this->model;
       $primarykeys = $model->schemaPK();
       if ($editLinkURL!='') {
          if (!preg_match('/\&$/',$editLinkURL)) { $editLinkURL .= '&'; }
          $nullpk = false;
          foreach ($primarykeys as $primarykey) {
              // Add fieldname=value pairs for primary key(s) to editLinkURL.
              $editLinkURL .= urlencode($primarykey) . '=' . urlencode($model->keyGet($primarykey));
              if ($model->keyGet($primarykey)=='') { $nullpk = true; }
          }
          if (!$nullpk) { $returnvalue .= "<li>agentrelations <a href='$editLinkURL'>Edit</a></li>\n";  }
       }
       $returnvalue .= "<li>".agentrelations::AGENTRELATIONSID.": ".$model->getagentrelationsid()."</li>\n";
       $returnvalue .= "<li>".agentrelations::FROMAGENTID.": ".$model->getfromagentid()."</li>\n";
       $returnvalue .= "<li>".agentrelations::TOAGENTID.": ".$model->gettoagentid()."</li>\n";
       $returnvalue .= "<li>".agentrelations::RELATIONSHIP.": ".$model->getrelationship()."</li>\n";
       $returnvalue .= "<li>".agentrelations::NOTES.": ".$model->getnotes()."</li>\n";
       $returnvalue .= "<li>".agentrelations::TIMESTAMPCREATED.": ".$model->gettimestampcreated()."</li>\n";
       $returnvalue .= "<li>".agentrelations::CREATEDBYUID.": ".$model->getcreatedbyuid()."</li>\n";
       $returnvalue .= "<li>".agentrelations::DATELASTMODIFIED.": ".$model->getdatelastmodified()."</li>\n";
       $returnvalue .= "<li>".agentrelations::LASTMODIFIEDBYUID.": ".$model->getlastmodifiedbyuid()."</li>\n";
       if ($includeRelated) {
           // note that $includeRelated is provided as false in calls out to
           // related tables to prevent infinite loops between related objects.
           $returnvalue .= "<li>agents</li>";
           $t_agents = new agents();
           $t_agentsView = new agentsView();
           $t_agentsView->setModel($t_agents);
           if ($model->getfromagentid() != '') {
               $t_agents->load($model->getfromagentid());
               $returnvalue .= $t_agentsView->getDetailsView(false);
           }
           $returnvalue .= "<li>agents</li>";
           $t_agents = new agents();
           $t_agentsView = new agentsView();
           $t_agentsView->setModel($t_agents);
           if ($model->gettoagentid() != '') {
               $t_agents->load($model->gettoagentid());
               $returnvalue .= $t_agentsView->getDetailsView(false);
           }

        }
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getSummaryLine($editable, $inverse = false) {
      global $CLIENT_ROOT;
      $result = "";
      $model = $this->model;
      $from = new Agent();
      $from->load($model->getfromagentid());
      $fromlink = "agent.php?agentid=".$model->getfromagentid();
      $to = new Agent();
      $to->load($model->gettoagentid());
      $tolink = "agent.php?agentid=".$model->gettoagentid();
      $id = $model->getagentrelationsid();
      $result .= "<div id='agentrelDiv$id'>";
      if ($inverse) {
         $relation = $model->getInverseRelationship();
         $result .= $to->getAssembledName(false) . " is " . $relation .  " <a href='$fromlink'>" . $from->getAssembledName(false) . "</a> ";
      } else {
         $relation = $model->getrelationship();
         $result .= $from->getAssembledName(false) . " is " . $relation .  " <a href='$tolink'>" . $to->getAssembledName(false) . "</a> ";
      }
      if ($editable) {
         $result .= "<a id='editARelLink$id'>Edit</a> <a id='deleteARelLink$id'>Delete</a>";
         $result .= "
     <script type='text/javascript'>
        function handlerARelEdit$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=edit&table=AgentRelations&agentrelationsid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#agentrelDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#editARelLink$id').bind('click',handlerARelEdit$id);
        function handlerARelDelete$id () {
            $.ajax({
               type: 'GET',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: 'mode=delete&table=AgentRelations&agentrelationsid=".$id."',
               dataType : 'html',
               success: function(data){
                  $('#agentrelDiv$id').html(data);
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        };
        $('#deleteARelLink$id').bind('click',handlerARelDelete$id);
     </script>
         ";
      }
      $result .= "</div>";
      return $result;
   }
   public function getJSON() {
       $returnvalue = '{ ';
       $model = $this->model;
       $returnvalue .= '"'.agentrelations::AGENTRELATIONSID.': "'.$model->getagentrelationsid().'",';
       $returnvalue .= '"'.agentrelations::FROMAGENTID.': "'.$model->getfromagentid().'",';
       $returnvalue .= '"'.agentrelations::TOAGENTID.': "'.$model->gettoagentid().'",';
       $returnvalue .= '"'.agentrelations::RELATIONSHIP.': "'.$model->getrelationship().'",';
       $returnvalue .= '"'.agentrelations::NOTES.': "'.$model->getnotes().'",';
       $returnvalue .= '"'.agentrelations::TIMESTAMPCREATED.': "'.$model->gettimestampcreated().'",';
       $returnvalue .= '"'.agentrelations::CREATEDBYUID.': "'.$model->getcreatedbyuid().'",';
       $returnvalue .= '"'.agentrelations::DATELASTMODIFIED.': "'.$model->getdatelastmodified().'",';
       $returnvalue .= '"'.agentrelations::LASTMODIFIEDBYUID.': "'.$model->getlastmodifiedbyuid().'" }';
       $returnvalue .= '</ul>';
       return  $returnvalue;
   }
   public function getTableRowView() {
       $returnvalue = '<tr>';
       $model = $this->model;
       $returnvalue .= "<td>".$model->getagentrelationsid()."</td>\n";
       $returnvalue .= "<td>".$model->getfromagentid()."</td>\n";
       $returnvalue .= "<td>".$model->gettoagentid()."</td>\n";
       $returnvalue .= "<td>".$model->getrelationship()."</td>\n";
       $returnvalue .= "<td>".$model->getnotes()."</td>\n";
       $returnvalue .= "<td>".$model->gettimestampcreated()."</td>\n";
       $returnvalue .= "<td>".$model->getcreatedbyuid()."</td>\n";
       $returnvalue .= "<td>".$model->getdatelastmodified()."</td>\n";
       $returnvalue .= "<td>".$model->getlastmodifiedbyuid()."</td>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getHeaderRow() {
       $returnvalue = '<tr>';
       $returnvalue .= "<th>".agentrelations::AGENTRELATIONSID."</th>\n";
       $returnvalue .= "<th>".agentrelations::FROMAGENTID."</th>\n";
       $returnvalue .= "<th>".agentrelations::TOAGENTID."</th>\n";
       $returnvalue .= "<th>".agentrelations::RELATIONSHIP."</th>\n";
       $returnvalue .= "<th>".agentrelations::NOTES."</th>\n";
       $returnvalue .= "<th>".agentrelations::TIMESTAMPCREATED."</th>\n";
       $returnvalue .= "<th>".agentrelations::CREATEDBYUID."</th>\n";
       $returnvalue .= "<th>".agentrelations::DATELASTMODIFIED."</th>\n";
       $returnvalue .= "<th>".agentrelations::LASTMODIFIEDBYUID."</th>\n";
       $returnvalue .= '</tr>';
       return  $returnvalue;
   }
   public function getEditFormView() {
       global $CLIENT_ROOT;
       $model = $this->model;
       if (strlen($model->getagentrelationsid())==0) {
          $new = TRUE;
       } else {
          $new = FALSE;
       }

       $returnvalue  = "
     <script type='text/javascript'>
        var frm = $('#saveARelRecordForm');
        frm.submit(function () {
            $('#statusDiv').html('Saving...');
            $('#saveARelResultDiv').html('');
            $.ajax({
               type: 'POST',
               url: '$CLIENT_ROOT/agents/rpc/handler.php',
               data: frm.serialize(),
               dataType : 'html',
               success: function(data){
                  $('#saveARelResultDiv').html(data);
                  $('#statusDiv').html('');
               },
               error: function( xhr, status, errorThrown ) {
                  $('#statusDiv').html('Error. ' + errorThrown);
                  console.log( 'Error: ' + errorThrown );
                  console.log( 'Status: ' + status );
                  console.dir( xhr );
               }
            });
            return false;
        });
     </script>
";
       $returnvalue .= '<div id="saveARelResultDiv"></div>';
       $returnvalue .= '<form id="saveARelRecordForm">';
       if ($new) {
          $returnvalue .= '<input type=hidden name=mode id=mode value=savenew>';
       } else {
          $returnvalue .= '<input type=hidden name=mode id=mode value=save>';
       }
       $returnvalue .= '<input type=hidden name=table id=table value="AgentRelations">';
       $returnvalue .= '<input type=hidden name=agentrelationsid value="'.$model->getagentrelationsid().'">';

       $returnvalue .= '<ul>';
       if (strlen($model->getfromagentid())>0) {
          $fromAgent = new Agent();
          $fromAgent->load($model->getfromagentid());
          $returnvalue .= '<input type=hidden name=fromagentid id=fromagentid value="'.$model->getfromagentid().'">';
          $returnvalue .= "<li>".$fromAgent->getAssembledName()."</li>\n";
       } else {
          $returnvalue .= "
          <script type='text/javascript'>
             $('#fagentselect').autocomplete({
                 source: '".$CLIENT_ROOT."/agents/rpc/handler.php?mode=listjson',
                 minLength: 2,
                 select: function( event, ui ) {
                       $('#".agentrelations::FROMAGENTID."').val(ui.item.value);
                       $('#fagentselect').val(ui.item.label);
                       event.preventDefault();
                    }
                 });
          </script>
          ";
          $dupof = "";
          if (strlen($model->getfromagentid())>0) {
             $toAg = new Agent();
             $toAg->load($model->gettoagentidid());
             $to = $toAg->getAssembledName(TRUE);
          }
          $returnvalue .= '<li>
                             <div class="ui-widget">
                                <label for="fagentselect"></label>
                                <input id="fagentselect" value="'.$to.'"/>
                                <input type="hidden" id="'.agentrelations::FROMAGENTID.'" name="'.agentrelations::FROMAGENTID.'" value="'.$model->getfromagentidid().'"/>
                             </div>
                           </li>';
       }
       $ct= new ctrelationshiptypes();
       $types = $ct->listRelationTypesFB();
       $returnvalue .= "<li>Relationship: <select id='".agentrelations::RELATIONSHIP."' name=".agentrelations::RELATIONSHIP." >\n";
       foreach ($types as $type) {
            if ($type==$model->getrelationship()) { $isselected = 'selected'; } else { $isselected = ''; }
            $returnvalue .= "<option value='$type' $isselected>$type</option>\n";
       }
       $returnvalue .= "
       <script type='text/javascript'>
          $('#tagentselect').autocomplete({
              source: '".$CLIENT_ROOT."/agents/rpc/handler.php?mode=listjson',
              minLength: 2,
              select: function( event, ui ) {
                    $('#".agentrelations::TOAGENTID."').val(ui.item.value);
                    $('#tagentselect').val(ui.item.label);
                    event.preventDefault();
                 }
              });
       </script>
       ";
       $dupof = "";
       if (strlen($model->gettoagentid())>0) {
          $toAg = new Agent();
          $toAg->load($model->gettoagentid());
          $to = $toAg->getAssembledName(TRUE);
       }
       $returnvalue .= '<li>
                          <div class="ui-widget">
                             <label for="tagentselect">of </label>
                             <input id="tagentselect" value="'.$to.'"/>
                             <input type="hidden" id="'.agentrelations::TOAGENTID.'" name="'.agentrelations::TOAGENTID.'" value="'.$model->gettoagentid().'"/>
                          </div>
                        </li>';

       $returnvalue .= "<li>NOTES<input type=text name=".agentrelations::NOTES." id=".agentrelations::NOTES." value='".$model->getnotes()."'  size='51'  maxlength='".agentrelations::NOTES_SIZE ."' ></li>\n";
       $returnvalue .= '<li><input type=submit value="Save"></li>';
       $returnvalue .= '</ul>';
       $returnvalue .= '</form>';
       return  $returnvalue;
   }
}

?>