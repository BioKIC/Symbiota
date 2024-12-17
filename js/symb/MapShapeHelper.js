//import { parseWkt as parseWkt } from "./wktpolygontools.js";

/**
 * A number, or a string containing a number.
 * @typedef {Number[]} LatLng 
 */

/**
 * A number, or a string containing a number.
 * @typedef {Object} Polygon 
 * @property {string} Polygon.type
 * @property {LatLng[]} Polygon.latlngs 
 * @property {string} Polygon.wkt 
 */

/**
 * A number, or a string containing a number.
 * @typedef {Object} Rectangle 
 * @property {string} Rectangle.type
 * @property {Number} Rectangle.upperLat 
 * @property {Number} Rectangle.lowerLat 
 * @property {Number} Rectangle.rightLng 
 * @property {Number} Rectangle.leftLng
 */

/**
 * A number, or a string containing a number.
 * @typedef {Object} Circle 
 * @property {string} Circle.type
 * @property {LatLng} Circle.center 
 * @property {float} Circle.radius 
 */

function loadMapShape(shapeType, loaders) {
	//Loaders must return shape values besides type for aboved defintions
	const { rectangleLoader, polygonLoader, circleLoader } = loaders;

	const MILEStoKM = 1.60934;
	const KMtoM = 1000;

	function isNumeric(n) {
		return !isNaN(parseFloat(n)) && isFinite(n);
	}

	switch (shapeType) {
		case "polygon":
			//text type current supported as wkt or geojson
			const polygon = polygonLoader();
			try {
				if (polygon.geoJSON) {
					return {
						type: "geoJSON",
						geoJSON: JSON.parse(polygon.geoJSON)
					};
				} else if (polygon.wkt) {
					let polyPoints = parseWkt(polygon.wkt);

					if (polyPoints) {
						return { type: "polygon", latlngs: polyPoints, wkt: polygon.wkt };
					}
				}
			} catch (e) {
				console.log(e)
				alert("There was an error loading the map polygon");
			}
			break;
		case "rectangle":
			const { upperLat, lowerLat, leftLng, rightLng } = rectangleLoader();

			if (isNumeric(upperLat) && isNumeric(lowerLat) && isNumeric(leftLng) && isNumeric(rightLng)) {
				return {
					type: "rectangle",
					upperLat: upperLat,
					rightLng: rightLng,
					lowerLat: lowerLat,
					leftLng: leftLng,
				}
			}
			break;
		case "circle":
			let { radius, pointLat, pointLng, radUnits } = circleLoader();

			//Can only be km or miles
			if (!radUnits) radUnits = "km";

			if (isNumeric(radius) && isNumeric(pointLng) && isNumeric(pointLng)) {
				return {
					type: "circle",
					radius: (radUnits === "mi" ? radius * MILEStoKM : parseFloat(radius)) * KMtoM,
					latlng: [
						pointLat,
						pointLng
					]
				}
			}
			break;
		default:
			alert(`No Settings for Map Mode: ${shapeType}`)
			return false;
	}
}
