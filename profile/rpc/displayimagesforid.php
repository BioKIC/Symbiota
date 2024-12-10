<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageExplorer.php');

$imageExplorer = new ImageExplorer();
$imgArr = $imageExplorer->getImages($_POST);

echo '<div style="clear:both;">';
echo '<input type="hidden" id="imgCnt" value="'.$imgArr['cnt'].'" />';

unset($imgArr['cnt']);

foreach($imgArr as $imgArr){
	$imgId = $imgArr['imgid'];
	$imgUrl = $imgArr['url'];
	$imgTn = $imgArr['thumbnailurl'];
	if($imgTn){
		$imgUrl = $imgTn;
		if($MEDIA_DOMAIN && substr($imgTn,0,1)=='/'){
			$imgUrl = $MEDIA_DOMAIN . $imgTn;
		}
	}
	elseif($MEDIA_DOMAIN && substr($imgUrl,0,1)=='/'){
		$imgUrl = $MEDIA_DOMAIN . $imgUrl;
	}
	?>

	<div class="tndiv" style="margin-top: 15px; margin-bottom: 15px">
		<div class="tnimg">
			<a href="imgdetails.php?mediaid=<?php echo htmlspecialchars($imgId, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>">
				<img src="<?php echo $imgUrl; ?>" />
			</a>
		</div>
		<div>
			<?php echo "<a href=\"../collections/editor/occurrenceeditor.php?q_customtypeone=EQUALS&occid=". htmlspecialchars($imgArr['occid'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .  "\">". htmlspecialchars($imgArr['instcode'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . " - " . htmlspecialchars($imgArr['catalognumber'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) .  "<br />" . htmlspecialchars($imgArr['sciname'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . "<br />" . htmlspecialchars($imgArr['stateprovince'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
			</a>
		</div>
	</div>
	<?php
}
echo "</div>";
?>
