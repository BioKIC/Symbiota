/*
	Copyright (c) 2004-2016, The JS Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/

//>>built
define("dojo/json5/util",["./unicode"],function(_1){
return {isSpaceSeparator:function(c){
return typeof c==="string"&&_1.Space_Separator.test(c);
},isIdStartChar:function(c){
return typeof c==="string"&&((c>="a"&&c<="z")||(c>="A"&&c<="Z")||(c==="$")||(c==="_")||_1.ID_Start.test(c));
},isIdContinueChar:function(c){
return typeof c==="string"&&((c>="a"&&c<="z")||(c>="A"&&c<="Z")||(c>="0"&&c<="9")||(c==="$")||(c==="_")||(c==="‌")||(c==="‍")||_1.ID_Continue.test(c));
},isDigit:function(c){
return typeof c==="string"&&/[0-9]/.test(c);
},isHexDigit:function(c){
return typeof c==="string"&&/[0-9A-Fa-f]/.test(c);
},};
});
