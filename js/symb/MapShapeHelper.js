//Need some js files into ESModules to get depedencies build into js
//For now just make sure it is globablly imported via script tag
//import { parseWkt as parseWkt } from "./wktpolygontools.js";

/* Shape Defintions:
 *
 * Polygon {
 *    type: polygon, 
 *    latlngs: [[lat, lng]...],
 *    wkt: String (Wkt format),
 * }
 *
 * Rectangle {
 *    type: "rectangle",
 *    upperLat: lat,
 *    lowerLat: lat,
 *    rightLng: lng,
 *    leftLng: lng,
 * }
 *
 * Circle { 
 *    type: "circle"
 *    radius: float,
 *    center [lat, lng]
 * }
 */
//export default 
function loadMapShape(shapeType, loaders) {
   //Loaders must return shape values besides type for aboved defintions
   const {rectangleLoader, polygonLoader, circleLoader } = loaders;

   const MILEStoKM = 1.60934;
   const KMtoM = 1000; 

   function isNumeric(n) {
      return !isNaN(parseFloat(n)) && isFinite(n);
   }

   switch(shapeType) {
      case "polygon":
         let wkt = polygonLoader();

         try {
            let polyPoints = parseWkt(wkt);
            if(polyPoints) {
               return { type: "polygon", latlngs: polyPoints, wkt: wkt};
            }
         } catch(e) {
            console.log(e)
            alert("There was an error loading the map polygon");
         }
         break;
      case "rectangle":
         const {upperLat, lowerLat, leftLng, rightLng } = rectangleLoader();

         if(isNumeric(upperLat) && isNumeric(lowerLat) && isNumeric(leftLng) && isNumeric(rightLng)) {
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
         let {radius, pointLat, pointLng, radUnits} = circleLoader();

         //Can only be km or miles
         if(!radUnits) radUnits = "km";

         if(isNumeric(radius) && isNumeric(pointLng) && isNumeric(pointLng)) {
            return {
               type: "circle",
               radius: (radUnits === "mi"? radius * MILEStoKM: parseFloat(radius)) * KMtoM,
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
