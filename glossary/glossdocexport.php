<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
require_once $SERVER_ROOT.'/vendor/phpoffice/phpword/bootstrap.php';

header('Content-Type: text/html; charset=' . $CHARSET);
ini_set('max_execution_time', 3600);

$language = array_key_exists('searchlanguage',$_POST)?$_POST['searchlanguage']:'';
$taxon = array_key_exists('searchtaxa',$_POST)?$_POST['searchtaxa']:'';
$searchTerm = array_key_exists('searchterm',$_REQUEST)?$_REQUEST['searchterm']:'';
$deepSearch = array_key_exists('deepsearch',$_POST)?$_POST['deepsearch']:0;
$exportType = array_key_exists('exporttype',$_POST)?$_POST['exporttype']:'';
$translations = array_key_exists('language',$_POST)?$_POST['language']:array();
$definitions = array_key_exists('definitions',$_POST)?$_POST['definitions']:'';
$images = array_key_exists('images',$_POST)?$_POST['images']:'';

//Sanitation
$language = htmlspecialchars($language, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$taxon = htmlspecialchars($taxon, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$searchTerm = htmlspecialchars($searchTerm, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
if(!is_numeric($deepSearch)) $relatedLanguage = 0;
$exportType = htmlspecialchars($exportType, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$definitions = htmlspecialchars($definitions, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);
$images = htmlspecialchars($images, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE);

$fileName = '';
$citationFormat = $DEFAULT_TITLE.'. '.date('Y').'. ';
$citationFormat .= 'http//:'.$_SERVER['HTTP_HOST'].$CLIENT_ROOT.(substr($CLIENT_ROOT,-1)=='/'?'':'/').'index.php. ';
$citationFormat .= 'Accessed on '.date('F d').'. ';

$phpWord = new \PhpOffice\PhpWord\PhpWord();
$phpWord->addParagraphStyle('titlePara', array('align'=>'center','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('titleFont', array('bold'=>true,'size'=>16,'name'=>'Microsoft Sans Serif'));
$phpWord->addParagraphStyle('transTermPara', array('align'=>'left','lineHeight'=>1.0,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('transTermTopicNodefFont', array('bold'=>true,'size'=>15,'name'=>'Microsoft Sans Serif'));
$phpWord->addFontStyle('transTermTopicDefFont', array('bold'=>true,'size'=>14,'name'=>'Microsoft Sans Serif'));
$phpWord->addParagraphStyle('transDefPara', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.78125,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addParagraphStyle('transDefList', array('align'=>'left','lineHeight'=>1.0,'indent'=>0.78125,'spaceBefore'=>0,'spaceAfter'=>0,'keepNext'=>true));
$phpWord->addFontStyle('transTableHeaderNodeFont', array('bold'=>false,'size'=>12,'underline'=>'single','name'=>'Microsoft Sans Serif','color'=>'000000'));
$phpWord->addFontStyle('transMainTermNodefFont', array('bold'=>false,'size'=>12,'name'=>'Microsoft Sans Serif','color'=>'21304B'));
$phpWord->addFontStyle('transTransTermNodefFont', array('bold'=>false,'size'=>12,'name'=>'Microsoft Sans Serif','color'=>'000000'));
$phpWord->addFontStyle('transMainTermDefFont', array('bold'=>true,'size'=>12,'name'=>'Microsoft Sans Serif','color'=>'21304B'));
$phpWord->addFontStyle('transTransTermDefFont', array('bold'=>true,'size'=>12,'name'=>'Microsoft Sans Serif','color'=>'000000'));
$phpWord->addFontStyle('transDefTextFont', array('bold'=>false,'size'=>12,'name'=>'Microsoft Sans Serif','color'=>'000000'));
$tableStyle = array('width'=>100,'cellMargin'=>60);
$colRowStyle = array('cantSplit'=>true,'exactHeight'=>180);
$phpWord->addTableStyle('exportTable',$tableStyle,$colRowStyle);
$nodefCellStyle = array('valign'=>'center','width'=>2520,'borderSize'=>0,'borderColor'=>'ffffff');
$imageCellStyle = array('valign'=>'top','width'=>2520,'borderSize'=>0,'borderColor'=>'ffffff');

$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>1080,'marginRight'=>1080,'marginTop'=>1080,'marginBottom'=>1080,'headerHeight'=>100,'footerHeight'=>0));
$glosManager = new GlossaryManager();
$subTitle = '';
if($language) $subTitle .= 'language: '.$language;
if($searchTerm) $subTitle .= '; search term: '.$searchTerm;
if($exportType == 'translation'){
	$exportArr = $glosManager->getExportArr($language, $taxon, $searchTerm, $deepSearch, 0, $translations, $definitions);
	if(in_array($language,$translations)){
		foreach($translations as $k => $trans){
			if($trans == $language) unset($translations[$k]);
		}
	}
	if($exportArr){
		$metaArr = $exportArr['meta'];
		unset($exportArr['meta']);

		//ksort($exportArr, SORT_STRING | SORT_FLAG_CASE);
		$fileName = 'GlossaryTranslation';
		$sciname = '';
		if(isset($metaArr['sciname'])){
			$sciname = $metaArr['sciname'];
			$fileName .= '_'.$sciname;
		}

		$header = $section->addHeader();
		$header->addPreserveText($sciname.' - p.{PAGE} '.date("Y-m-d"),null,array('align'=>'right'));
		$textrun = $section->addTextRun('titlePara');
		if(isset($GLOSSARY_BANNER) && $GLOSSARY_BANNER){
			$textrun->addImage($glosManager->getDomain() . $CLIENT_ROOT . '/images/layout/' . $GLOSSARY_BANNER, array('width'=>500, 'align'=>'center'));
			$textrun->addTextBreak(1);
		}
		$titleStr = 'Translation Table';
		if($sciname) $titleStr .= ' for '.$sciname;
		$textrun->addText(htmlspecialchars($titleStr),'titleFont');
		if($subTitle) $textrun->addText(' ('.trim($subTitle).')', 'transDefTextFont');
		$textrun->addTextBreak(1);
		if($definitions == 'nodef'){
			$table = $section->addTable('exportTable');
			$table->addRow();
			$cell = $table->addCell(2520,$nodefCellStyle);
			$textrun = $cell->addTextRun('transTermPara');
			$textrun->addText(htmlspecialchars($language),'transTableHeaderNodeFont');
			foreach($translations as $trans){
				$cell = $table->addCell(2520,$nodefCellStyle);
				$textrun = $cell->addTextRun('transTermPara');
				$textrun->addText(htmlspecialchars($trans),'transTableHeaderNodeFont');
			}
			foreach($exportArr as $glossId => $glossArr){
				$table->addRow();
				$cell = $table->addCell(2520,$nodefCellStyle);
				$textrun = $cell->addTextRun('transTermPara');
				$textrun->addText(htmlspecialchars($glossArr['term']),'transMainTermNodefFont');
				foreach($translations as $trans){
					$cell = $table->addCell(2520,$nodefCellStyle);
					$textrun = $cell->addTextRun('transTermPara');
					$termStr = '[No Translation]';
					if(array_key_exists('trans', $glossArr)){
						if(array_key_exists($trans,$glossArr['trans'])){
							$termStr = $glossArr['trans'][$trans]['term'];
						}
					}
					$textrun->addText(htmlspecialchars($termStr),'transTransTermNodefFont');
				}
			}
		}
		else{
			$textrun->addTextBreak(1);
			foreach($exportArr as $glossId => $glossArr){
				$textrun = $section->addTextRun('transTermPara');
				$textrun->addText(htmlspecialchars($glossArr['term']),'transMainTermDefFont');
				$translationStr = '';
				foreach($translations as $trans){
					$termStr = '[No Translation]';
					if(array_key_exists('trans', $glossArr)){
						if(array_key_exists($trans,$glossArr['trans'])){
							$termStr = $glossArr['trans'][$trans]['term'];
						}
					}
					$translationStr .= $trans.': '.$termStr.', ';
				}
				if($translationStr) $textrun->addText(htmlspecialchars(' ('.trim($translationStr,', ').')'), 'transTransTermNodefFont');
				if($definitions == 'onedef'){
					if($glossArr['definition']){
						$textrun = $section->addTextRun('transDefPara');
						$textrun->addText(htmlspecialchars($glossArr['definition']),'transDefTextFont');
						$section->addTextBreak(1);
					}
				}
				elseif($definitions == 'alldef'){
					$listItemRun = $section->addListItemRun(0, null, 'transDefList');
					if($glossArr['definition']){
						$listItemRun->addText(htmlspecialchars($glossArr['definition']), 'transDefTextFont');
					}
					else{
						$listItemRun->addText(htmlspecialchars('[No Definition]'), 'transDefTextFont');
					}
					foreach($translations as $trans){
						$listItemRun = $section->addListItemRun(0, null, 'transDefList');

						if(array_key_exists('trans', $glossArr)){
							if(array_key_exists($trans,$glossArr['trans'])){
								$listItemRun->addText(htmlspecialchars($glossArr['trans'][$trans]['definition']),'transDefTextFont');
							}
						}
						else{
							$listItemRun->addText(htmlspecialchars('[No Definition]'),'transDefTextFont');
						}
					}
					$section->addTextBreak(1);
				}
			}
		}
		if(isset($metaArr['references'])){
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('titlePara');
			$textrun->addText(htmlspecialchars('References'),'transTransTermDefFont');
			$referencesArr = $metaArr['references'];
			ksort($referencesArr);
			foreach($referencesArr as $ref){
				$listItemRun = $section->addListItemRun(0,null,'transDefList');
				$listItemRun->addText(htmlspecialchars($ref),'transDefTextFont');
			}
		}
		if(isset($metaArr['contributors'])){
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('titlePara');
			$textrun->addText(htmlspecialchars('Contributors'),'transTransTermDefFont');
			$contributorsArr = $metaArr['contributors'];
			ksort($contributorsArr);
			foreach($contributorsArr as $cont){
				$listItemRun = $section->addListItemRun(0,null,'transDefList');
				$listItemRun->addText(htmlspecialchars($cont),'transDefTextFont');
			}
		}
		if(isset($metaArr['imgcontributors'])){
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('titlePara');
			$textrun->addText(htmlspecialchars('Image Contributors'),'transTransTermDefFont');
			$imgcontributorsArr = $metaArr['imgcontributors'];
			ksort($imgcontributorsArr);
			foreach($imgcontributorsArr as $cont){
				$listItemRun = $section->addListItemRun(0,null,'transDefList');
				$listItemRun->addText(htmlspecialchars($cont),'transDefTextFont');
			}
		}
		$section->addTextBreak(1);
		$textrun = $section->addTextRun('titlePara');
		$textrun->addText(htmlspecialchars('How to Cite Us'),'transTransTermDefFont');
		$textrun = $section->addTextRun('transTermPara');
		$textrun->addText(htmlspecialchars($citationFormat),'transTransTermNodefFont');
	}
}
else{
	$exportArr = $glosManager->getExportArr($language, $taxon, $searchTerm, $deepSearch, $images);
	if($exportArr){
		$metaArr = $exportArr['meta'];
		unset($exportArr['meta']);
		//ksort($exportArr, SORT_STRING | SORT_FLAG_CASE);
		$fileName = 'Glossary';
		$sciname = '';
		if(isset($metaArr['sciname'])){
			$sciname = $metaArr['sciname'];
			$fileName .= '_'.$sciname;
		}

		$header = $section->addHeader();
		$header->addPreserveText($sciname.' - p.{PAGE} '.date("Y-m-d"),null,array('align'=>'right'));
		$textrun = $section->addTextRun('titlePara');
		if(isset($GLOSSARY_BANNER) && $GLOSSARY_BANNER){
			$textrun->addImage($glosManager->getDomain() . $CLIENT_ROOT . '/images/layout/' . $GLOSSARY_BANNER, array('width'=>500, 'align'=>'center'));
			$textrun->addTextBreak(1);
		}
		$titleStr = 'Glossary';
		if($sciname) $titleStr .= 'for '.$sciname;
		$textrun->addText(htmlspecialchars($titleStr),'titleFont');
		if($subTitle) $textrun->addText(' ('.trim($subTitle).')', 'transDefTextFont');
		$textrun->addTextBreak(1);
		foreach($exportArr as $singleEx => $singleExArr){
			$textrun = $section->addTextRun('transTermPara');
			$textrun->addText(htmlspecialchars($singleExArr['term']), 'transMainTermDefFont');
			if(isset($singleExArr['searchTerm']) && $singleExArr['searchTerm'] != $singleExArr['term']){
				$textrun->addText(' redirected from ', 'transDefTextFont');
				$textrun->addText(htmlspecialchars($singleExArr['searchTerm']), 'transMainTermDefFont');
			}
			if($singleExArr['definition']){
				$textrun = $section->addTextRun('transDefPara');
				$textrun->addText(htmlspecialchars($singleExArr['definition']),'transDefTextFont');
			}
			if(!$images || ($images && !array_key_exists('images',$singleExArr))){
				$textrun->addTextBreak(1);
			}
			if($images && array_key_exists('images',$singleExArr)){
				$imageArr = $singleExArr['images'];
				$table = $section->addTable('exportTable');
				foreach($imageArr as $img => $imgArr){
					$imgSrc = $imgArr["url"];
					if(substr($imgSrc,0,1)=="/"){
						if(!empty($GLOBALS['IMAGE_DOMAIN'])){
							$imgSrc = $GLOBALS['IMAGE_DOMAIN'].$imgSrc;
						}
						else{
							$imgSrc = 'http://'.$_SERVER['HTTP_HOST'].$imgSrc;
						}
					}
					$table->addRow();
					$cell = $table->addCell(4125,$imageCellStyle);
					$textrun = $cell->addTextRun('transDefPara');
					@$imgSize = getimagesize(str_replace(' ', '%20', $imgSrc));
					if($imgSize){
						$width = $imgSize[0];
						$height = $imgSize[1];
						if($width > $height){
							$targetWidth = $width;
							if($width > 230) $targetWidth = 230;
							$textrun->addImage($imgSrc,array('width'=>$targetWidth));
						}
						else{
							$targetHeight = $height;
							if($height > 170) $targetHeight = 170;
							$textrun->addImage($imgSrc,array('height'=>$targetHeight));
						}
						$cell = $table->addCell(5625,$imageCellStyle);
						$textrun = $cell->addTextRun('transTermPara');
						if($imgArr["createdBy"]){
							$textrun->addText(htmlspecialchars('Image courtesy of: '),'transTransTermDefFont');
							$textrun->addText(htmlspecialchars($imgArr["createdBy"]),'transDefTextFont');
							$textrun->addTextBreak(2);
						}
						if($imgArr["structures"]){
							$textrun->addText(htmlspecialchars('Structures: '),'transTransTermDefFont');
							$textrun->addText(htmlspecialchars($imgArr["structures"]),'transDefTextFont');
							$textrun->addTextBreak(2);
						}
						if($imgArr["notes"]){
							$textrun->addText(htmlspecialchars('Notes: '),'transTransTermDefFont');
							$textrun->addText(htmlspecialchars($imgArr["notes"]),'transDefTextFont');
						}
					}
				}
			}
		}
		if(isset($metaArr['references'])){
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('titlePara');
			$textrun->addText('References', 'transTransTermDefFont');
			$referencesArr = $metaArr['references'];
			ksort($referencesArr);
			foreach($referencesArr as $ref){
				$listItemRun = $section->addListItemRun(0,null,'transDefList');
				$listItemRun->addText(htmlspecialchars($ref),'transDefTextFont');
			}
		}
		if(isset($metaArr['contributors'])){
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('titlePara');
			$textrun->addText(htmlspecialchars('Contributors'),'transTransTermDefFont');
			$contributorsArr = $metaArr['contributors'];
			ksort($contributorsArr);
			foreach($contributorsArr as $cont){
				$listItemRun = $section->addListItemRun(0,null,'transDefList');
				$listItemRun->addText(htmlspecialchars($cont),'transDefTextFont');
			}
		}
		if(isset($metaArr['imgcontributors'])){
			$section->addTextBreak(1);
			$textrun = $section->addTextRun('titlePara');
			$textrun->addText(htmlspecialchars('Image Contributors'),'transTransTermDefFont');
			$imgcontributorsArr = $metaArr['imgcontributors'];
			ksort($imgcontributorsArr);
			foreach($imgcontributorsArr as $cont){
				$listItemRun = $section->addListItemRun(0,null,'transDefList');
				$listItemRun->addText(htmlspecialchars($cont),'transDefTextFont');
			}
		}
		$section->addTextBreak(1);
		$textrun = $section->addTextRun('titlePara');
		$textrun->addText(htmlspecialchars('How to Cite Us'),'transTransTermDefFont');
		$textrun = $section->addTextRun('transTermPara');
		$textrun->addText(htmlspecialchars($citationFormat),'transTransTermNodefFont');
	}
}

$fileName = str_replace(array(' ', '/'), '_', $fileName);
$fileName = preg_replace('/[^0-9A-Za-z\-_]/', '', $fileName);
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