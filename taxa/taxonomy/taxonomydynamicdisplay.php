<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyDisplayManager.php');
if($LANG_TAG != 'en' && file_exists($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydisplay.' . $LANG_TAG . '.php'))
	include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydisplay.' . $LANG_TAG . '.php');
	else include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomydisplay.en.php');
header('Content-Type: text/html; charset=' . $CHARSET);

$target = $_REQUEST['target'] ?? '';
$displayAuthor = !empty($_REQUEST['displayauthor']) ? 1: 0;
$limitToOccurrences = !empty($_REQUEST['limittooccurrences']) ? 1 : 0;
$taxAuthId = array_key_exists('taxauthid', $_REQUEST) ? filter_var($_REQUEST['taxauthid'], FILTER_SANITIZE_NUMBER_INT) : 1;
$editorMode = !empty($_REQUEST['emode']) ? 1 : 0;
$submitAction = array_key_exists('tdsubmit', $_POST) ? $_POST['tdsubmit'] : '';
$statusStr = $_REQUEST['statusstr'] ?? '';

$taxonDisplayObj = new TaxonomyDisplayManager();
$taxonDisplayObj->setTargetStr($target);
$taxonDisplayObj->setTaxAuthId($taxAuthId);

if($submitAction){
	if($submitAction == 'exportTaxonTree'){
		$taxonDisplayObj->setDisplayFullTree(1);
		$taxonDisplayObj->setLimitToOccurrences($limitToOccurrences);
		$taxonDisplayObj->exportCsv();
		exit;
	}
}

$isEditor = false;
if($IS_ADMIN || array_key_exists('Taxonomy', $USER_RIGHTS)){
	$isEditor = true;
	$editorMode = 1;
	if(array_key_exists('target', $_POST) && !array_key_exists('emode', $_POST)) $editorMode = 0;
}

$treePath = $taxonDisplayObj->getDynamicTreePath();
$targetId = end($treePath);
reset($treePath);
//echo json_encode($treePath);
?>
<!Doctype html>
<html lang="<?php echo $LANG_TAG ?>">
<head>
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['TAX_EXPLORE'] . ': ' . $taxonDisplayObj->getTargetStr(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT.'/includes/head.php');
	include_once($SERVER_ROOT.'/includes/googleanalytics.php');
	?>
	<link rel="stylesheet" href="../../js/dojo-1.17.3/dijit/themes/claro/claro.css" media="screen">
	<style>
		.dijitLeaf,
		.dijitIconLeaf,
		.dijitFolderClosed,
		.dijitIconFolderClosed,
		.dijitFolderOpened,
		.dijitIconFolderOpen {
			background-image: none;
			width: 0px;
			height: 0px;
		}
		.fieldset-size {
			padding: 10px;
			max-width: 600px;
		}
		.icon-image{ border: 0px; width: 15px; }
		.tax-meta-arr {
			float: left;
			margin: 1rem 0rem 2.5rem 0rem;
			font-weight: bold;
			font-size: 120%;
		}
		.tax-detail-div {
			margin-top: 1.35rem;
			margin-left: 0.7rem;
			float: left;
			font-size: 80%;
		}
		.tax-meta-div {
			margin: 1rem 1.35rem 3rem 1.35rem;
			display: none;
			clear: both;
		}
	</style>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="../../js/dojo-1.17.3/dojo/dojo.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			$("#taxontarget").autocomplete({
				source: function( request, response ) {
					$.getJSON( "rpc/gettaxasuggest.php", { term: request.term, taid: document.tdform.taxauthid.value }, response );
				},
				autoFocus: true,
				minLength: 3 }
			);
		});

		function displayTaxomonyMeta(){
			$("#taxDetailDiv").hide();
			$("#taxMetaDiv").show();
		}
	</script>
</head>
<body class="claro">
	<?php
	$displayLeftMenu = (isset($taxa_admin_taxonomydisplayMenu)?$taxa_admin_taxonomydisplayMenu:false);
	include($SERVER_ROOT.'/includes/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php"><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="taxonomydynamicdisplay.php"><b><?php echo htmlspecialchars($LANG['TAX_EXPLORE'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></b></a>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<?php $taxMetaArr = $taxonDisplayObj->getTaxonomyMeta(); ?>
		<h1 class="page-heading"><?php echo $LANG['TAX_EXPLORE'] . ': ' . (array_key_exists('name', $taxMetaArr) ? $taxMetaArr['name'] : $LANG['CENTRAL_THESAURUS']); ?></h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:<?php echo (strpos($statusStr,'SUCCESS') !== false?'green':'red'); ?>;margin:15px;">
				<?= $taxonDisplayObj->cleanOutStr($statusStr); ?>
			</div>
			<hr/>
			<?php
		}
		if($isEditor){
			?>
			<div style="float:right;">
				<a href="taxonomyloader.php" target="_blank"><img class="icon-image" src="../../images/add.png" title="<?= $LANG['ADD_NEW_TAXON'] ?>" alt="<?= $LANG['PLUS_SIGN_DESC'] ?>"></a>
			</div>
			<?php
		}
		?>
		<div>
			<?php
			
			if(count($taxMetaArr) > 1){
				//echo '<div id="taxDetailDiv" class="tax-detail-div"><a href="#" onclick="displayTaxomonyMeta()">(more details)</a></div>';
				echo '<div id="taxMetaDiv" class="tax-meta-div">';
				if(isset($taxMetaArr['description'])) echo '<div style="margin:3px 0px"><b>' . $LANG['DESCRIPTION'] . ':</b> ' . $taxMetaArr['description'] . '</div>';
				if(isset($taxMetaArr['editors'])) echo '<div style="margin:3px 0px"><b>' . $LANG['EDITORS'] . ':</b> ' . $taxMetaArr['editors'] . '</div>';
				if(isset($taxMetaArr['contact'])) echo '<div style="margin:3px 0px"><b>' . $LANG['CONTACT'] . ':</b> ' . $taxMetaArr['contact'] . '</div>';
				if(isset($taxMetaArr['email'])) echo '<div style="margin:3px 0px"><b>' . $LANG['EMAIL'] . ':</b> ' . $taxMetaArr['email'] . '</div>';
				if(isset($taxMetaArr['url'])) echo '<div style="margin:3px 0px"><b>URL:</b> <a href="' . $taxMetaArr['url'] . '" target="_blank">' . $taxMetaArr['url'] . '</a></div>';
				if(isset($taxMetaArr['notes'])) echo '<div style="margin:3px 0px"><b>' . $LANG['NOTES'] . ':</b> ' . $taxMetaArr['notes'] . '</div>';
				echo '</div>';
			}
			?>
		</div>
		<div style="clear:both;">
			<form id="tdform" name="tdform" action="taxonomydynamicdisplay.php" method='POST'>
				<fieldset class="fieldset-size">
					<legend><b><?php echo $LANG['TAX_SEARCH']; ?></b></legend>
                    <div>
						<label for="taxontarget"> <?= $LANG['TAXON'] ?>: </label>
						<input id="taxontarget" name="target" type="text" class="search-bar" value="<?= $taxonDisplayObj->getTargetStr() ?>" />
					</div>
					<div style="margin:15px 15px 0px 60px;">
						<div>
							<input id="displayauthor" name="displayauthor" type="checkbox" value="1" <?php echo ($displayAuthor ? 'checked' : ''); ?> />
							<label for="displayauthor"> <?= $LANG['DISP_AUTHORS'] ?> </label>
						</div>
						<div>
							<input id="limittooccurrences" name="limittooccurrences" type="checkbox" value="1" <?= ($limitToOccurrences ? 'checked' : ''); ?> />
							<label for="limittooccurrences"> <?= $LANG['LIMIT_TO_OCCURRENCES'] ?> </label>
						</div>
						<?php
						if($isEditor){
							?>
							<div>
								<input name="emode" id="emode" type="checkbox" value="1	" <?= ($editorMode ? 'checked' : '')?> />
								<label for="emode"><?= $LANG['EDITOR_MODE'] ?></label>
							</div>
							<?php
						}
						?>
					</div>
					<div class="flex-form" style="margin: 10px">
						<div style="float: right">
							<button name="tdsubmit" type="submit" value="exportTaxonTree"><?= $LANG['EXPORT_TREE'] ?></button>
						</div>
						<div>
							<button name="tdsubmit" type="submit" value="displayTaxonTree"><?= $LANG['DISP_TAX_TREE'] ?></button>
							<input name="taxauthid" type="hidden" value="<?= $taxAuthId; ?>" />
						</div>
					</div>
				</fieldset>
			</form>
		</div>
		<div id="tree"></div>
		<script type="text/javascript">
			require([
				"dojo/window",
				"dojo/_base/declare",
				"dojo/dom",
				"dojo/on",
				"dijit/Tree",
				"dijit/tree/ObjectStoreModel",
				"dijit/tree/dndSource",
				"dojo/store/JsonRest",
				"dojo/domReady!"
			], function(win, declare, dom, on, Tree, ObjectStoreModel, dndSource, JsonRest){
				// set up the store to get the tree data
				var taxonTreeStore = new JsonRest({
					target: "rpc/getdynamicchildren.php",
					labelAttribute: "label",
					getChildren: function(object){
						return this.query({id:object.id, authors:<?= $displayAuthor ?>, limittooccurrences:<?= $limitToOccurrences ?>, targetid:<?= $targetId ?>, emode:<?= $editorMode ?>}).then(function(fullObject){
							return fullObject.children;
						});
					},
					mayHaveChildren: function(object){
						return "children" in object;
					}
				});

				/*aspect.around(taxonTreeStore, "put", function(originalPut){
					return function(obj, options){
						if(options && options.parent){
							obj.parent = options.parent.id;
						}
						return originalPut.call(taxonTreeStore, obj, options);
					}
				});

				taxonTreeStore = new Observable(taxonTreeStore);*/

				// set up the model, assigning taxonTreeStore, and assigning method to identify leaf nodes of tree
				var taxonTreeModel = new ObjectStoreModel({
					store: taxonTreeStore,
					deferItemLoadingUntilExpand: true,
					getRoot: function(onItem){
						this.store.query({id:"root",authors:<?php echo $displayAuthor; ?>,targetid:<?php echo $targetId; ?>}).then(onItem);
					},
					mayHaveChildren: function(object){
						return "children" in object;
					}
				});

				var TaxonTreeNode = declare(Tree._TreeNode, {
					_setLabelAttr: {node: "labelNode", type: "innerHTML"}
				});

				// set up the tree, assigning taxonTreeModel;
				var taxonTree = new Tree({
					model: taxonTreeModel,
					showRoot: false,
					label: "Taxa Tree",
					//dndController: dndSource,
					persist: false,
					_createTreeNode: function(args){
					   return new TaxonTreeNode(args);
					},
					onClick: function(item){
						// Get the URL from the item, and navigate to it
						//location.href = item.url;
						window.open(item.url,'_blank');
					}
				}, "tree");

				taxonTree.set("path", <?php echo json_encode($treePath); ?>).then(
					function(path){
						if(taxonTree.selectedNode){
							taxonTree._expandNode(taxonTree.selectedNode);
							document.getElementById(taxonTree.selectedNode.id).scrollIntoView();
							//win.scrollIntoView(taxonTree.selectedNode.id);
						}
					}
				);
				taxonTree.startup();

				/*taxonTree.onLoadDeferred.then(function(){
					var parentnode = taxonTree.getNodesByItem("<?php echo $targetId; ?>");
					var lastnodes = parentnode[0].getChildren();
					for (i in lastnodes) {
						if(lastnodes[i].isExpanded){
							 taxonTree._collapseNode(lastnodes[i]);
						}
						lastnodes[i].makeExpandable();
					}
				});*/
			});

		</script>
	</div>
	<?php
	include($SERVER_ROOT.'/includes/footer.php');
	?>
</body>
</html>