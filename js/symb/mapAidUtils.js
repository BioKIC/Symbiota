/**
 * @typedef {String} MapMode
 */

/**
 * Enum to Different Map Modes for Mapping utilites.
 * @readonly
 * @enum {MapMode}
 */
const MAP_MODES = Object.freeze({
	POLYGON: 'polygon',
	RECTANGLE: 'rectangle',
	CIRCLE: 'circle',
});

/**
 * @typedef {String} PolygonTextType
 */

/**
 * Enum for supported polygon text types.
 * @readonly
 * @enum {PolygonTextType}
 */
const POLYGON_TEXT_TYPES = Object.freeze({
	WKT: 'wkt',
	GEOJSON: 'geoJson',
});


/** Function
 * Opens Coordinate map helper
 * @param {Object} map_options - Options that are passed to the map coordinate aid window 
 * @param {String} options.title = 'Map Coodinate Helper' - Enum to decide what mode map coordinate aid starts up in
 * @param {MapMode} [options.map_mode] - Enum to decide what mode map coordinate aid starts up in
 * @param {Boolean} [options.map_mode_strict = false] - Flag to decide if map coordinate aid can change modes after opened 
 * @param {String} [options.polygon_input_id = 'footprintwkt'] - Polygon only option for html id input element for loading and saving out wkt/geojson data
 * @param {String} [options.polygon_text_type = POLYGON_TEXT_TYPES.WKT] - Option for what data type should be exported out
 * @param {String} [options.client_root] - Pass in for Symbini config for  
 */
function openCoordAid(options) {
	const default_options = {
		polygon_output_id: "footprintwkt",
		title: "Map Coodinate Helper",
	}
	const exclude_params = ["client_root", "title"]

	options = {
		...default_options,
		...options,
	}

	const paramStr = Object.entries(options)
		.reduce((filtered, [k, v]) => {
			if (!exclude_params.includes(k)) {
				filtered.push(`${k}=${v}`);
			}
			return filtered;
		}, [])
		.join('&');

	mapWindow = open(
		`${options.client_root ? options.client_root : ''}/collections/tools/mapcoordaid.php?${paramStr}`,
		options.title,
		"resizable=0,width=900,height=630,left=20,top=20",
	);
	if (mapWindow.opener == null) mapWindow.opener = self;
	mapWindow.focus();
}
