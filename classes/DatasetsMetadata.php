<?php 

  include_once($SERVER_ROOT.'/classes/Manager.php');

 /**
 * Controler class for /neon/classes/DatasetsMetadata.php
 * 
 */

 class DatasetsMetadata extends Manager {
   
  public function __construct() {
    parent::__construct(null,'readonly');
    $this->verboseMode = 2;
    set_time_limit(2000);
  }

  public function __destruct() {
    parent::__destruct();
  }

  // Main functions

  // Gets NEON Domains
  public function getNeonDomains(){
    $dataArr = array();

    $sql = 'SELECT d.name AS domainnumber, s.domainname, d.datasetid FROM omoccurdatasets AS d JOIN neon_field_sites AS s ON d.name = s.domainnumber GROUP BY domainnumber ORDER BY domainnumber;';

    $result = $this->conn->query($sql);

    while ($row = $result->fetch_assoc()){
      $dataArr[] = $row;
    }
    $result->free(); 
    return $dataArr;
  }

  // Gets NEON Sites filtered by Domain
  public function getNeonSitesByDom($domainnumber){
    $dataArr = array();

    $sql = 'SELECT siteid, sitename, domainnumber, datasetid FROM omoccurdatasets AS d JOIN neon_field_sites AS s ON d.name = s.siteid WHERE domainnumber = "'.$domainnumber.'" ORDER BY siteid;';

    $result = $this->conn->query($sql);

    while ($row = $result->fetch_assoc()){
      $dataArr[] = $row;
    }
    $result->free();
    return $dataArr;
  }
};

;?>