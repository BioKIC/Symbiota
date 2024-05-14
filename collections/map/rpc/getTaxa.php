<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/APITaxonomy.php');
include_once($SERVER_ROOT.'/classes/MapSupport.php');

header('Content-Type: application/json');

$tid = isset($_REQUEST['tid']) ? filter_var($_REQUEST['tid'], FILTER_SANITIZE_NUMBER_INT): 0;
$scinames = isset($_REQUEST['scinames']) ? $_REQUEST['scinames']: false;

$retArr = [];
if($IS_ADMIN) {
   $scinames = explode(",", $scinames);
   $taxonAPI = new APITaxonomy();
   $mapManager = new MapSupport();

   foreach($scinames as $sciname) {
      $taxon = $taxonAPI->getTaxon(trim($sciname));
      if(!empty($taxon)) {
         foreach($taxon as $tid => $taxon_info) {
            $taxa_list = $mapManager->getTaxaList($tid);
            if(!empty($taxa_list)) {
               array_push($retArr, [
                  "tid" => $tid, 
                  "sciname" => $taxon_info['sciname'], 
                  'taxa_list' => $taxa_list
               ]);
            }
         }
      }
   }
}
echo json_encode($retArr);
?>
