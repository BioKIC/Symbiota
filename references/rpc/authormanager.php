<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ReferenceManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$uid = array_key_exists("uid",$_REQUEST)?$_REQUEST["uid"]:'';
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
$refId = array_key_exists('refid',$_REQUEST)?$_REQUEST['refid']:0;
$refAuthId = array_key_exists("refauthid",$_REQUEST)?$_REQUEST["refauthid"]:0;
$firstName = array_key_exists("firstname",$_REQUEST)?$_REQUEST["firstname"]:'';
$middleName = array_key_exists("midname",$_REQUEST)?$_REQUEST["midname"]:'';
$lastName = array_key_exists("lastname",$_REQUEST)?$_REQUEST["lastname"]:'';
$table = array_key_exists("table",$_REQUEST)?$_REQUEST["table"]:'';
$field = array_key_exists("field",$_REQUEST)?$_REQUEST["field"]:'';
$id = array_key_exists("id",$_REQUEST)?$_REQUEST["id"]:'';
$type = array_key_exists("type",$_REQUEST)?$_REQUEST["type"]:'';

$refManager = new ReferenceManager();
if($action=="addauthor"){
	$refManager->addAuthor($refId,$refAuthId);
	$authArr = $refManager->getRefAuthArr($refId);
	$listHtml = '';
	if($authArr){
		$listHtml .= '<ul>';
		foreach($authArr as $k => $v){
			$listHtml .= '<li>';
			$listHtml .= '<a href="authoreditor.php?authid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
			$listHtml .= ' <input type="image" style="style="width:1.3em;margin-left:5px;" src="../images/del.png" onclick="deleteRefAuthor('.$k.');" title="Delete author">';
			$listHtml .= '</li>';
		}
		$listHtml .= '</ul>';
	}
	else{
		$listHtml .= '<div><b>There are currently no authors connected with this reference.</b></div>';
	}
	echo $listHtml;
}
if($action=="createauthor"){
	$refManager->createAuthor($firstName,$middleName,$lastName);
	$refAuthId = $refManager->getRefAuthId();
	$refManager->addAuthor($refId,$refAuthId);
	$authArr = $refManager->getRefAuthArr($refId);
	$listHtml = '';
	if($authArr){
		$listHtml .= '<ul>';
		foreach($authArr as $k => $v){
			$listHtml .= '<li>';
			$listHtml .= '<a href="authoreditor.php?authid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .'" target="_blank">' . htmlspecialchars($v, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
			$listHtml .= ' <input type="image" style="width:1.3em;margin-left:5px;" src="../images/del.png" onclick="deleteRefAuthor('.$k.');" title="Delete author">';
			$listHtml .= '</li>';
		}
		$listHtml .= '</ul>';
	}
	else{
		$listHtml .= '<div><b>There are currently no authors connected with this reference.</b></div>';
	}
	echo $listHtml;
}
if($action=="deleterefauthor"){
	$refManager->deleteRefAuthor($refId,$refAuthId);
	$authArr = $refManager->getRefAuthArr($refId);
	$listHtml = '';
	if($authArr){
		$listHtml .= '<ul>';
		foreach($authArr as $k => $v){
			$listHtml .= '<li>';
			$listHtml .= '<a href="authoreditor.php?authid=' . htmlspecialchars($k, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '" target="_blank">' . htmlspecialchars($v, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a>';
			$listHtml .= ' <input type="image" style="width:1.3em;margin-left:5px;" src="../images/del.png" onclick="deleteRefAuthor('.$k.');" title="Delete author">';
			$listHtml .= '</li>';
		}
		$listHtml .= '</ul>';
	}
	else{
		$listHtml .= '<div><b>There are currently no authors connected with this reference.</b></div>';
	}
	echo $listHtml;
}
if($action=="deletereflink"){
	$refManager->deleteRefLink($refId,$table,$field,$id);
	$authArr = $refManager->getRefAuthArr($refId);
	$listHtml = '';
	if($authArr){
		$listHtml .= '<ul>';
		foreach($authArr as $k => $v){
			$onClick = "deleteRefLink('".$table."','".$field."','".$type."',".$k.");";
			$listHtml .= '<li>';
			$listHtml .= $v;
			$listHtml .= '</li>';
		}
		$listHtml .= '</ul>';
	}
	else{
		$listHtml .= 'There are no '.$type.' linked with this reference';
	}
	echo $listHtml;
}
?>