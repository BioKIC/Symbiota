<?php
// Rename labeljson_template.php to labeljson.php and modify or replace example label formats below
$LABEL_FORMAT_JSON = '{"labelFormats": [
	{
		"title":"Generic Herbarium Label",
		"displaySpeciesAuthor":1,
		"displayBarcode":0,
		"labelType":"2",
		"customStyles":"body{ font-size:10pt; }",
		"defaultCss":"../../css/v202209/symbiota/collections/reports/labelhelpers.css",
		"customCss":"",
		"customJs":"",
		"pageSize":"letter",
		"labelHeader":{
			"prefix":"Flora of ",
			"midText":3,
			"suffix":" county",
			"className":"text-center font-bold font-sans text-2xl",
			"style":"margin-bottom:10px;"
		},
		"labelBlocks":[
			{"divBlock":{"className":"label-block","blocks":[
				{"divBlock":{"className":"taxonomy my-2 text-lg","blocks":[
					{"fieldBlock":[
						{"field":"identificationqualifier"},
						{"field":"speciesname","className":"font-bold italic"},
						{"field":"parentauthor"},
						{"field":"taxonrank","className":"font-bold"},
						{"field":"infraspecificepithet","className":"font-bold italic"},
						{"field":"scientificnameauthorship"}
						],"delimiter":" "
					},
					{"fieldBlock":[{"field":"family","styles":["float:right"]}]}
				]}},
				{"fieldBlock":[{"field":"identifiedby","prefix":"Det by: "},{"field":"dateidentified"}],"delimiter":" "},
				{"fieldBlock":[{"field":"identificationreferences"}]},
				{"fieldBlock":[{"field":"identificationremarks"}]},
				{"fieldBlock":[{"field":"taxonremarks"}]},
				{"divBlock":{"className":"localDiv","className":"text-lg","style":"margin-top:10px;","blocks":[
					{"fieldBlock":[{"field":"country","className":"font-bold"},{"field":"stateprovince","style":"font-weight:bold"},{"field":"county"},{"field":"municipality"},{"field":"locality"}],"delimiter":", "}
				]}},
				{"fieldBlock":[{"field":"decimallatitude"},{"field":"decimallongitude","style":"margin-left:10px"},{"field":"coordinateuncertaintyinmeters","prefix":"+-","suffix":" meters","style":"margin-left:10px"},{"field":"geodeticdatum","prefix":"[","suffix":"]","style":"margin-left:10px"}]},
				{"fieldBlock":[{"field":"verbatimcoordinates"}]},
				{"fieldBlock":[{"field":"elevationinmeters","prefix":"Elev: ","suffix":"m. "},{"field":"verbatimelevation"}]},
				{"fieldBlock":[{"field":"habitat","suffix":"."}]},
				{"fieldBlock":[{"field":"substrate","suffix":"."}]},
				{"fieldBlock":[{"field":"verbatimattributes"},{"field":"establishmentmeans"}],"delimiter":"; "},
				{"fieldBlock":[{"field":"associatedtaxa","prefix":"Associated species: ","className":"italic"}]},
				{"fieldBlock":[{"field":"occurrenceremarks"}]},
				{"fieldBlock":[{"field":"typestatus"}]},
				{"divBlock":{"className":"collector","style":"margin-top:10px;","blocks":[
					{"fieldBlock":[{"field":"recordedby","style":"float:left"},{"field":"recordnumber","style":"float:left;margin-left:10px"},{"field":"eventdate","style":"float:right"}]},
					{"fieldBlock":[{"field":"associatedcollectors","prefix":"with: "}],"style":"clear:both; margin-left:10px;"}
				]}}
			]}}
		],
		"labelFooter":{
			"textValue":"",
			"className":"text-center font-bold font-sans",
			"style":"margin-top:10px;"
		}
	},
	{
		"title":"Generic Vertebrate Label",
		"displaySpeciesAuthor":0,
		"displayBarcode":0,
		"labelType":"3",
		"customStyles":"body{ font-size:10pt; }",
		"defaultCss":"../../css/v202209/symbiota/collections/reports/labelhelpers.css",
		"customCss":"",
		"customJs":"",
		"pageSize":"letter",
		"labelHeader":{
			"prefix":"",
			"midText":0,
			"suffix":"",
			"className": "text-center font-bold font-sans text-2xl",
			"style":"text-align:center;margin-bottom:5px;font:bold 7pt arial,sans-serif;clear:both;"
		},
		"labelFooter":{
			"textValue":"",
			"className": "text-center font-bold font-sans text-2xl",
			"style":"text-align:center;margin-top:10px;font:bold 10pt arial,sans-serif;clear:both;"
		},
		"labelBlocks":[
			{"divBlock":{"className":"labelBlockDiv","blocks":[
				{"fieldBlock":[{"field":"family","styles":["margin-bottom:2px;font-size:pt"]}]},
				{"divBlock":{"className":"taxonomyDiv","style":"font-size:10pt;","blocks":[
					{"fieldBlock":[
						{"field":"identificationqualifier"},
						{"field":"speciesname","style":"font-weight:bold;font-style:italic"},
						{"field":"parentauthor"},
						{"field":"taxonrank","style":"font-weight:bold"},
						{"field":"infraspecificepithet","style":"font-weight:bold;font-style:italic"},
						{"field":"scientificnameauthorship"}
						],"delimiter":" "
					}
				]}},
				{"fieldBlock":[{"field":"identifiedby","prefix":"Det by: "},{"field":"dateidentified"}]},
				{"fieldBlock":[{"field":"identificationreferences"}]},
				{"fieldBlock":[{"field":"identificationremarks"}]},
				{"fieldBlock":[{"field":"taxonremarks"}]},
				{"fieldBlock":[{"field":"catalognumber","style":"font-weight:bold;font-size:14pt;margin:5pt 0pt;"}]},
				{"divBlock":{"className":"localDiv","style":"margin-top:3px;padding-top:3px;border-top:3px solid black","blocks":[
					{"fieldBlock":[{"field":"country"},{"field":"stateprovince","prefix":", "},{"field":"county","prefix":", "},{"field":"municipality","prefix":", "},{"field":"locality","prefix":": "},{"field":"decimallatitude","prefix":": ","suffix":"° N"},{"field":"decimallongitude","prefix":" ","suffix":"° W"},{"field":"coordinateuncertaintyinmeters","prefix":" +-","suffix":" meters","style":"margin-left:10px"},{"field":"elevationinmeters","prefix":", ","suffix":"m."}]}
				]}},
				{"divBlock":{"className":"collectorDiv","style":"margin-top:10px;font-size:6pt;clear:both;","blocks":[
					{"fieldBlock":[{"field":"recordedby","style":"float:left;","prefix":"Coll.: ","prefixStyle":"font-weight:bold"},{"field":"preparations","style":"float:right","prefix":"Prep.: "}]}
				]}},
				{"divBlock":{"className":"collectorDiv","style":"margin-top:10px;font-size:6pt;clear:both;","blocks":[
					{"fieldBlock":[{"field":"recordnumber","style":"float:left;","prefix":"Coll. No: ","prefixStyle":"font-weight:bold"},{"field":"eventdate","style":"float:right","prefix":"Date: "}]}
				]}}
			]}}
		]
	},
  {
    "title": "Generic Lichen Packet",
    "labelHeader": {
      "prefix": "Lichens of ",
      "midText": "2",
      "suffix": ", United States",
      "className": "text-2xl font-family-arial mt-2",
      "style": ""
    },
    "labelFooter": {
      "textValue": "Custom Collection Name",
      "className": "",
      "style": ""
    },
    "customStyles": "",
    "defaultCss": "../../css/v202209/symbiota/collections/reports/labelhelpers.css",
    "customCss": "../../css/v202209/symbiota/collections/reports/lichenpacket.css",
    "customJS": "../../js/symb/lichenpacket.js",
    "labelType": "packet",
    "pageSize": "letter",
    "displaySpeciesAuthor": 1,
    "displayBarcode": 1,
    "labelBlocks": [
      {
        "divBlock": {
          "className": "label-blocks",
          "style": "",
          "blocks": [
            {
              "fieldBlock": [
                {
                  "field": "scientificname",
                  "className": "font-bold italic text-xl font-family-arial"
                },
                {
                  "field": "scientificnameauthorship",
                  "className": "font-family-arial text-sm"
                }
              ],
              "delimiter": " ",
              "className": "mt-3"
            },
            {
              "fieldBlock": [
                {
                  "field": "identifiedby",
                  "className": "font-family-arial text-sm ml-2",
                  "prefix": "det. "
                },
                {
                  "field": "dateidentified",
                  "className": "font-family-arial text-sm ml-2"
                }
              ],
              "delimiter": " "
            },
            {
              "fieldBlock": [
                {
                  "field": "country",
                  "className": "font-bold font-family-arial text-sm"
                },
                {
                  "field": "stateprovince",
                  "className": "font-bold font-family-arial text-sm"
                },
                {
                  "field": "county",
                  "className": "font-family-arial text-sm"
                },
                {
                  "field": "municipality",
                  "className": "text-sm font-family-arial"
                },
                {
                  "field": "locality",
                  "className": "font-family-arial text-sm"
                }
              ],
              "delimiter": ", ",
              "className": "mt-2 ml-2"
            },
            {
              "fieldBlock": [
                {
                  "field": "decimallatitude",
                  "className": "font-family-arial text-sm"
                },
                {
                  "field": "decimallongitude",
                  "className": "font-family-arial text-sm",
                  "prefix": ", ",
                  "suffix": ""
                },
                {
                  "field": "elevationinmeters",
                  "className": "font-family-arial text-sm",
                  "prefix": "; ",
                  "suffix": "m."
                }
              ],
              "delimiter": "",
              "className": "mt-2 ml-2"
            },
            {
              "fieldBlock": [
                {
                  "field": "habitat",
                  "className": "font-family-arial text-sm"
                },
                {
                  "field": "associatedtaxa",
                  "className": "font-family-arial text-sm"
                },
                {
                  "field": "substrate",
                  "className": "font-family-arial text-sm"
                },
                {
                  "field": "occurrenceremarks",
                  "className": "font-family-arial text-sm",
                  "prefix": ""
                }
              ],
              "delimiter": "; ",
              "className": "mt-2 ml-2"
            },
            {
              "fieldBlock": [
                {
                  "field": "recordedby",
                  "className": "font-bold font-family-arial text-sm"
                },
                {
                  "field": "recordnumber",
                  "className": "font-family-arial text-sm font-bold"
                },
                {
                  "field": "eventdate",
                  "className": "font-family-arial text-sm"
                }
              ],
              "delimiter": " ",
              "className": "mt-3"
            }
          ]
        }
      }
    ]
  }
]}';
?>