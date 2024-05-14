function geothesaurusSource(params) {
	const parent_selector = params.parent;
	return (request, response) => {
		function optionize(data) {
			response(data.map(v => ({ label: v.geoterm, value: v.geoterm })))
		}

		if (params.parent) {
			const el = document.getElementById(parent_selector)
			if (el) {
				params.parent = el.value
			}
		}

		const url = "../../geothesaurus/rpc/searchGeothesaurus.php";
		params.geoterm = request.term;
		$.getJSON(url, params, optionize)
	}
}

function initLocalitySuggest(opts = { country, state_province, county, municipality }) {
	if (!opts) return;
	//Numbers are arbirary and are a number form of ADM0-ADM3 or 50-80 system but with more depth
	const localeParams = {
		country: { geolevel: 50 },
		state_province: { geolevel: 60, parent: opts?.country?.id },
		county: { geolevel: 70, parent: opts?.state_province?.id },
		municipality: { geolevel: 80, parent: opts?.county?.id },
	}

	for (let locale in localeParams) {
		if (opts[locale]) {
			$(`#${opts[locale]['id']}`).autocomplete({
				source: geothesaurusSource(localeParams[locale]),
				minLength: 2,
				autoFocus: true,
				change: opts[locale]['change'],
			});
		}
	}
}
