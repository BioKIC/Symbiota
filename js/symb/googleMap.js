class GoogleMap {

   DEFAULT_MAP_OPTIONS = {
      center: [43.64701, -79.39425],
      mapTypeId: google.maps.MapTypeId.TERRAIN,
      scaleControl: true
   }

   DEFAULT_DRAW_OPTIONS = {
      strokeWeight: 0,
      fillOpacity: 0.45,
      editable: true,
      draggable: true,
      drawControl: true,
   }

   /* To Hold Reference to Google Map */
   mapLayer;

   /* Active_Shape Type
    * type: String represenation of shape 
    * layer: leaflet layer
    * center: { lat: float, lng float }
    */
   activeShape;

   /* Reference Leaflet Feature Group for all drawn items*/
   _drawManager;
   onDrawChange;

   constructor(map_id, map_options=DEFAULT_MAP_OPTIONS) {
      this.mapLayer = new google.maps.Map(document.getElementById(map_id), map_options);
   }

   DrawOptions;

   enableDrawing(drawOptions = DEFAULT_DRAW_OPTIONS, onDrawChange) {
      this.onDrawChange = onDrawChange;

      drawOptions = {
         ...drawOptions,
         ...this.DEFAULT_DRAW_OPTIONS,
      }
 
      this.DrawOptions = drawOptions;

      function getMapMode(mode) {
         switch (mode) {
            case "polygon":
               return google.maps.drawing.OverlayType.POLYGON;
            case "rectangle":
               return google.maps.drawing.OverlayType.RECTANGLE;
            case "circle":
               return google.maps.drawing.OverlayType.CIRCLE;
            default:
               return mode;
         }
      }

      if(drawOptions.drawControl) {
         let drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: getMapMode(drawOptions.mapMode),
            drawingControl: true,
            drawingControlOptions: {
               position: google.maps.ControlPosition.TOP_CENTER,
               drawingModes: [
                  google.maps.drawing.OverlayType.POLYGON,
                  google.maps.drawing.OverlayType.RECTANGLE,
                  google.maps.drawing.OverlayType.CIRCLE
               ]
            },
            markerOptions: {
               draggable: true
            },
            polygonOptions: drawOptions,
            circleOptions: drawOptions,
            rectangleOptions: drawOptions,
         });

         drawingManager.setMap(this.mapLayer);

         google.maps.event.addListener(drawingManager, 'click', function(e) {
            alert("control clicked on");
            alert(drawingManager.getDrawingMode());
         });

         drawingManager.addListener('click', function(e) {
            alert("control clicked on 2");
            alert(drawingManager.getDrawingMode());
         });

         google.maps.event.addListener(drawingManager, 'at_insert', function(e) {
            alert("at insert");
            alert(drawingManager.getDrawingMode());
         });

         drawingManager.addListener('at_insert', function(e) {
            alert("at insert2");
            alert(drawingManager.getDrawingMode());
         });

         google.maps.event.addListener(drawingManager, 'overlaycomplete', function(e) {
            if (e.type != google.maps.drawing.OverlayType.MARKER) {
               // Switch back to non-drawing mode after drawing a shape.
               drawingManager.setDrawingMode(null);

               var shapeType = e.type;
               // Add an event listener that selects the newly-drawn shape when the user
               // mouses down on it.
               var newShape = e.overlay;
               newShape.type = e.type;

               //Clear if Shape Isn't the same
               if (this.activeShape && this.activeShape.layer != newShape) 
                  this.activeShape.layer.setMap(null);

               let listeners = ['click', 'dragend']

               if(shapeType === "circle") {
                  listeners.push('radius_changed');
                  listeners.push('center_changed');
               } else if (shapeType === "rectangle") {
                  listeners.push('bounds_changed');
               } else if(shapeType === "polygon") {
                  listeners.push('insert_at');
                  listeners.push('remove_at');
                  listeners.push('set_at');
               }

               listeners.forEach(eventListener => {
                  google.maps.event.addListener(newShape, eventListener, function () {
                     this.activeShape = setSelection(newShape);
                     onDrawChange(this.activeShape);
                  }.bind(this))
               })

               this.activeShape = setSelection(newShape);
               onDrawChange(this.activeShape);
            }
         }.bind(this));

         this._drawingManager = drawingManager;
      }
   }

   drawShape(shape) {
      let listeners = ['click', 'dragend']
      let bounds = false;

		this._drawingManager.setDrawingMode(null);
      switch(shape.type) {
         case "polygon":
				bounds = new google.maps.LatLngBounds();
            const polygon = new google.maps.Polygon({
               paths: shape.latlngs.map(pt=> {
                  const coord = new google.maps.LatLng(pt[0],pt[1]);
                  bounds.extend(coord);
                  return coord; 
               }),
               ...this.DrawOptions
            });

            shape.layer = polygon;
            shape.layer.type = 'polygon';

            this.activeShape = shape;
            polygon.setMap(this.mapLayer);
            break;
         case "rectangle":
            listeners.push('bounds_changed');

            const rectangle = new google.maps.Rectangle({
               bounds: {
                  north: shape.upperLat,
                  south: shape.lowerLat,
                  east: shape.rightLng,
                  west: shape.leftLng,
               },
               ...this.DrawOptions
            });

            shape.layer = rectangle;
            shape.layer.type = 'rectangle';

            this.activeShape = shape;
            rectangle.setMap(this.mapLayer);

            bounds = rectangle.getBounds();
            break;
         case "circle":

            listeners.push('radius_changed');
            listeners.push('center_changed');

            const circle = new google.maps.Circle({
               center: {lat: shape.latlng[0], lng: shape.latlng[1]},
               radius: shape.radius,
               ...this.DrawOptions
            });

            shape.layer = circle;
            shape.layer.type = 'circle';

            this.activeShape = shape;
            circle.setMap(this.mapLayer);
				bounds = circle.getBounds();
            break;
         default:
            throw Error(`Can't draw ${shape.type}`)
      } 

      listeners.forEach(eventListener => {
         google.maps.event.addListener(shape.layer, eventListener, function () {
            this.activeShape = setSelection(shape.layer);
            if(this.onDrawChange) this.onDrawChange(this.activeShape);
         }.bind(this))
      })

      if(bounds) {
         this.mapLayer.fitBounds(bounds);
         this.mapLayer.panToBounds(bounds);
      }
   }
}

function setSelection(overlay) {
   let shape = {
      type: overlay.type,
      layer: overlay
   }

   const SIG_FIGS = 6;

   switch(overlay.type) {
      case "polygon":
         //LatLngs
         console.log(overlay)
         shape.latlngs = overlay.getPath().getArray().map(
            pt=> [pt.lat() , pt.lng()]);
         shape.wkt = "POLYGON ((" + shape.latlngs.map(pt => {
            return `${pt[0].toFixed(SIG_FIGS)} ${pt[1].toFixed(SIG_FIGS)}`;
         }).join(",") + "))";
         shape.center = shape.latlngs[0];
         
         break;
      case "rectangle":

         const northEast = overlay.getBounds().getNorthEast();
         shape.upperLat =  northEast.lat();
         shape.rightLng =  northEast.lng();

         const southWest = overlay.getBounds().getSouthWest();
         shape.lowerLat = southWest.lat();
         shape.leftLng = southWest.lng();
         break;
      case "circle":
         shape.radius = overlay.getRadius();

         shape.center = {
            lat: overlay.getCenter().lat(),
            lng: overlay.getCenter().lng()
         };
         break; 
      default:
         console.log(overlay)
   }

   return shape;
}
