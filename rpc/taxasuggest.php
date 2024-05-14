<?php
/*
 * Input: term = scientific name fragment, taxonType, $rankLow = rankid lower limit, $rankHigh = rankid upper limit
 * Return: autosuggest return list
 */
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonSearchSupport.php');
header('Content-Type: application/json; charset='.$CHARSET);
include_once($SERVER_ROOT . '/rpc/crossPortalHeaders.php');

$term = (array_key_exists('term',$_REQUEST)?$_REQUEST['term']:'');
$taxonType = (array_key_exists('t',$_REQUEST)?$_REQUEST['t']:0);
$rankLow = (array_key_exists('ranklow',$_REQUEST)?$_REQUEST['ranklow']:0);
$rankHigh = (array_key_exists('rankhigh',$_REQUEST)?$_REQUEST['rankhigh']:0);

$nameArr = array();
if($term){
   if(isset($DEFAULT_TAXON_SEARCH) && !$taxonType) $taxonType = $DEFAULT_TAXON_SEARCH;
   $searchManager = new TaxonSearchSupport();
   $searchManager->setQueryString($term);
   $searchManager->setTaxonType($taxonType);
   $searchManager->setRankLow($rankLow);
   $searchManager->setRankHigh($rankHigh);

   $nameArr = $searchManager->getTaxaSuggest();
}
echo json_encode($nameArr);
?>
