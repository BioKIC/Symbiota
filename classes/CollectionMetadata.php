<?php

use function PHPUnit\Framework\isEmpty;

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/CollectionMetadata.php
 * 
 */

 class CollectionMetadata extends Manager {
   
  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // Main functions

  // Gets biorepo collids from available collections
  public function getBiorepoCollsIds(){
    $dataArr = array();

    $sql = 'SELECT DISTINCT col.collid FROM omcollections AS col LEFT JOIN omcollcatlink AS l ON col.CollID = l.collid LEFT JOIN omcollcategories AS cat ON l.ccpk = cat.ccpk WHERE l.ccpk NOT IN (6,8) AND available = "TRUE"';
  
    $result = $this->conn->query($sql);

    while ($row = $result->fetch_assoc()){
      $dataArr[] = $row['collid'];
    }
    $result->free(); 
    
    $dataStr = implode(",", $dataArr);
    return $dataStr;
   
  }

  // Gets group of collections metadata based on category
  public function getCollMeta(){

    $dataArr = array();

    $sql = 'SELECT col.collid,  cat.category, col.collectioncode, col.collectionname FROM omcollections AS col LEFT JOIN omcollcatlink AS l ON col.CollID = l.collid LEFT JOIN omcollcategories AS cat ON l.ccpk = cat.ccpk';

    $result = $this->conn->query($sql);

    while ($row = $result->fetch_assoc()){
      $dataArr[] = $row;
    }
    $result->free(); 

    return $dataArr;
  }

    // Gets collections metadata filtered by category
  public function getCollMetaByCat($cat){
    // $sql = 'SELECT * FROM collmetadata WHERE collid = '.$collid.'';
    $dataArr = array();

    $sql = 'SELECT col.collid, col.collectioncode, col.institutioncode, col.collectionname FROM omcollections AS col LEFT JOIN omcollcatlink AS l ON col.CollID = l.collid LEFT JOIN omcollcategories AS cat ON l.ccpk = cat.ccpk WHERE cat.category = "'.$cat.'" ORDER BY col.collectionname;';

    $result = $this->conn->query($sql);
      while($row = $result->fetch_array()){
        $dataArr[] = $row;
      }
      $result->free(); 
    return $dataArr;
  }
    // Gets filtered biorepo collections metadata groups
  public function getBiorepoGroups($filterName){
    // $sql = 'SELECT * FROM collmetadata WHERE collid = '.$collid.'';
    $dataArr = array();

    $sql = 'SELECT col.collid,  col.collectioncode, col.institutioncode, col.collectionname FROM omcollections AS col LEFT JOIN omcollcatlink AS l ON col.CollID = l.collid WHERE l.ccpk NOT IN (6,8) GROUP BY '.$filterName.' ORDER BY '.$filterName.';';
    try{
      $result = $this->conn->query($sql);
      while($row = $result->fetch_array()){
        $dataArr[] = $row;
      }
      $result->free(); 
    } catch (Exception $e){
      echo 'Caught exception: ',  $e->getMessage(), "\n";

    }
    return $dataArr;
  }

  // Gets filtered biorepo collections 
  public function getBiorepoColls($filterName, $filterVal){
    // $sql = 'SELECT * FROM collmetadata WHERE collid = '.$collid.'';
    $dataArr = array();

    $sql = 'SELECT DISTINCT col.collid,  col.collectioncode, col.institutioncode, col.collectionname FROM omcollections AS col LEFT JOIN omcollcatlink AS l ON col.CollID = l.collid WHERE l.ccpk NOT IN (6,8) AND '.$filterName.'= "'.$filterVal.'" ORDER BY col.collectionname;';

    $result = $this->conn->query($sql);

      while($row = $result->fetch_array()){
        $dataArr[] = $row;
      }
      $result->free(); 
    return $dataArr;
  }
};

;?>