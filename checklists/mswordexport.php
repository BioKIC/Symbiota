<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
require_once($SERVER_ROOT.'/vendor/phpoffice/phpword/bootstrap.php');

header('Content-Type: text/html; charset='.$CHARSET);
ini_set('max_execution_time', 240); //240 seconds = 4 minutes

$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:0;
$dynClid = array_key_exists('dynclid',$_REQUEST)?$_REQUEST['dynclid']:0;
$pageNumber = array_key_exists('pagenumber',$_REQUEST)?$_REQUEST['pagenumber']:1;
$pid = array_key_exists('pid',$_REQUEST)?$_REQUEST['pid']:'';
$thesFilter = array_key_exists('thesfilter',$_REQUEST)?$_REQUEST['thesfilter']:0;
$taxonFilter = array_key_exists('taxonfilter',$_REQUEST)?$_REQUEST['taxonfilter']:'';
$showAuthors = array_key_exists('showauthors',$_REQUEST)?$_REQUEST['showauthors']:0;
$showSynonyms = array_key_exists('showsynonyms',$_REQUEST)?$_REQUEST['showsynonyms']:0;
$showCommon = array_key_exists('showcommon',$_REQUEST)?$_REQUEST['showcommon']:0;
$showImages = array_key_exists('showimages',$_REQUEST)?$_REQUEST['showimages']:0;
$showVouchers = array_key_exists('showvouchers',$_REQUEST)?$_REQUEST['showvouchers']:0;
$showAlphaTaxa = array_key_exists('showalphataxa',$_REQUEST)?$_REQUEST['showalphataxa']:0;
$searchCommon = array_key_exists('searchcommon',$_REQUEST)?$_REQUEST['searchcommon']:0;
$searchSynonyms = array_key_exists('searchsynonyms',$_REQUEST)?$_REQUEST['searchsynonyms']:0;

//Sanitation
if(!is_numeric($clid)) $clid = 0;
if(!is_numeric($dynClid)) $dynClid = 0;
if(!is_numeric($pid)) $pid = 0;
if(!is_numeric($pageNumber)) $pageNumber = 1;
if(!is_numeric($thesFilter)) $thesFilter = 0;
if(!preg_match('/^[a-z\-\s]+$/i', $taxonFilter)) $taxonFilter = '';
if(!is_numeric($showAuthors)) $showAuthors = 0;
if(!is_numeric($showSynonyms)) $showSynonyms = 0;
if(!is_numeric($showCommon)) $showCommon = 0;
if(!is_numeric($showImages)) $showImages = 0;
if(!is_numeric($showVouchers)) $showVouchers = 0;
if(!is_numeric($showAlphaTaxa)) $showAlphaTaxa = 0;
if(!is_numeric($searchCommon)) $searchCommon = 0;
if(!is_numeric($searchSynonyms)) $searchSynonyms = 0;

$clManager = new ChecklistManager();
if($clid){
	$clManager->setClid($clid);
}
elseif($dynClid){
	$clManager->setDynClid($dynClid);
}
$clArray = Array();
if($clid || $dynClid){
	$clArray = $clManager->getClMetaData();
}
$showDetails = 0;
if($pid) $clManager->setProj($pid);
elseif(array_key_exists('proj',$_REQUEST)) $pid = $clManager->setProj($_REQUEST['proj']);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
if($searchCommon){
	$showCommon = 1;
	$clManager->setSearchCommon();
}
if($searchSynonyms) $clManager->setSearchSynonyms(true);
if($showAuthors) $clManager->setShowAuthors(true);
if($showSynonyms) $clManager->setShowSynonyms(true);
if($showCommon) $clManager->setShowCommon(true);
if($showImages) $clManager->setShowImages(true);
if($showVouchers) $clManager->setShowVouchers(true);
if($showAlphaTaxa) $clManager->setShowAlphaTaxa(true);
$clid = $clManager->getClid();
$pid = $clManager->getPid();

$taxaArray = Array();
if($clid || $dynClid){
	$taxaArray = $clManager->getTaxaList($pageNumber,0);
}

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->addParagraphStyle('defaultPara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('titleFont', array('bold'=>true,'size'=>20,'name'=>'Arial'));
$phpWord->addFontStyle('topicFont', array('bold'=>true,'size'=>12,'name'=>'Arial'));
$phpWord->addFontStyle('textFont', array('size'=>12,'name'=>'Arial'));
$phpWord->addParagraphStyle('linePara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addParagraphStyle('familyPara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>225,'spaceAfter'=>75,'keepNext'=>true));
$phpWord->addFontStyle('familyFont', array('bold'=>true,'size'=>16,'name'=>'Arial'));
$phpWord->addParagraphStyle('scinamePara', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.3125,'spaceBefore'=>0,'spaceAfter'=>45,'keepNext'=>true));
$phpWord->addFontStyle('scientificnameFont', array('bold'=>true,'italic'=>true,'size'=>12,'name'=>'Arial'));
$phpWord->addParagraphStyle('synonymPara', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.78125,'spaceBefore'=>0,'spaceAfter'=>45));
$phpWord->addFontStyle('synonymFont', array('bold'=>false,'italic'=>true,'size'=>12,'name'=>'Arial'));
$phpWord->addParagraphStyle('notesvouchersPara', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.78125,'spaceBefore'=>0,'spaceAfter'=>45));
$phpWord->addParagraphStyle('imagePara', array('align'=>'center','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0));
$tableStyle = array('width'=>100);
$colRowStyle = array('cantSplit'=>true,'exactHeight'=>3750);
$phpWord->addTableStyle('imageTable',$tableStyle,$colRowStyle);
$imageCellStyle = array('valign'=>'center','width'=>2475,'borderSize'=>15,'borderColor'=>'808080');
$blankCellStyle = array('valign'=>'center','width'=>2475,'borderSize'=>15,'borderColor'=>'000000');

$domainRoot = $clManager->getDomain().$CLIENT_ROOT;
$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>1080,'marginRight'=>1080,'marginTop'=>1080,'marginBottom'=>1080,'headerHeight'=>0,'footerHeight'=>0));
$title = $clManager->getClName();
$clManager->cleanOutText($title);
$textrun = $section->addTextRun('defaultPara');
$textrun->addLink($domainRoot.'/checklists/checklist.php?clid='.$clid.'&pid='.$pid.'&dynclid='.$dynClid, $title,'titleFont');
$textrun->addTextBreak(1);
if($clid){
	if($clArray['type'] == 'rarespp'){
		$locality = $clManager->cleanOutText($clArray['locality']);
		$textrun->addText('Sensitive species checklist for: ','topicFont');
		$textrun->addText($locality,'textFont');
		$textrun->addTextBreak(1);
	}
	$authors = $clManager->cleanOutText($clArray['authors']);
	$textrun->addText('Authors: ','topicFont');
	$textrun->addText($authors,'textFont');
	$textrun->addTextBreak(1);
	if($clArray['publication']){
		$publication = $clManager->cleanOutText($clArray['publication']);
		$textrun->addText('Publication: ','topicFont');
		$textrun->addText($publication,'textFont');
		$textrun->addTextBreak(1);
	}
}
if(isset($clArray['locality']) || ($clid && isset ($clArray['latcentroid']) || isset ($clArray['abstract']) || isset($clArray['notes']))){
	$locStr = $clManager->cleanOutText($clArray['locality']);
	if($clid && $clArray['latcentroid']) $locStr .= ' ('.$clArray['latcentroid'].', '.$clArray['longcentroid'].')';
	if($locStr){
		$textrun->addText('Locality: ','topicFont');
		$textrun->addText($locStr,'textFont');
		$textrun->addTextBreak(1);
	}
	if($clid && isset ($clArray['abstract'])){
		$abstract = $clManager->cleanOutText($clArray['abstract']);
		$textrun->addText('Abstract: ', 'topicFont');
		$textrun->addText($abstract, 'textFont');
		$textrun->addTextBreak(1);
	}
	if($clid && isset ($clArray['notes'])){
		$notes = $clManager->cleanOutText($clArray['notes']);
		$textrun->addText('Notes: ', 'topicFont');
		$textrun->addText($notes, 'textFont');
		$textrun->addTextBreak(1);
	}
}
$textrun = $section->addTextRun('linePara');
$textrun->addLine(array('weight'=>1,'width'=>670,'height'=>0));
$textrun = $section->addTextRun('defaultPara');
$textrun->addText('Families: ','topicFont');
$textrun->addText($clManager->getFamilyCount(),'textFont');
$textrun->addTextBreak(1);
$textrun->addText('Genera: ', 'topicFont');
$textrun->addText($clManager->getGenusCount(), 'textFont');
$textrun->addTextBreak(1);
$textrun->addText('Species: ', 'topicFont');
$textrun->addText($clManager->getSpeciesCount(), 'textFont');
$textrun->addTextBreak(1);
$textrun->addText('Total Taxa: ', 'topicFont');
$textrun->addText($clManager->getTaxaCount(), 'textFont');
$textrun->addTextBreak(1);
$prevfam = '';
if($showImages){
	$imageCnt = 0;
	$table = $section->addTable('imageTable');
	foreach($taxaArray as $tid => $sppArr){
		$imageCnt++;
		$family = $sppArr['family'];
		$tu = (array_key_exists('tnurl',$sppArr)?$sppArr['tnurl']:'');
		$u = (array_key_exists('url',$sppArr)?$sppArr['url']:'');
		$imgSrc = ($tu?$tu:$u);
		if($imageCnt%4 == 1) $table->addRow();
		if($imgSrc){
			$imgSrc = (array_key_exists('imageDomain',$GLOBALS)&&substr($imgSrc,0,4)!='http'?$GLOBALS['imageDomain']:'').$imgSrc;
			$cell = $table->addCell(null,$imageCellStyle);
			$textrun = $cell->addTextRun('imagePara');
			$textrun->addImage($imgSrc,array('width'=>160,'height'=>160));
			$textrun->addTextBreak(1);
			$textrun->addLink($domainRoot.'/taxa/index.php?taxauthid=1&taxon='.$tid.'&clid='.$clid, htmlspecialchars($sppArr['sciname']), 'topicFont');
			$textrun->addTextBreak(1);
			if(array_key_exists('vern',$sppArr)){
				$vern = $clManager->cleanOutText($sppArr['vern']);
				$textrun->addText($vern, 'topicFont');
				$textrun->addTextBreak(1);
			}
			if(!$showAlphaTaxa){
				if($family != $prevfam){
					$textrun->addLink($domainRoot.'/taxa/index.php?taxauthid=1&taxon='.$family.'&clid='.$clid, htmlspecialchars('['.$family.']'), 'textFont');
					$prevfam = $family;
				}
			}
		}
		else{
			$cell = $table->addCell(null,$blankCellStyle);
			$textrun = $cell->addTextRun('imagePara');
			$textrun->addText('Image', 'topicFont');
			$textrun->addTextBreak(1);
			$textrun->addText('not yet', 'topicFont');
			$textrun->addTextBreak(1);
			$textrun->addText('available', 'topicFont');
		}
	}
}
else{
	$voucherArr = $clManager->getVoucherArr();
	foreach($taxaArray as $tid => $sppArr){
		if(!$showAlphaTaxa){
			$family = $sppArr['family'];
			if($family != $prevfam){
				$textrun = $section->addTextRun('familyPara');
				$textrun->addLink($domainRoot.'/taxa/index.php?taxauthid=1&taxon='.$family.'&clid='.$clid, $family, 'familyFont');
				$prevfam = $family;
			}
		}
		$textrun = $section->addTextRun('scinamePara');
		$textrun->addLink($domainRoot.'/taxa/index.php?taxauthid=1&taxon='.$tid.'&clid='.$clid, $sppArr['sciname'], 'scientificnameFont');
		if(array_key_exists('author', $sppArr)){
			$sciAuthor = $clManager->cleanOutText($sppArr['author']);
			$textrun->addText(' '.$sciAuthor, 'textFont');
		}
		if(array_key_exists('vern',$sppArr)){
			$vern = $clManager->cleanOutText($sppArr['vern']);
			$textrun->addText(' - '.$vern, 'topicFont');
		}
		if(isset($sppArr['syn']) && $sppArr['syn']){
			$textrun = $section->addTextRun('synonymPara');
			$textrun->addText('[','textFont');
			$textrun->addText($clManager->cleanOutText($sppArr['syn']), 'synonymFont');
			$textrun->addText(']','textFont');
		}
		if($showVouchers){
			if(array_key_exists('notes',$sppArr) || array_key_exists($tid,$voucherArr)){
				$textrun = $section->addTextRun('notesvouchersPara');
			}
			if(array_key_exists('notes',$sppArr)){
				$noteStr = $clManager->cleanOutText($sppArr['notes']);
				$textrun->addText($noteStr.($noteStr && array_key_exists($tid,$voucherArr)?'; ':''), 'textFont');
			}
			if(array_key_exists($tid,$voucherArr)){
				$i = 0;
				foreach($voucherArr[$tid] as $occid => $collName){
					if($i > 0) $textrun->addText(', ', 'textFont');
					$voucStr = $clManager->cleanOutText($collName);
					$textrun->addLink($domainRoot.'/collections/individual/index.php?occid='.$occid, $voucStr, 'textFont');
					$i++;
				}
			}
		}
	}
}
$fileName = str_replace(array(' ', '/', '.'), '_', $clManager->getClName() ?? "");
$fileName = preg_replace('/[^0-9A-Za-z\-]/', '', $fileName);
if(strlen($fileName) > 30) $fileName = substr($fileName, 0, 30);
$targetFile = $SERVER_ROOT.'/temp/report/'.$fileName.'_'.date('Y-m-d').'.docx';
$phpWord->save($targetFile, 'Word2007');

ob_start();
ob_clean();
ob_end_flush();
header('Content-Description: File Transfer');
header('Content-type: application/force-download');
header('Content-Disposition: attachment; filename='.basename($targetFile));
header('Content-Transfer-Encoding: binary');
header('Content-Length: '.filesize($targetFile));
readfile($targetFile);
unlink($targetFile);
?>