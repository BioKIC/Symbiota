<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>Label Content Format Visual Editor</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
		<link href="<?php echo htmlspecialchars($CSS_BASE_PATH, ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>/symbiota/collections/reports/labelhelpers.css" type="text/css" rel="stylesheet" >
		<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
		<style>
			:root{--main-max-size:940px}html{color:#212529}body{min-width:760px!important;margin:0auto!important;font-size:14px;line-height:24px}main{max-width:var(--main-max-size);margin:0 auto;display:grid;grid-template-columns:1fr 2fr 1fr;grid-gap:1em;align-items:start}@media only screen and (max-width:var(--main-max-size)){main{grid-template-columns:100%}}main li{list-style-type:none;display:inline-block}#fields-filter{margin-bottom:.25em;width:100%}select.control{display:block;margin:.25em 0;width:100%}input{width:100%}h1{font-weight:400;max-width:var(--main-max-size);margin:0 auto}h1.title{padding-top:.5em}h1.subtitle{padding-bottom:.5em}h2,h3,h4{text-transform:uppercase;font-weight:500;letter-spacing:1px}h4,h5{margin:.5em 0}#field-options>div{display:inline-block;margin:1em 0}#fields-list{overflow-y:scroll;height:65vh}#fields-list .material-icons{display:none}#build-label{background-color:#acacac;grid-column:1/2;padding:.5em;margin-top:1em;max-height:40vh;overflow:scroll}#build-label li .material-icons{font-size:12px;padding-left:6px}#build-label>li span.material-icons:hover{background-color:none!important}#build-label .delimiter{height:1em}#label-middle{border:1px solid #fff}#label-middle>.field-block{border:1px dashed #fff;min-height:2em;line-height:10px;text-align:left!important;margin:0!important}#label-middle .draggable{font-family:inherit!important;font-size:normal!important;font-weight:400!important;font-style:normal!important;text-transform:none!important;float:none!important}.field-block.container.selected{background-color:#004b22}#preview-label{border:1px solid gray;min-height:100px;padding:.5em}#preview-label .field-block{line-height:1.8rem;overflow:auto}#preview-label>.field-block>div{display:inline}#field-block-options{margin-top:2em}.unclickable{pointer-events:none!important}#instructions{width:100%;background-color:rgba(255,255,255,.637);position:absolute;top:0}#instructions li{color:initial;line-height:normal}.instructions-content{max-width:var(--main-max-size);background-color:#f1f6f9;margin:2em auto;padding:2em;border:1px solid #212529}button,input,optgroup,select,textarea{font-family:inherit;font-size:100%;line-height:1.15;margin:0}li{color:#fff;font-weight:600;font-size:.8rem!important;border-radius:2px;font:inherit;line-height:1;margin:.5em;padding:.25em .5em}button.btn{text-transform:uppercase}button.btn:hover{background-color:#a8a7a7}button.btn:focus{outline:0}button.control:disabled{background:#d3d3d3;cursor:not-allowed}button.control:disabled,button.control:disabled:hover,button.control[disabled]{background:#d3d3d3;cursor:not-allowed}button.control{color:#fff;background:#2f4f4f;border:none;border-right:1px solid #778899;font:inherit;line-height:1;padding:.5em;outline:0;height:40px;cursor:pointer}button.control:hover{background:#3d6666;border:none;line-height:1;border-right:1px solid #778899;outline:0}button .material-icons{width:24px}button>.material-icons{pointer-events:none}.field-block>span.material-icons.disabled{background:#d3d3d3;color:gray;cursor:not-allowed}.field-block>span.material-icons.disabled:hover{background:#d3d3d3}span.material-icons{cursor:pointer}span.material-icons:hover{background-color:#949494}.draggable.selected{background-color:#004b22;border:1px solid #fff}.drag-icon{background-color:#fff;cursor:move}[data-category=specimen]{background:#0da827;border:2px solid #0da827}[data-category=collection]{background:#0da827;border:2px solid #0da827}[data-category=taxon]{background:#077eb6;border:2px solid #077eb6}[data-category=determination]{background:#1c4eda;border:2px solid #1c4eda}[data-category=event]{background:#ee7bc8;border:2px solid #ee7bc8}[data-category=locality]{background:#952ed1;border:2px solid#952ed1}button.control.selected,button.control.selected:hover{background-color:#004b22}.draggable.dragging{opacity:1}
		</style>
	</head>
	<body>
    <div class="top-breathing-room-rel bottom-breathing-room-rel" style="text-align: center;">
      <h1 class="page-heading">Label Format Visual Editor</h1>
    </div>
    <main>
      <div>
        <div id="fields">
          <h4>Fields Available</h4>
          <p style="font-size:0.7em;line-height:1.4em">For field definitions please refer to the <a href="https://dwc.tdwg.org/terms/">Darwin Core Quick Reference Guide</a>.</p>
          <label for="fields-filter">Filter fields by category:</label>
          <select name="fields-filter" id="fields-filter">
            <option value="all">All</option>
            <option value="specimen">Specimen</option>
            <!-- <option value="collection">Collection</option> -->
            <option value="taxon">Taxon</option>
            <option value="determination">Determination</option>
            <option value="event">Event</option>
            <option value="locality">Locality</option>
          </select>
          <div id="fields-list" class="container"></div>
        </div>
      </div>
      <div>
        <div id="build">
          <div id="build-label">
            <h4 style="color: #212529">Label Content Area</h4>
            <p style="font-size:0.7em;line-height:1.4em">drag, drop & reorder fields here; click to select fields or lines to apply formats (only one item formattable at a time); reorder lines clicking on arrows; remove lines/fields clicking on "x"</p>
            <div id="label-middle">
              <div class="field-block container" data-delimiter=" ">
                <span class="material-icons">close</span><span class="material-icons">keyboard_arrow_up</span><span class="material-icons">keyboard_arrow_down</span>
              </div>
            </div>
            <button class="btn top-breathing-room-rel" onClick="addLine()">Add line</button>
          </div>
        </div>
        <div id="preview">
          <h4>Label preview</h4>
          <p style="font-size:0.7em;line-height:1.4em">content automatically displayed below</p>
          <div id="preview-label"></div>
          <!-- <button class="btn" onclick="printJson()">Display JSON</button>
          <button class="btn" onclick="loadJson()">Load JSON</button> -->
          <!-- <button class="btn" id='copyBtn' onclick="copyJson()" style="display: none;">Copy JSON to clipboard</button> -->
          <button class="btn top-breathing-room-rel" onclick="saveJson()">Save format & Close</button>
          <button class="btn top-breathing-room-rel" onclick="cancelWindow()">Cancel & Close</button>
          <textarea id="dummy" style="display: none; height: 300px; width: 100%;" data-format-id=""></textarea>
        </div>
      </div>
      <div>
        <div id="controls">
          <div id="field-options">
            <h4>Field Options</h4>
            <div>
              <div>
                <label for="prefix">Prefix:</label>
                <input type="text" name="prefix" id="prefix" class="control" disabled="true" data-group="field">
              </div>
              <div>
                <label for="suffix">Suffix:</label>
                <input type="text" name="suffix" id="suffix" class="control" disabled="true" data-group="field">
              </div>
            </div>
          </div>
          <div id="field-block-options">
            <h4>Line Options</h4>
            <div>
              <label for="delimiter">Fields Delimiter:</label>
              <input type="text" name="delimiter" id="delimiter" class="control bottom-breathing-room-rel" disabled="true" data-group="field-block">
            </div>
          </div>
        </div>
      </div>
    </main>
  </body>
  <script src="../../js/symb/collections.labeljsongui.js"></script>
</html>
