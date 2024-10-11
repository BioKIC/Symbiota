<?php 
$action = $_POST['action'] ?? false;

function get_lang_banner($lang_code) {
	$lang_str = match($lang_code) {
		'es' => 'Español (Spanish)',
		'fr' => 'Français (French)',
		'pt' => 'Português (Portuguese)',
		Default => 'English',
	};

	$seperator = "------------------\n";
	$language = "Language: $lang_str\n";
	$language = "Language: $lang_str\n";

	//Currently This tool is assuming some of it was google translated
	$google_translate = "Translated by: Google Translate ". date('(Y-m-d)') . "\n";
	
	return "/*\n" . 
		$seperator . 
		$language .
		$google_translate .
		$seperator . 
		"*/\n\n";

}

if($action === 'regenerate_lang_file') {
	$langs = [];
	foreach ($_POST as $key => $value) {
		if($key === 'action' || $key === 'filepath') continue;
		[$key, $lang_code] = explode('|', trim($key));
		if(!isset($langs[$lang_code])) {
			$langs[$lang_code] = [['key' => $key, 'value' => $value]];
		} else {
			array_push($langs[$lang_code], ['key' => $key, 'value' => $value]);
		}
	}

	$path = 'content/lang/' . trim($_POST['filepath']);
	foreach ($langs as $lang_code => $lang_map) {
		$new_lang_file = fopen($path . '.' . $lang_code . '.php', "w");
		fwrite($new_lang_file, "<?php\n");
		fwrite($new_lang_file, get_lang_banner($lang_code));
		foreach ($lang_map as $entry) {
			$key_str = '$LANG[\'' . $entry['key'] . '\'] = \'' . str_replace("'", "\'", $entry['value']) . "';\n";
			fwrite($new_lang_file, $key_str);
		}
		fwrite($new_lang_file, "\n?>");
		fclose($new_lang_file);
	}
}

$LANG = [];
$filepath = $_REQUEST['filepath'] ?? null;

function get_lang_map($path) {
	if(!$path) return [];
	$langs = ['en' => null, 'es' => null, 'fr' => null, 'pt' => null];
	$langs_key_map = [];

	foreach (array_keys($langs) as $lang_code) {
		$full_path = 'content/lang/' . $path. '.' . $lang_code . '.php';
		if(file_exists($full_path)) {
			$SERVER_ROOT = '/var/www/html';
			include($full_path);
		} else {
			continue;
		}

		foreach ($LANG as $lang_key => $lang_value) {

			if(!isset($langs_key_map[$lang_key])) {
				$langs_key_map[$lang_key] = $langs;
			}
			$langs_key_map[$lang_key][$lang_code] = $lang_value;
		}
	}

	return $langs_key_map;
}

/*
 * Goals
 * - Create easy system to get google translations into lang files if they change
 *
 * Ideal Workflow
 * - select which values you want to get google translations for
 * - downloadCsv and upload to google drive for google translate
 * - download translated sheet and upload it to create new lang files
 * - Show visual diff with chance to edit
 * - Accept changes when ready to generate new lang files
 */

function echo_lang($langs_key_map) {
	echo '<table id="lang-table">';

	echo '<thead>';
	echo '<th>Lang Key</th><th id="use_gt">gt</th> <th id="h_en">en</th><th id="h_es">es</th><th id="h_fr">fr</th><th id="h_pt">pt</th>';
	echo '</thead>';

	echo '<tbody>';
	$cnt = 1;
	foreach ($langs_key_map as $lang_key => $lang_arr) {
		echo '<tr class="lang_row" ' . (!($cnt % 2 === 0)? 'style="background-color: #F5F5F5"' : '') . '>';
			echo '<td style="width:fit-content">' . $lang_key. '</td>';
			echo '<td style="width:4rem"><input id="gt_'.$lang_key.'"type="checkbox"/></td>';
			foreach ($lang_arr as $lang_code => $lang_value) {
				echo '<td>';
				echo '<div>';
				$id = $lang_key . '|'. $lang_code;
				echo "<input id=\"$id\" name=\"$id\" value=\"$lang_value\">";
				echo '</div>';
				echo '</td>';
			}
		echo '</tr>';
		$cnt++;
	}
	echo '</tbody>';
	echo '</table>';

}
$lang_map = get_lang_map($filepath);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Language File Tool</title>
		<style>
		.lang_row {
			padding: 1rem;
		}
		th {
			background: white;
			position: sticky;
			border: solid black 1px;
			top: 0;
		}

		tbody input {
			width: 95%
		}

		td div{
			display:flex;
			flex-direction:column;
		}

		table {
			width: 100%
		}
		</style>
		<script type="text/javascript">
		function toggleColumn(el) {
			let headers = document.querySelectorAll(`#lang-table thead tr th`);

			if(!headers) return;

			let col_num = null;
			let header = null;
			let idx = 1;
			for(let th of headers) {
				if(th.id === `h_${el.id}`) {
					col_num = idx;
					header = th;
				}
				idx++;
			}

			if(!col_num || !header) return;

			const tds = document.querySelectorAll(`tbody td:nth-child(${col_num})`)

			if(header) {
				if(header.style.display === "none") {
					header.style.display = "table-cell";
				} else {
					header.style.display = "none";
				}
			}

			for(let td of tds) {
				if(td.style.display === "none") {
					td.style.display = "table-cell";
				} else {
					td.style.display = "none";
				}
			}
		}	

		function downloadCsv() {
			const data = document.getElementById('lang-data')
			let json = null
			try {
				json = JSON.parse(data.getAttribute('data-lang'));
			} catch(e) {
				alert('Failed Parse data for download')
				return;
			}

			let langs = ['en', 'es', 'fr', 'pt']
			let lang_keys = Object.keys(json);
			let rows = [
				['Lang Key', ...langs].join(',')
			];

			for(let i=0; i < lang_keys.length; i++) {
				const key = lang_keys[i];
				let row = [key];
				const gt = document.getElementById(`gt_${key}`);
				for(let lang_code of langs) {
					const el = document.getElementById(`${key}|${lang_code}`);
					if(lang_code !== 'en' && gt && gt.checked) {
						//Offset of 2 because google sheets start at 1 + headers line is 2
						row.push(`=GOOGLETRANSLATE(B${i + 2},""en"",""${lang_code}"")`);
					} else if(el && el.value) {
						row.push(el.value);
					} else {
						row.push('');
					}
				}
				rows.push('"' + row.join('","') + '"');
				//rows.push(row.join(','));
			}
	
			let csv = rows.join('\n')
			const blob = new Blob([csv], { type: 'text/csv' });
			//var encodedUri = encodeURI("data:text/csv;charset=utf-8," + csv);
			const url = URL.createObjectURL(blob);
			var link = document.createElement("a");
			link.setAttribute("href", url);
			link.setAttribute("download", '<?= str_replace("/", "_", $filepath)?>.csv');
			document.body.appendChild(link); // Required for FF
			link.click();
		}	

		function loadFileData(data) {
			const rows = data.split('\n');
			const csv = []

			const delimiter = ',';

			for(let i = 0; i < rows.length; i++) {
				const row_str = rows[i] //.replaceAll('""', '\\"');
				let quoted = false;
				let escaped = false;
				let splits = [];
				let cell = "";
				let dble_cnt = 0;

				for (let str_pos = 0; str_pos < row_str.length; str_pos++) {
					const prev = str_pos !== 0? row_str[str_pos - 1]: false;
					const c = row_str[str_pos];
					if(prev === delimiter && c === '"' && !quoted) {
						quoted = true;
					} else if(c === delimiter && (!quoted  || dble_cnt % 2 === 1)) {
						dble_cnt = 0;
						quoted = false;
						splits.push(cell);
						cell = "";
					} else {
						dble_cnt = (c === '"'? dble_cnt + 1: 0);
						if(c !== '"' || dble_cnt > 1) {
							cell += c;
						}

						if(str_pos + 1 >= row_str.length) {
							dble_cnt = 0;
							quoted = false;
							splits.push(cell);
							cell = "";
						}
					}
				}
				csv.push(splits);
			}

			const table = document.getElementById('lang-table')

			if(table) table.innerHTML = "";
			//else document.appendChild("div")
			const headers = csv[0];
//headers.reduce((a, v)=> a + `<th>${v}</th>`, "")
			let thead = "";
			for(let i = 0; i < headers.length; i++) {
				if(i === 1) {
					thead += `<th id="h_gt">gt</th>`;
				}
				thead += `<th id="h_${headers[i]}">${headers[i]}</th>`;
			}

			let tbody = ""
			for(let i = 1; i < csv.length; i++) {
				tbody += '<tr class="lang_row"' + (i % 2 !== 0?'style="background-color: #F5F5F5"': '') + '>';
				const row = csv[i];
				const lang_key = row[0];
				tbody += `<td>${lang_key}</td>`;
				tbody += `<td style="width:4rem"><input id="gt_${lang_key}"type="checkbox"/></td>`;

				for(let lang_pos = 1; lang_pos < row.length; lang_pos++) {
					const lang_code = headers[lang_pos];
					const id = `${lang_key}|${lang_code}`
					tbody += `<td><div><input id="${id}" name="${id}" value="${row[lang_pos]}"></div></td>`;
				}
				tbody += '</tr>';
			}

			table.innerHTML = `<thead>${thead}<thead><tbody>${tbody}</tbody>`;
		}

		function init() {
			const fileInput = document.getElementById('load-file')
			const readFile = () => {
				const reader = new FileReader()
				reader.onload = () => {
					loadFileData(reader.result);
				}
				// start reading the file. When it is done, calls the onload event defined above.
				reader.readAsText(fileInput.files[0])
			}

			fileInput.addEventListener('change', readFile)
		}
		</script>
	</head>
	<body onload="init()">
		<h1>Lang File Tool</h1>
		<div id="lang-data" data-lang="<?= htmlspecialchars(json_encode($lang_map))?>"/>
		<?php echo ($filepath && count($lang_map) <= 0? $filepath . ' Lang files for ' . $filepath . ' not found<br/><br/>':'')?>

		<fieldset>
			<legend>Get New Lang File Set</legend>
			<form>
				<label for="filepath">Filepath</label>
				<input id="filepath" name="filepath" value="<?= $filepath?>">
				<button type="submit">Sumbit</button>
			</form>
		</fieldset>

		<fieldset>
			<legend>Upload File Set</legend>
			<div>
				<input id="load-file" type="file" accept="text/csv" name="translation">
				<span id="out"></span>
				<button type="button">
					Load File Data
				</button>
			</div>
		</fieldset>

		<fieldset>
			<legend>Download</legend>
			<div>
				<button type="" onclick="downloadCsv()">Download as CSV</button>
			</div>
		</fieldset>

		<div>
			<input data-col_num="3" id="en" type="checkbox" name="en" checked onclick="toggleColumn(this)">
			<label for="en">English</label>

			<input data-col_num="4" id="es" type="checkbox" name="es" checked onclick="toggleColumn(this)">
			<label for="es">Spanish</label>

			<input data-col_num="5" id="fr" type="checkbox" name="fr" checked onclick="toggleColumn(this)">
			<label for="fr">French</label>

			<input data-col_num="6" id="pt" type="checkbox" name="pt" checked onclick="toggleColumn(this)">
			<label for="pt">Portuguese</label>
		</div>
		<?php if($lang_map && count($lang_map) > 0):?>	
			<div style="overflow: scroll-x;">
			<Form method="POST">
				<input type="hidden" name="action" value="regenerate_lang_file"/>
				<input type="hidden" name="filepath" value="<?= $filepath?>"/>
				<?php echo_lang($lang_map)?>
				<button type="submit">Regenerate Lang Files</button>
			</Form>
			</div>
		<?php endif?>
	</body>
</html>
