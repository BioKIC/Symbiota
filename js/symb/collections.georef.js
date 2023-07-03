function utm2LatLng(zValue, eValue, nValue, datum, hemisphere){
	if(hemisphere){
		hemisphere = hemisphere.toLowerCase().trim().substr(0,1);
	}
	if(isNaN(zValue)){
		let zGrid = zValue.match(/\D/);
		zValue = parseInt(zValue);
		if(zGrid && hemisphere != 'n' && hemisphere != 's'){
			zGrid = zGrid.toString().toLowerCase();
			if(zGrid == 's'){
				if((nValue > 3400000 && nValue < 4600000) && zValue != 19){
					hemisphere = 'n';
				}
				else{
					hemisphere = 's';
				}
			}
			else if(zGrid < 'n'){
				hemisphere = 's';
			}
			else{
				hemisphere = 'n';
			}
		}
	}
	if(hemisphere == 's'){
		nValue = 10000000 - nValue;
	}

	//Datum assumed to be WGS84 or NAD83
	let d = 0.99960000000000004; // scale along long0
	let d1 = 6378137; // Polar Radius
	let d2 = 0.00669438;
	if(datum.match(/nad\s?27/i)){
		//datum is NAD27
		d1 = 6378206; 
		d2 = 0.006768658;
	}
	
	let d4 = (1 - Math.sqrt(1 - d2)) / (1 + Math.sqrt(1 - d2));
	let d15 = eValue - 500000;
	let d11 = ((zValue - 1) * 6 - 180) + 3;
	let d3 = d2 / (1 - d2);
	let d10 = nValue / d;
	let d12 = d10 / (d1 * (1 - d2 / 4 - (3 * d2 * d2) / 64 - (5 * Math.pow(d2,3) ) / 256));
	let d14 = d12 + ((3 * d4) / 2 - (27 * Math.pow(d4,3) ) / 32) * Math.sin(2 * d12) + ((21 * d4 * d4) / 16 - (55 * Math.pow(d4,4) ) / 32) * Math.sin(4 * d12) + ((151 * Math.pow(d4,3) ) / 96) * Math.sin(6 * d12);
	let d5 = d1 / Math.sqrt(1 - d2 * Math.sin(d14) * Math.sin(d14));
	let d6 = Math.tan(d14) * Math.tan(d14);
	let d7 = d3 * Math.cos(d14) * Math.cos(d14);
	let d8 = (d1 * (1 - d2)) / Math.pow(1 - d2 * Math.sin(d14) * Math.sin(d14), 1.5);
	let d9 = d15 / (d5 * d);
	let d17 = d14 - ((d5 * Math.tan(d14)) / d8) * (((d9 * d9) / 2 - (((5 + 3 * d6 + 10 * d7) - 4 * d7 * d7 - 9 * d3) * Math.pow(d9,4) ) / 24) + (((61 + 90 * d6 + 298 * d7 + 45 * d6 * d6) - 252 * d3 - 3 * d7 * d7) * Math.pow(d9,6) ) / 720);
	let latValue = (d17 / Math.PI) * 180; // Breddegrad (N)
	let d18 = ((d9 - ((1 + 2 * d6 + d7) * Math.pow(d9,3) ) / 6) + (((((5 - 2 * d7) + 28 * d6) - 3 * d7 * d7) + 8 * d3 + 24 * d6 * d6) * Math.pow(d9,5) ) / 120) / Math.cos(d14);
	let lngValue = d11 + ((d18 / Math.PI) * 180); // Længdegrad (Ø)
	latValue = Math.round(latValue*1000000)/1000000;
	lngValue = Math.round(lngValue*1000000)/1000000;
	if(hemisphere == 's' && latValue > 0) {
		latValue = latValue * -1;
	}
	return latValue + "," + lngValue;
}
