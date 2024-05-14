<?php

include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/OmOccurrences.php');
include_once($SERVER_ROOT.'/classes/ImageDetailManager.php');

/**
 * Controler class for ActionRequests, may be subclassed for particular action requests.
 */
class ActionManager extends Manager {

   public function __construct($id=null,$conType='readonly'){
      parent::__construct(null,'write');
   }

   protected function getTableName($table) {
      $result = null;

      if (strtolower($table)=='omoccurrences' || strtolower($table)=='occurence') { $result = 'omoccurrences'; }
      if (strtolower($table)=='images' || strtolower($table)=='image') { $result = 'images'; }

      return $result;
   }

   /**
    * Create a new action request.
    *
    * @param uid the user id of the person making the request.
    * @param fk the primary key value of the row in the table to which the action request applies.
    * @param table the name of the table to which the action request applies.
    * @param requesttype the type of request being made.
    * @param remarks any elaboration by the requestor on the request.
    * @return the actionrequestid for the inserted row, or null if there was an error, if
    *   an error, it can be retrieved with getErrorMessage();
    */
   public function makeActionRequest($uid,$fk,$table,$requesttype,$remarks) {
      $result = null;

      $tablename = $this->getTableName($table);
      if ($tablename==null) {
         $this->errormessage = "Error: $table not recognized as a table name.";
      } else {
         $sql = "insert into actionrequest (uid_requestor,fk,requesttype,requestremarks,tablename,state,priority) values (?,?,?,?,?,'New',3) ";
         $stmt = $this->conn->stmt_init();
         $stmt->prepare($sql);
         if ($stmt) {
            $stmt->bind_param('iisss',$uid,$fk,$requesttype,$remarks,$tablename);
            if ($stmt->execute()) {
                $result = $this->conn->insert_id;
            } else {
              $this->errormessage = "Error:". $stmt->error;
            }
            $stmt->close;
         } else {
            $this->errormessage = "Error: ". trim($stmt->error . " " . $this->conn->error);
         }
      }
      return $result;
   }


   /**
    * Obtain an array of text strings summarizing action requests that apply to some table or all tables.
    *
    * @param fk the primary key of table to check for action requests, if null, list all requests for tablename.
    * @param table the table name to check for action requests, if null, list requests for all tables.
    *
    * @return an array of text strings, one per action request.
    */
   public function listActionRequests($fk, $table) {
      $result = Array();
      $tablename = $this->getTableName($table);
      if ($tablename==null) {
         if ($fk==null) {
            $sql = "select requesttype, requestremarks, a.state, a.resolution, requestdate, u.firstname, u.lastname from actionrequest a left join users u on a.uid_requestor = u.uid order by requestdate desc ";
         } else {
            $sql = "select requesttype, requestremarks, a.state, a.resolution, requestdate, u.firstname, u.lastname from actionrequest a left join users u on a.uid_requestor = u.uid where a.fk = ? order by requestdate desc ";
         }
      } else {
         if ($fk==null) {
            $sql = "select requesttype, requestremarks, a.state, a.resolution, requestdate, u.firstname, u.lastname from actionrequest a left join users u on a.uid_requestor = u.uid where tablename = ? order by requestdate desc ";
         } else {
            $sql = "select requesttype, requestremarks, a.state, a.resolution, requestdate, u.firstname, u.lastname from actionrequest a left join users u on a.uid_requestor = u.uid where a.fk = ? and tablename = ? order by requestdate desc ";
         }
      }
      $stmt = $this->conn->stmt_init();
      $stmt->prepare($sql);
      if ($stmt->error==null) {
         if ($tablename==null) {
             if ($fk!=null) {
                $stmt->bind_param('i',$fk);
             }
         } else {
             if ($fk==null) {
                $stmt->bind_param('s',$tablename);
             } else {
                $stmt->bind_param('is',$fk,$tablename);
             }
         }
         $stmt->execute();
         $stmt->bind_result($requesttype,$requestremarks,$state,$resolution,$requestdate,$firstname,$lastname);
         while ($stmt->fetch()) {
            if (strlen($resolution)>0) {
               $state = "$state ($resolution)";
            }
            $result[] = "$state: $firstname $lastname Requested $requesttype  on $requestdate ";
         }
         $stmt->close();
      } else {
         $this->errormessage = "Error:" . trim($stmt->error . " " . $this->conn->error);
      }
      return $result;
    }

   /**
    * Obtain an array of action request objects describing action requests that apply to some table or all tables.
    *
    * @param fk the primary key of table to check for action requests, if null, list all requests for tablename.
    * @param table the table name to check for action requests, if null, list requests for all tables.
    *
    * @return an array of ActionRequest objects, one per action request.
    */
   public function listActionRequestsObjArr($fk, $table) {
      $result = Array();
      $tablename = $this->getTableName($table);
      $fields = "actionrequestid, fk, tablename, requesttype, uid_requestor, requestdate, requestremarks, priority, uid_fullfillor, a.state, a.resolution, statesetdate, resolutionremarks, concat(ifnull(u.firstname,''), ' ', ifnull(u.lastname,'')) as requestor, concat(ifnull(f.firstname,''), ' ', ifnull(f.lastname,'')) as fullfillor ";
      if ($tablename==null) {
         if ($fk==null) {
            $wherebit = "";
         } else {
            $wherebit = "where a.fk = ? ";
         }
      } else {
         if ($fk==null) {
            $wherebit = "where tablename = ? ";
         } else {
            $wherebit = "where a.fk = ? and tablename = ?";
         }
      }
      $order = "order by requestdate desc ";
      $sql = "select $fields from actionrequest a left join users u on a.uid_requestor = u.uid left join users f on a.uid_fullfillor = f.uid $wherebit $order ";
      $stmt = $this->conn->stmt_init();
      $stmt->prepare($sql);
      if ($stmt->error==null) {
         if ($tablename==null) {
             if ($fk!=null) {
                $stmt->bind_param('i',$fk);
             }
         } else {
             if ($fk==null) {
                $stmt->bind_param('s',$tablename);
             } else {
                $stmt->bind_param('is',$fk,$tablename);
             }
         }
         $stmt->execute();
         $stmt->bind_result($actionrequestid, $fk, $tablename, $requesttype, $uid_requestor, $requestdate, $requestremarks, $priority, $uid_fullfillor, $state, $resolution, $statesetdate, $resolutionremarks, $requestor, $fullfillor);
         while ($stmt->fetch()) {
            $req = new ActionRequest();
            $req->actionrequestid = $actionrequestid;
            $req->fk = $fk;
            $req->tablename = $tablename;
            $req->requesttype = $requesttype;
            $req->uid_requestor = $uid_requestor;
            $req->requestdate = $requestdate;
            $req->requestremarks = $requestremarks;
            $req->priority = $priority;
            $req->uid_fullfillor = $uid_fullfillor;
            $req->state = $state;
            $req->resolution = $resolution;
            $req->statesetdate = $statesetdate;
            $req->resolutionremarks = $resolutionremarks;
            $req->requestor = $requestor;
            $req->fullfillor = $fullfillor;
            $result[] = $req;
         }
         $stmt->close();
      } else {
         $this->errormessage = "Error:" . trim($stmt->error . " " . $this->conn->error);
      }
      return $result;
   }

   /**
    * Query for action request objects.
    *
    * @return an array of ActionRequest objects, one per action request.
    */
   public function queryActionRequestsObjArr($requesttype,$priority,$state,$resolution,$collid,$text) {
      $result = Array();
      $tablename = $this->getTableName($table);
      $fields = "actionrequestid, fk, tablename, requesttype, uid_requestor, requestdate, requestremarks, priority, uid_fullfillor, a.state, a.resolution, statesetdate, resolutionremarks, concat(ifnull(u.firstname,''), ' ', ifnull(u.lastname,'')) as requestor, concat(ifnull(f.firstname,''), ' ', ifnull(f.lastname,'')) as fullfillor ";
      $wherebit = '';
      $types = "";
      $params = Array();
      $and = "";
      if ($requesttype!=null) {
         if (!is_array($requesttype)) {
            $rt = Array();
            $rt[] = $requesttype;
         } else {
            $rt = $requesttype;
         }
         $wherebit .= "$and (";
         $or = '';
         foreach ($rt as $rtype) {
            $wherebit .= "$or requesttype = ?  ";
            $types .= 's';
            $params[] = $rtype;
            $or = " OR ";
         }
         $wherebit .= ")";
         $and = " AND ";
      }
      if ($priority!=null) {
            $wherebit .= "$and priority = ?  ";
            $types .= 'i';
            $params[] = $priority;
            $and = " AND ";
      }
      if ($state!=null) {
         if (!is_array($state)) {
            $st = Array();
            $st[] = $state;
         } else {
            $st = $state;
         }
         $wherebit .= "$and (";
         $or = '';
         foreach ($st as $stype) {
            $wherebit .= "$or a.state = ?  ";
            $types .= 's';
            $params[] = $stype;
            $or = " OR ";
         }
         $wherebit .= ")";
         $and = " AND ";
      }
      if ($resolution!=null) {
            $wherebit .= "$and resolution = ?  ";
            $types .= 's';
            $params[] = $resolution;
            $and = " AND ";
      }
      // TODO: collid
      if ($text!=null) {
            $wherebit .= "$and ( requestremarks like ? OR resolutionremarks like ? )  ";
            $types .= 'ss';
            $params[] = "%$text%";
            $params[] = "%$text%";
            $and = " AND ";
      }
      $order = "order by requestdate desc ";
      if (strlen($wherebit)>0) { $wherebit = " WHERE $wherebit "; }
      $sql = "select $fields from actionrequest a left join users u on a.uid_requestor = u.uid left join users f on a.uid_fullfillor = f.uid $wherebit $order ";
      $stmt = $this->conn->stmt_init();
      $stmt->prepare($sql);
      if ($stmt->error==null) {
         if (strlen($wherebit)>0) {
            call_user_func_array('mysqli_stmt_bind_param',
                 array_merge (array($stmt, $types), $this->correctReferences($params))
            );
         }
         $stmt->execute();
         $stmt->bind_result($actionrequestid, $fk, $tablename, $requesttype, $uid_requestor, $requestdate, $requestremarks, $priority, $uid_fullfillor, $state, $resolution, $statesetdate, $resolutionremarks, $requestor, $fullfillor);
         while ($stmt->fetch()) {
            $req = new ActionRequest();
            $req->actionrequestid = $actionrequestid;
            $req->fk = $fk;
            $req->tablename = $tablename;
            $req->requesttype = $requesttype;
            $req->uid_requestor = $uid_requestor;
            $req->requestdate = $requestdate;
            $req->requestremarks = $requestremarks;
            $req->priority = $priority;
            $req->uid_fullfillor = $uid_fullfillor;
            $req->state = $state;
            $req->resolution = $resolution;
            $req->statesetdate = $statesetdate;
            $req->resolutionremarks = $resolutionremarks;
            $req->requestor = $requestor;
            $req->fullfillor = $fullfillor;
            $result[] = $req;
         }
         $stmt->close();
      } else {
         $this->errormessage = "Error:" . trim($stmt->error . " " . $this->conn->error);
      }
      return $result;
   }
   /**
    * Obtain an actionrequest object by actionrequestid.
    *
    * @param actionrequestid the id of the action request to return
    * @return an ActionRequest object containing the action request, or null if there
    *    was an error.
    */
   public function getActionRequestsObj($actionrequestid) {
      $result = null;
      $fields = "actionrequestid, fk, tablename, requesttype, uid_requestor, requestdate, requestremarks, priority, uid_fullfillor, a.state, a.resolution, statesetdate, resolutionremarks, concat(ifnull(u.firstname,''), ' ', ifnull(u.lastname,'')) as requestor, concat(ifnull(f.firstname,''), ' ', ifnull(f.lastname,'')) as fullfillor ";
      if ($tablename==null) {
         if ($fk==null) {
            $wherebit = "";
         } else {
            $wherebit = "where a.fk = ? ";
         }
      } else {
         if ($fk==null) {
            $wherebit = "where tablename = ? ";
         } else {
            $wherebit = "where a.fk = ? and tablename = ?";
         }
      }
      $wherebit = 'where actionrequestid = ? ';
      $order = "order by requestdate desc ";
      $sql = "select $fields from actionrequest a left join users u on a.uid_requestor = u.uid left join users f on a.uid_fullfillor = f.uid $wherebit $order ";
      $stmt = $this->conn->stmt_init();
      $stmt->prepare($sql);
      if ($stmt->error==null) {
         $stmt->bind_param('i',$actionrequestid);
         $stmt->execute();
         $stmt->bind_result($actionrequestid, $fk, $tablename, $requesttype, $uid_requestor, $requestdate, $requestremarks, $priority, $uid_fullfillor, $state, $resolution, $statesetdate, $resolutionremarks, $requestor, $fullfillor);
         while ($stmt->fetch()) {
            $result = new ActionRequest();
            $result->actionrequestid = $actionrequestid;
            $result->fk = $fk;
            $result->tablename = $tablename;
            $result->requesttype = $requesttype;
            $result->uid_requestor = $uid_requestor;
            $result->requestdate = $requestdate;
            $result->requestremarks = $requestremarks;
            $result->priority = $priority;
            $result->uid_fullfillor = $uid_fullfillor;
            $result->state = $state;
            $result->resolution = $resolution;
            $result->statesetdate = $statesetdate;
            $result->resolutionremarks = $resolutionremarks;
            $result->requestor = $requestor;
            $result->fullfillor = $fullfillor;
            $this->id = $actionrequestid;
         }
         $stmt->close();
      } else {
         $this->errormessage = "Error:" . trim($stmt->error . " " . $this->conn->error);
      }
      return $result;
   }

   public function saveChanges($arrayFromRequest) {
      $array = $this->cleanInArray($arrayFromRequest);
      $result = FALSE;
      $state = 'New';
      $priority = 3;
      if (isset($array['actionrequestid'])) {
         $actionrequestid = preg_replace('/[^0-9]/','',$array['actionrequestid']);
      }
      if (isset($array['state'])) {
         $state = $array['state'];
      }
      if (isset($array['resolution'])) {
         $resolution = $array['resolution'];
      }
      if (isset($array['resolutionremarks'])) {
         $resolutionremarks = $array['resolutionremarks'];
      }
      if (isset($array['priority'])) {
         $priority = preg_replace('/[^0-9]/','',$array['priority']);
      }
      if ($priority==null) { $priority = 3; }
      $uid = $SYMB_UID;
      if (strlen($actionrequestid)>0) {
          $sql = "update actionrequest set state = ?, priority = ?, resolution = ?, statesetdate = now(), uid_fullfillor = ?, resolutionremarks = ? where actionrequestid = ? ";
          $stmt = $this->conn->stmt_init();
          $stmt->prepare($sql);
          if ($stmt->error==null) {
             $stmt->bind_param('sisisi',$state,$priority,$resolution,$uid,$resolutionremarks,$actionrequestid);
             $stmt->execute();
             if ($stmt->error==null) {
                $result=TRUE;
             } else {
                $this->errormessage = $stmt->error;
             }
          } else {
             $this->errormessage = $stmt->error;
          }
          $stmt->close();
      }
   }
}

/** Data structure to hold action requests.
 */
class ActionRequest {
   public $actionrequestid;
   public $fk;
   public $tablename;
   public $requesttype;
   public $uid_requestor;
   public $requestdate;
   public $requestremarks;
   public $priority;
   public $uid_fullfillor;
   public $state;
   public $resolution;
   public $statesetdate;
   public $resolutionremarks;
   public $requestor;
   public $fullfillor;

   public function getHumanReadableTablename() {
      $result = "Unknown";
      if($this->tablename=="omoccurrences") {
         $result = "Occurrence";
      }
      if($this->tablename=="images") {
         $result = "Image";
      }
      return $result;
   }

   /**
    * Obtain a hyperlink to the appropriate display page for the table and row referenced
    * in this action request.
    *
    * @return a text string containing an a with href to the display page for the row
    *   referenced by fk with a brief text description of the row, or an empty string
    *   if there is an error condition, including the absence of an implementation for
    *   the table in this function.
    */
   public function getLinkToRow() {
      $result = "";
      if($this->tablename=="omoccurrences") {
         $occ = new OmOccurrences();
         $occ->load($this->fk);
         $result = "<a href='../collections/individual/index.php?occid=$this->fk&clid=0'>" . htmlspecialchars($occ->getinstitutionCode(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . ":" . htmlspecialchars($occ->getcollectionCode(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . " " . htmlspecialchars($occ->getcatalogNumber(), ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a>";
      }
      if($this->tablename=="images") {
         $im = new ImageDetailManager($this->fk);
         $imArr = $im->getImageMetadata();
         if (isset($imArr['sciname'])) {
            $caption .= $imArr['sciname'];
         } elseif (isset($imArr['caption'])) {
            $caption .= $imArr['caption'];
         } elseif (isset($imArr['photographer'])) {
            $caption .= $imArr['photographer'];
         } else {
            $caption = $imArr['imagetype'];
         }
         $caption .= " " . $imArr['initialtimestamp'];
         $caption = trim($caption);
         $result = "<a href='../imagelib/imgdetails.php?imgid=$this->fk'>" .  htmlspecialchars($caption, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "</a>";
      }
      return $result;
   }

   /** To enable mysqli_stmt->bind_param using call_user_func_array($array)
    * allow $array to be converted to array of by references
    * if php version requires it.
    */
   public static function correctReferences($array) {
   	if (strnatcmp(phpversion(),'5.3') >= 0) {
   		$byrefs = array();
   		foreach($array as $key => $value)
   			$byrefs[$key] = &$array[$key];
   			return $byrefs;
   	}
   	return $byrefs;
   }
}

?>
