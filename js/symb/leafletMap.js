function getObservationSvg(opts = {color: "#7A8BE7", size: 24, className:""}) {
   const default_ops = {color: "#7A8BE7", size: 24};
   opts = {...default_ops, ...opts};
   const half = opts.size/2;

   return L.divIcon({
      html: `
		<svg
		width="${opts.size}"
		height="${opts.size}"
		viewBox="-10 -10 ${opts.size + 20} ${opts.size + 20}"
		version="1.1"
		preserveAspectRatio="none"
		xmlns="http://www.w3.org/2000/svg"
		>
			<polygon 
				class="${opts.className}" 
				points="${half},0 0,${opts.size} ${opts.size},${opts.size}" 
				style="fill:${opts.color};stroke:black;stroke-width:3" 
			/>
		</svg>`,
      className: "",
      observation: true,
      iconSize: [opts.size, opts.size],
      iconAnchor: [half, half],
   });
}

async function getMacroStratData(lat, lng, zoom) {
	return fetch(`https://macrostrat.org/api/v2/mobile/map_query_v2?lng=${lng}&lat=${lat}&z=${zoom}`)
		.then(async response => {
		if(!response.ok) {
			return {};
		}
		const json_data = await response.json()

		if(!json_data.success) {
			return {};
		}
		return json_data.success.data;
	})
}

function getSpecimenSvg(opts = {color: "#7A8BE7", size: 24, className:""}) {
	const default_ops = {color: "#7A8BE7", size: 24};
	opts = {...default_ops, ...opts};
	const stroke_width = 2;
	const size_with_stroke = stroke_width * 2 + opts.size;
	return L.divIcon({
		html: `
			<svg 
			height="${size_with_stroke * 2}"
			width="${size_with_stroke * 2}"
			version="1.1"
			preserveAspectRatio="none"
			xmlns="http://www.w3.org/2000/svg"
			>
				<circle 
					r="${opts.size}" 
					cx="${size_with_stroke}" cy="${size_with_stroke}" 
					fill="${opts.color}" stroke="black" stroke-width="${stroke_width}"
				/>
			</svg>`,
		className: "",
		iconSize: [size_with_stroke, size_with_stroke],
		iconAnchor: [size_with_stroke, size_with_stroke],
	});
}

class LeafletMap {
   //DEFAULTS
   DEFAULT_MAP_OPTIONS = {
      center: [0, 0],
      zoom: 2,
      minZoom: 2,
   };

   DEFAULT_SHAPE_OPTIONS = {
      color: '#000',
      opacity: 0.85,
      fillOpacity: 0.55
   };

   DEFAULT_DRAW_OPTIONS = {
      polyline: false,
      control: true,
      circlemarker: false,
      marker: false,
      multiDraw: false,
      drawColor: this.DEFAULT_SHAPE_OPTIONS,
      lang: "en",
   };
   
   /* To Hold Reference to Leaflet Map */
   mapLayer;

   /* Active_Shape Type
    * type: String represenation of shape 
    * layer: leaflet layer
    * center: { lat: float, lng float }
    */
   activeShape;

   //List of drawn shapes
   shapes = [];

   /* Reference Leaflet Feature Group for all drawn items*/
   drawLayer;

   /* Save Markerclusterer taxa clusters for later manipulation */
   taxaClusters = [];

   defaultBounds = [];

   constructor(map_id, map_options={}) {

	  map_options = {
		 ...this.DEFAULT_MAP_OPTIONS,
		 ...map_options,
	  }

      this.mapLayer = L.map(map_id, map_options);

	  if(map_options.defaultBounds) {
	    this.defaultBounds = map_options.defaultBounds;
		this.resetBounds();
	  }

      const terrainLayer = L.tileLayer('https://{s}.google.com/vt?lyrs=p&x={x}&y={y}&z={z}', {
         attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
         subdomains:['mt0','mt1','mt2','mt3'],
         maxZoom: 20, 
         worldCopyJump: true,
         detectRetina:true,
      });

    const satelliteLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
         attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
         subdomains:['mt1','mt2','mt3'],
         maxZoom: 20, 
         noWrap:true,
         displayRetina:true,
         tileSize: 256,
      });

    const basicLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
         displayRetina:true,
         maxZoom: 20, 
         noWrap:true,
         tileSize: 256,
         attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    });

	var Stadia_OSMBright = L.tileLayer('https://tiles.stadiamaps.com/tiles/osm_bright/{z}/{x}/{y}{r}.{ext}', {
		minZoom: 0,
		maxZoom: 20,
		attribution: '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
		ext: 'png'
	}).addTo(this.mapLayer);

      var Esri_WorldImagery = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
         displayRetina:true,
         attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community'
      });

      var macro_strat = L.tileLayer('https://macrostrat.org/api/v2/maps/burwell/emphasized/{z}/{x}/{y}/tile.png', {
         displayRetina:true,
		 opacity: .50,
         attribution: 'Map data: &copy; <a href="https://macrostrat.org/#about">Macrostrat</a> (<a href="http://creativecommons.org/licenses/by/4.0/">CC-BY-4.0</a>)',
      });

      const openTopoLayer = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
         maxZoom: 17,
         displayRetina:true,
         attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
      });

		const macro_strat_info = async e => {
			const zoom = e.target._zoom;
			const lat = e.latlng.lat;
			const lon = e.latlng.lng;
			const macro_strat_data = await getMacroStratData(lat, lon, zoom);

			const loop_strat_names = (data) => {
				let html_str = "";
				for(let strat_name of data.macrostrat.strat_names) {
					html_str += `<a target="_blank" href="https://macrostrat.org/sift/#/strat_name/${strat_name.strat_name_id}">${strat_name.rank_name}</a> `
				}

				return html_str;
			}

			if(macro_strat_data.mapData && macro_strat_data.mapData.length) {
				let content = ""

				if(macro_strat_data.mapData[0].name) {
					content += `<div>
						<span style="font-weight:bold">Unit: </span>
						<span>${macro_strat_data.mapData[0].name}</span>
					</div>`;
				}

				if(macro_strat_data.mapData[0].age) {
					content+= `<div>
						<span style="font-weight:bold">Age: </span>
						<span>${macro_strat_data.mapData[0].age}</span>
					</div>`;

				}

				if(macro_strat_data.mapData[0].ref) {
					content += `<div style="font-size:0.8rem">
						<span style="font-weight:bold">Source:</span>
						${macro_strat_data.mapData[0].ref.authors}, ${macro_strat_data.mapData[0].ref.ref_title}: ${macro_strat_data.mapData[0].ref.ref_source}, ${macro_strat_data.mapData[0].ref.isbn_doi} ${macro_strat_data.mapData[0].ref.source_id} / ${macro_strat_data.mapData[0].map_id}
					</div>`;
				}

				if(macro_strat_data.mapData[0].macrostrat && macro_strat_data.mapData[0].macrostrat.strat_names && macro_strat_data.mapData[0].macrostrat.strat_names.length) {
					content += `<div style="margin-top:1rem">
						<span style="font-weight:bold">Macrostrat matched units: </span>
						${loop_strat_names(macro_strat_data.mapData[0])}
					</div>`;
				}

				L.popup()
					.setLatLng([lat, lon])
					.setContent(`
						<div style="font-size:1rem">
							${content}
						</div>`)
					.openOn(this.mapLayer);
			}
		}


		/* Alternative to using the api. Uses color inference. Back if we don't want to use macrostrat api*/
		const macro_strat_color = (e) => {
			const zoom = e.target._zoom;

			let coords = this.mapLayer.project(e.latlng, zoom).floor();

			let pX = coords.x / 256;
			let pY = coords.y / 256;

			coords.x = Math.floor(pX);
			coords.y = Math.floor(pY);
			coords.z = zoom

			const tile = new Image();
			tile.crossOrigin = "anonymous";
			tile.src = `https://macrostrat.org/api/v2/maps/burwell/emphasized/${coords.z}/${coords.x}/${coords.y}/tile.png`;

			const canvas = document.createElement("canvas");
			const ctx = canvas.getContext("2d");

			tile.addEventListener('load', function() {
				ctx.drawImage(tile, 0, 0);
				const dX = Math.floor((pX - coords.x) * 512);
				const dY = Math.floor((pY - coords.y) * 512);
				const pixel = ctx.getImageData(dX, dY, 1, 1);	

				ctx.fillRect(dX, dY, 10, 10);

				const data = pixel.data;
				console.log(`rgb(${data[0]} ${data[1]} ${data[2]} / ${data[3] / 255})`);
			});
		}

		macro_strat.on('add', (e) => {
			this.mapLayer.on('click', macro_strat_info)
		})

		macro_strat.on('remove', (e) => {
			this.mapLayer.off('click', macro_strat_info)
		})

      if(map_options.layer_control !== false) {
			const layers = {
				"Terrain": terrainLayer,
				"Basic": Stadia_OSMBright,
				"Topo": openTopoLayer,
				"Satellite": Esri_WorldImagery,
			};
			const overlays = {
				"Macrostrat": L.layerGroup([macro_strat])
			}
      this.mapLayer.layerControl = L.control.layers(layers, overlays).addTo(this.mapLayer);
      }

      if(map_options.scale !== false) {
         this.mapLayer.scaleControl = L.control.scale({maxWidth: 200}).addTo(this.mapLayer);
      }

      this.setLang(map_options.lang);

      this.mapLayer._onResize();
   }

   clearMap() {
      this.drawLayer.clearLayers();
      this.activeShape = null;
      this.shapes = [];
   }

   resetBounds() {
		if(!this.defaultBounds || this.defaultBounds.length !== 2) {
			return false;
		}

		try {
			this.mapLayer.fitBounds(this.defaultBounds);
			return true;
		} catch(e) {
			console.log("ERROR: Failed to reset map bounds")
			return false;
		}
   }

   setLang(lang) {
      switch(lang) {
         case "en":
            L.drawLocal = {
               draw: {
                  toolbar: {
                     actions: {
                        title: 'Cancel drawing',
                        text: 'Cancel'
                     },
                     finish: {
                        title: 'Finish drawing',
                        text: 'Finish'
                     },
                     undo: {
                        title: 'Delete last point drawn',
                        text: 'Delete last point'
                     },
                     buttons: {
                        polyline: 'Draw a polyline',
                        polygon: 'Draw a polygon',
                        rectangle: 'Draw a rectangle',
                        circle: 'Draw a circle',
                        marker: 'Draw a marker',
                        circlemarker: 'Draw a circlemarker'
                     }
                  },
                  handlers: {
                     circle: {
                        tooltip: {
                           start: 'Click and drag to draw circle.'
                        },
                        radius: 'Radius'
                     },
                     circlemarker: {
                        tooltip: {
                           start: 'Click map to place circle marker.'
                        }
                     },
                     marker: {
                        tooltip: {
                           start: 'Click map to place marker.'
                        }
                     },
                     polygon: {
                        tooltip: {
                           start: 'Click to start drawing shape.',
                           cont: 'Click to continue drawing shape.',
                           end: 'Click first point to close this shape.'
                        }
                     },
                     polyline: {
                        error: '<strong>Error:</strong> shape edges cannot cross!',
                        tooltip: {
                           start: 'Click to start drawing line.',
                           cont: 'Click to continue drawing line.',
                           end: 'Click last point to finish line.'
                        }
                     },
                     rectangle: {
                        tooltip: {
                           start: 'Click and drag to draw rectangle.'
                        }
                     },
                     simpleshape: {
                        tooltip: {
                           end: 'Release mouse to finish drawing.'
                        }
                     }
                  }
               },
               edit: {
                  toolbar: {
                     actions: {
                        save: {
                           title: 'Save changes',
                           text: 'Save'
                        },
                        cancel: {
                           title: 'Cancel editing, discards all changes',
                           text: 'Cancel'
                        },
                        clearAll: {
                           title: 'Clear all layers',
                           text: 'Clear All'
                        }
                     },
                     buttons: {
                        edit: 'Edit layers',
                        editDisabled: 'No layers to edit',
                        remove: 'Delete layers',
                        removeDisabled: 'No layers to delete'
                     }
                  },
                  handlers: {
                     edit: {
                        tooltip: {
                           text: 'Drag handles or markers to edit features.',
                           subtext: 'Click cancel to undo changes.'
                        }
                     },
                     remove: {
                        tooltip: {
                           text: 'Click on a feature to remove.'
                        }
                     }
                  }
               }
            };
            break;
         case "es":
            L.drawLocal = {
               draw: {
                  toolbar: {
                     actions: {
                        title: 'Cancelar dibujo',
                        text: 'Cancelar'
                     },
                     finish: {
                        title: 'Terminar de dibujar',
                        text: 'Finalizar'
                     },
                     undo: {
                        title: 'Eliminar el último punto dibujado',
                        text: 'Eliminar el último punto'
                     },
                     buttons: {
                        polyline: 'Dibujar una polilínea',
                        polygon: 'Dibujar un polígono',
                        rectangle: 'Dibujar un rectángulo',
                        circle: 'Dibuja un circulo',
                        marker: 'Dibujar un marcador',
                        circlemarker: 'Dibuja un marcadgeojsonor circular'
                     }
                  },
                  handlers: {
                     circle: {
                        tooltip: {
                           start: 'Haga clic y arrastre para dibujar un círculo.'
                        },
                        radius: 'Radio'
                     },
                     circlemarker: {
                        tooltip: {
                           start: 'Haga clic en el mapa para colocar un marcador circular.'
                        }
                     },
                     marker: {
                        tooltip: {
                           start: 'Haga clic en el mapa para colocar el marcador.'
                        }
                     },
                     polygon: {
                        tooltip: {
                           start: 'Haga clic para comenzar a dibujar la forma.',
                           cont: 'Haga clic para continuar dibujando la forma.',
                           end: 'Haga clic en el primer punto para cerrar esta forma.'
                        }
                     },
                     polyline: {
                        error: '<strong>Error:</strong> ¡Los bordes de las formas no pueden cruzarse!',
                        tooltip: {
                           start: 'Haga clic para comenzar a dibujar la línea.',
                           cont: 'Haga clic para continuar dibujando la línea.',
                           end: 'Haga clic en el último punto para finalizar la línea.'
                        }
                     },
                     rectangle: {
                        tooltip: {
                           start: 'Haga clic y arrastre para dibujar un rectángulo.'
                        }
                     },
                     simpleshape: {
                        tooltip: {
                           end: 'Suelte el mouse para terminar de dibujar.'
                        }
                     }
                  }
               },
               edit: {
                  toolbar: {
                     actions: {
                        save: {
                           title: 'Guardar cambios',
                           text: 'Guardar'
                        },
                        cancel: {
                           title: 'Cancelar la edición, descarta todos los cambios.',
                           text: 'Cancelar'
                        },
                        clearAll: {
                           title: 'Limpiar todas las capas',
                           text: 'Limpiar todo'
                        }
                     },
                     buttons: {
                        edit: 'Editar capas',
                        editDisabled: 'No hay capas para editar',
                        remove: 'Eliminar capas',
                        removeDisabled: 'No hay capas para eliminar'
                     }
                  },
                  handlers: {
                     edit: {
                        tooltip: {
                           text: 'Arrastre controladores o marcadores para editar funciones.',
                           subtext: 'Haga clic en cancelar para deshacer los cambios.'
                        }
                     },
                     remove: {
                        tooltip: {
                           text: 'Haga clic en una función para eliminarla.'
                        }
                     }
                  }
               }
            };
            break;
         case "fr":
            L.drawLocal = {
               draw: {
                  toolbar: {
                     actions: {
                        title: 'Annuler le dessin',
                        text: 'Annuler'
                     },
                     finish: {
                        title: 'Terminer le dessin',
                        text: 'Finition'
                     },
                     undo: {
                        title: 'Supprimer le dernier point dessiné',
                        text: 'Supprimer le dernier point'
                     },
                     buttons: {
                        polyline: 'Dessiner une polyligne',
                        polygon: 'Dessiner un polygone',
                        rectangle: 'Dessiner un rectangle',
                        circle: 'Dessine un cercle',
                        marker: 'Dessiner un marqueur',
                        circlemarker: 'Dessinez un marqueur de cercle'
                     }
                  },
                  handlers: {
                     circle: {
                        tooltip: {
                           start: 'Cliquez et faites glisser pour dessiner un cercle.'
                        },
                        radius: 'Rayon'
                     },
                     circlemarker: {
                        tooltip: {
                           start: 'Cliquez sur la carte pour placer un marqueur de cercle.'
                        }
                     },
                     marker: {
                        tooltip: {
                           start: 'Cliquez sur la carte pour placer le marqueur.'
                        }
                     },
                     polygon: {
                        tooltip: {
                           start: 'Cliquez pour commencer à dessiner la forme.',
                           cont: 'Cliquez pour continuer à dessiner la forme.',
                           end: 'Cliquez sur le premier point pour fermer cette forme.'
                        }
                     },
                     polyline: {
                        error: '<strong>Erreur:</strong> les bords de la forme ne peuvent pas se croiser !',
                        tooltip: {
                           start: 'Cliquez pour commencer à tracer une ligne.',
                           cont: 'Cliquez pour continuer à tracer la ligne.',
                           end: 'Cliquez sur le dernier point pour terminer la ligne.'
                        }
                     },
                     rectangle: {
                        tooltip: {
                           start: 'Cliquez et faites glisser pour dessiner un rectangle.'
                        }
                     },
                     simpleshape: {
                        tooltip: {
                           end: 'Relâchez la souris pour terminer le dessin.'
                        }
                     }
                  }
               },
               edit: {
                  toolbar: {
                     actions: {
                        save: {
                           title: 'Sauvegarder les modifications',
                           text: 'Sauver'
                        },
                        cancel: {
                           title: 'Annuler la modification, annule toutes les modifications',
                           text: 'Annuler'
                        },
                        clearAll: {
                           title: 'Effacer tous les calques',
                           text: 'Tout effacer'
                        }
                     },
                     buttons: {
                        edit: 'Modifier les calques',
                        editDisabled: 'Aucun calque à modifier',
                        remove: 'Supprimer des calques',
                        removeDisabled: 'Aucun calque à supprimer'
                     }
                  },
                  handlers: {
                     edit: {
                        tooltip: {
                           text: 'Faites glisser les poignées ou les marqueurs pour modifier les entités.',
                           subtext: 'Cliquez sur Annuler pour annuler les modifications.'
                        }
                     },
                     remove: {
                        tooltip: {
                           text: 'Cliquez sur une fonctionnalité à supprimer.'
                        }
                     }
                  }
               }
            };
            break;
      }
   }

   enableDrawing(drawOptions = this.DEFAULT_DRAW_OPTIONS, onDrawChange) {
      var drawnItems = new L.FeatureGroup();
      this.drawLayer = drawnItems;
      this.mapLayer.addLayer(drawnItems);

	  L.Draw.Polygon.prototype.options.shapeOptions.draggable = true;

      //Jank workaround for leaflet-draw api
      const setDrawColor = (drawOption) => {
         if(drawOptions[drawOption] === false)
            return;

         if(!drawOptions[drawOption]) drawOptions[drawOption] = { };

         if(!drawOptions[drawOption].shapeOptions)
            drawOptions[drawOption].shapeOptions = drawOptions.drawColor;
      }

      if(drawOptions.drawColor) {
         //Setting Styles for Pre Rendered
         L.Path.mergeOptions(drawOptions.drawColor);
         //Set Color for All Manual Draws 
         setDrawColor("polyline");
         setDrawColor("polygon");
         setDrawColor("rectangle");
         setDrawColor("circle");
      }

      if(drawOptions.map_mode_strict) {
         if(drawOptions.mode !== "polygon") drawOptions.polygon = false;
         if(drawOptions.mode !== "circle") drawOptions.circle = false;
         if(drawOptions.mode !== "rectangle") drawOptions.rectangle = false;
         if(drawOptions.mode !== "marker") drawOptions.marker = false;
         if(drawOptions.mode !== "polyline") drawOptions.polyline = false;
      }

      if(drawOptions.control || drawOptions.control === undefined) {
         var drawControl = new L.Control.Draw({
            position: 'topright',
            draw: drawOptions,
            edit: {
               featureGroup: drawnItems,
			   edit: {
			     moveMakers: false,
			   }
            }
         });

         this.mapLayer.addControl(drawControl);

         //Event saved edit 
         this.mapLayer.on('draw:edited', function(e) {
            if(!e.layers || !e.layers._layers) return;
            ///Some Extra steps to get at the layer
            const layer = e.layers._layers;
            const keys = Object.keys(layer);
            let type = this.activeShape.type;

            if(keys.length > 0) {
               const edited_shape = getShapeCoords(type, layer[keys[0]]);
               this.activeShape = edited_shape;
               this.shapes = this.shapes.map(s=> s.layer._leaflet_id === edited_shape.layer._leaflet_id? edited_shape: s);
            }

            if(onDrawChange) onDrawChange(this.activeShape);
         }.bind(this))

         //Event saved delete 
         this.mapLayer.on('draw:deleted', function(e) {
            const ids = Object.keys(e.layers._layers);
            this.shapes = this.shapes.filter(s => !ids.includes(`${s.layer._leaflet_id}`))
            this.activeShape = null;
            if(onDrawChange) onDrawChange(this.activeShape);
         }.bind(this))

         //Fires on New Draw
         this.mapLayer.on('draw:created', function (e) {
            if(!drawOptions || !drawOptions.multiDraw) {
               this.drawLayer.clearLayers();
            }

            const id = this.shapes.length;

            const layer = e.layer;
            this.drawLayer.addLayer(layer);

            this.activeShape = getShapeCoords(e.layerType, e.layer);
            this.activeShape.id = id;
            this.shapes.push(this.activeShape);

            if(onDrawChange) onDrawChange(this.activeShape);
         }.bind(this))
      }
         switch(drawOptions.mode) {
            case "polygon": 
               document.querySelector(".leaflet-draw-draw-polygon").click();
            break;
            case "rectangle": 
               document.querySelector(".leaflet-draw-draw-rectangle").click();
            break;
            case "marker": 
               document.querySelector(".leaflet-draw-draw-marker").click();
            break;
            case "circle":
               document.querySelector(".leaflet-draw-draw-circle").click();
            break;
         }

   }

   drawShape(shape, fitbounds=true) {
      const id = this.shapes.length;

      const fitShape = () => {
         this.mapLayer.fitBounds(this.activeShape.layer.getBounds());
      }

      switch(shape.type) {
         case "geoJSON":
            const geoJSON = L.geoJSON(shape.geoJSON);

            for(let layer_id of Object.keys(geoJSON._layers)) {
               for(let polygon of geoJSON._layers[layer_id]._latlngs) {
                  this.drawShape({type:"polygon", latlngs: polygon}, false)
               }
            }

            this.mapLayer.fitBounds(geoJSON.getBounds());
            return;
         case "polygon":
            const poly = L.polygon(shape.latlngs);
            this.activeShape = getShapeCoords(shape.type, poly);
            poly.addTo(this.drawLayer);
            if(fitbounds) fitShape();
            break;
         case "rectangle":
            const rec = L.rectangle([
               [shape.upperLat, shape.rightLng],
               [shape.lowerLat, shape.leftLng]
            ]);
            this.activeShape = getShapeCoords(shape.type, rec);
            rec.addTo(this.drawLayer)
            if(fitbounds) fitShape();
            break;
         case "circle":
            const circ = L.circle(shape.latlng, shape.radius);
            this.activeShape = getShapeCoords(shape.type, circ);
            circ.addTo(this.drawLayer);
            if(fitbounds) fitShape();
            break;
         default:
            throw Error(`Can't draw ${shape.type}`)
      }

      if(this.activeShape) {
         this.activeShape.id = id;
         this.shapes.push(this.activeShape);
      }
   }
}

function getShapeCoords(layerType, layer) {
   if(!layer)
      return null;

   let shape ={
      type: layerType,
      layer: layer,
   };

   const SIG_FIGS = 6;

   switch(layerType) {
      case "polygon":
         let latlngs = layer._latlngs[0].map(coord=> [coord.lat, coord.lng]);
         latlngs.push(latlngs[0]);
         let polygon = latlngs.map(coord => 
            (`${coord[0].toFixed(SIG_FIGS)} ${coord[1].toFixed(SIG_FIGS)}`));
         shape.latlngs = latlngs;
         shape.wkt = "POLYGON ((" + polygon.join(',') + "))";
         shape.center = layer.getBounds().getCenter();
         break;
      case "rectangle":
         const northEast = layer._bounds._northEast;
         shape.upperLat =  northEast.lat;
         shape.rightLng =  northEast.lng;

         const southWest = layer._bounds._southWest;
         shape.lowerLat =southWest.lat;
         shape.leftLng = southWest.lng;

         shape.center = layer.getBounds().getCenter();
         break;
      case "circle":
         shape.radius = layer._mRadius;
         shape.center = {
            lat: layer._latlng.lat,
            lng: layer._latlng.lng
         };
         break;
      default:
         throw Error(`Couldn't parse "${layerType}" as a shape type`);
   }

   return shape;
}
