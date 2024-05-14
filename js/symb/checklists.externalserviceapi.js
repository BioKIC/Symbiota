/*--------------------------------------------------------------------
|  This Symbiota enhancement was made possible with support from
|   the United States Institute of Museum and Library Services grant
|   MG-70-19-0057-19, to the New York Botanical Garden (NYBG).
|  Programming performed by Christopher D. Tyrrell, all errors and
|   omissions are his.
*-------------------------------------------------------------------*/



function extractiNatTaxaIdAndName(resultsjson) {
    let outputArr = Array();
    resultsjson.forEach(element => {
        outputArr.push({id: element.taxon.id, name: element.taxon.name});
    });
    return outputArr;
}

// Note: as of the time of coding, iNaturalist API v2 is in beta and this section may require some adjusting post-release.
//       API v1 does not allow custom fields (i.e., taxa names) to be returned.
async function fetchiNatPage1(projID, iconictaxon = '', qualitygrade = 'research') {
    let apiurl = '';
    if(iconictaxon == '') {
        apiurl = `https://api.inaturalist.org/v2/observations/species_counts?project_id=${projID}&quality_grade=${qualitygrade}&per_page=500&fields=(taxon:(name:!t))`;
    } else {
        apiurl = `https://api.inaturalist.org/v2/observations/species_counts?project_id=${projID}&quality_grade=${qualitygrade}&iconic_taxa=${iconictaxon}&per_page=500&fields=(taxon:(name:!t))`;
    }
    const resp = await fetch(apiurl);
    try {
        if(resp.ok) {
            const page1 = await resp.json();
            return page1;
        }
    } catch(err) {
        console.error(err);
    }
}

async function fetchiNatAdditionalPages(loopnum, projID, iconictaxon = '', qualitygrade = 'research') {
    try {
        let apiurl = '';
        if(iconictaxon == '') {
            apiurl = `https://api.inaturalist.org/v2/observations/species_counts?project_id=${projID}&quality_grade=${qualitygrade}&per_page=500&fields=(taxon:(name:!t))`;
        } else {
            apiurl = `https://api.inaturalist.org/v2/observations/species_counts?project_id=${projID}&quality_grade=${qualitygrade}&iconic_taxa=${iconictaxon}&per_page=500&fields=(taxon:(name:!t))`;
        }
        let allapiurls = [];
        if(loopnum > 1) {
            for(let i = 2; i <= loopnum; i++) {
                allapiurls.push(apiurl + `&page=${i}`);
            }
        }
        const resps = await Promise.all(allapiurls.map(async (url) => {
            const resp = await fetch(url);
            // Throttle to < 100 requests per minute as per iNaturalist API guidelines 
            await new Promise(governor => setTimeout(governor, 600));
            return resp;
        }));
        const resppromises = resps.map(result => result.json());
        const additionalPages = await Promise.all(resppromises);
        return additionalPages;
    } catch(err) {
        console.error(err);
    }
}

async function iNatPlotPoints(llbounds, projID, iconictaxon = '', qualitygrade = 'research', rank = 'species') {
    let apiurl = '';
    if(iconictaxon == '') {
        // add something here to switch to API v2 if v1 fails?
        apiurl = `https://api.inaturalist.org/v1/points/${zoom}/${xtile}/${ytile}.grid.json?mappable=true&project_id=${projID}&rank=${rank}&quality_grade=${qualitygrade}&order=asc&order_by=updated_at`;
    } else {
        apiurl = `https://api.inaturalist.org/v1/points/${zoom}/${xtile}/${ytile}.grid.json?mappable=true&project_id=${projID}&rank=${rank}&iconic_taxa=${iconictaxon}&quality_grade=${qualitygrade}&order=asc&order_by=updated_at`;
    }
    const resp = await fetch(apiurl);
    try {
        if(resp.ok) {
            const page1 = await resp.json();
            return page1;
        }
    } catch(err) {
        console.error(err);
    }

}


async function iNatGetVoucher(obsID) {
    let apiurl = `https://api.inaturalist.org/v1/observations/`;
    const resp = await fetch(apiurl+obsID);
    try {
        if(resp.ok) {
            const obsjson = await resp.json();
            return obsjson;
        }
    } catch(err) {
        console.error(err);
    }
}

// VOUCHERS
function parseVoucherIDs(textboxID) {
    let txtbox = document.getElementById(textboxID);
    const extIDUntrimmedArr = txtbox.value.split(",");
    const extIDArr = extIDUntrimmedArr.map(element => {
        return element.trim();
    });
    return extIDArr;
}

function retrieveVoucherInfo(taxonID) {
    let idArr = parseVoucherIDs('i-'+taxonID).join('%2C');
    iNatGetVoucher(idArr)
        .then(resp => {
            const reportingSpan = document.getElementById('r-'+taxonID);
            const hiddenVoucherField = document.getElementById('v-'+taxonID);
            let storeJsonStr = '';
            let retrievedVouch = '';
            for (const obs of resp.results) {
                if(storeJsonStr == '') {
                    storeJsonStr = encodeURIComponent('[');
                } else {
                    storeJsonStr += encodeURIComponent(',');
                }
                storeJsonStr += encodeURIComponent('{"id":"'+obs.id+'","taxon":"'+obs.taxon.name+'","user":"'+obs.user.login+'","date":"'+obs.observed_on_details.date+'","repository":"iNat","lng":'+obs.geojson.coordinates[0]+',"lat":'+obs.geojson.coordinates[1]+'}');
                retrievedVouch += '<a href="https://www.inaturalist.org/observations/'+obs.id+'" target="_blank">' + obs.user.login + ' ' + obs.observed_on_details.date + ' [iNat]</a>; ';
            }
            storeJsonStr += encodeURIComponent(']');
            reportingSpan.innerHTML = retrievedVouch;
            hiddenVoucherField.setAttribute("value", hiddenVoucherField.value + storeJsonStr);
        })
        .catch(error => {
            error.message;
        })
}