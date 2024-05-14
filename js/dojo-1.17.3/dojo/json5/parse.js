/*
	Copyright (c) 2004-2016, The JS Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/

//>>built
define("dojo/json5/parse",["../string","./util"],function(_1,_2){
var _3;
var _4;
var _5;
var _6;
var _7;
var _8;
var _9;
var _a;
var _b;
function _c(_d,_e){
_3=String(_d);
_4="start";
_5=[];
_6=0;
_7=1;
_8=0;
_9=undefined;
_a=undefined;
_b=undefined;
do{
_9=_f();
_10[_4]();
}while(_9.type!=="eof");
if(typeof _e==="function"){
return _11({"":_b},"",_e);
}
return _b;
};
function _11(_12,_13,_14){
var _15=_12[_13];
if(_15!=null&&typeof _15==="object"){
for(var _16 in _15){
var _17=_11(_15,_16,_14);
if(_17===undefined){
delete _15[_16];
}else{
_15[_16]=_17;
}
}
}
return _14.call(_12,_13,_15);
};
var _18;
var _19;
var _1a;
var _1b;
var c;
function _f(){
_18="default";
_19="";
_1a=false;
_1b=1;
for(;;){
c=_1c();
var _1d=_1e[_18]();
if(_1d){
return _1d;
}
}
};
function _1c(){
if(_3[_6]){
return _1.fromCodePoint(_1.codePointAt(_3,_6));
}
};
function _1f(){
var c=_1c();
if(c==="\n"){
_7++;
_8=0;
}else{
if(c){
_8+=c.length;
}else{
_8++;
}
}
if(c){
_6+=c.length;
}
return c;
};
var _1e={"default":function(){
switch(c){
case "\t":
case "\v":
case "\f":
case " ":
case " ":
case "﻿":
case "\n":
case "\r":
case " ":
case " ":
_1f();
return;
case "/":
_1f();
_18="comment";
return;
case undefined:
_1f();
return _20("eof");
}
if(_2.isSpaceSeparator(c)){
_1f();
return;
}
return _1e[_4]();
},comment:function(){
switch(c){
case "*":
_1f();
_18="multiLineComment";
return;
case "/":
_1f();
_18="singleLineComment";
return;
}
throw _21(_1f());
},multiLineComment:function(){
switch(c){
case "*":
_1f();
_18="multiLineCommentAsterisk";
return;
case undefined:
throw _21(_1f());
}
_1f();
},multiLineCommentAsterisk:function(){
switch(c){
case "*":
_1f();
return;
case "/":
_1f();
_18="default";
return;
case undefined:
throw _21(_1f());
}
_1f();
_18="multiLineComment";
},singleLineComment:function(){
switch(c){
case "\n":
case "\r":
case " ":
case " ":
_1f();
_18="default";
return;
case undefined:
_1f();
return _20("eof");
}
_1f();
},value:function(){
switch(c){
case "{":
case "[":
return _20("punctuator",_1f());
case "n":
_1f();
_22("ull");
return _20("null",null);
case "t":
_1f();
_22("rue");
return _20("boolean",true);
case "f":
_1f();
_22("alse");
return _20("boolean",false);
case "-":
case "+":
if(_1f()==="-"){
_1b=-1;
}
_18="sign";
return;
case ".":
_19=_1f();
_18="decimalPointLeading";
return;
case "0":
_19=_1f();
_18="zero";
return;
case "1":
case "2":
case "3":
case "4":
case "5":
case "6":
case "7":
case "8":
case "9":
_19=_1f();
_18="decimalInteger";
return;
case "I":
_1f();
_22("nfinity");
return _20("numeric",Infinity);
case "N":
_1f();
_22("aN");
return _20("numeric",NaN);
case "\"":
case "'":
_1a=(_1f()==="\"");
_19="";
_18="string";
return;
}
throw _21(_1f());
},identifierNameStartEscape:function(){
if(c!=="u"){
throw _21(_1f());
}
_1f();
var u=_23();
switch(u){
case "$":
case "_":
break;
default:
if(!_2.isIdStartChar(u)){
throw _24();
}
break;
}
_19+=u;
_18="identifierName";
},identifierName:function(){
switch(c){
case "$":
case "_":
case "‌":
case "‍":
_19+=_1f();
return;
case "\\":
_1f();
_18="identifierNameEscape";
return;
}
if(_2.isIdContinueChar(c)){
_19+=_1f();
return;
}
return _20("identifier",_19);
},identifierNameEscape:function(){
if(c!=="u"){
throw _21(_1f());
}
_1f();
var u=_23();
switch(u){
case "$":
case "_":
case "‌":
case "‍":
break;
default:
if(!_2.isIdContinueChar(u)){
throw _24();
}
break;
}
_19+=u;
_18="identifierName";
},sign:function(){
switch(c){
case ".":
_19=_1f();
_18="decimalPointLeading";
return;
case "0":
_19=_1f();
_18="zero";
return;
case "1":
case "2":
case "3":
case "4":
case "5":
case "6":
case "7":
case "8":
case "9":
_19=_1f();
_18="decimalInteger";
return;
case "I":
_1f();
_22("nfinity");
return _20("numeric",_1b*Infinity);
case "N":
_1f();
_22("aN");
return _20("numeric",NaN);
}
throw _21(_1f());
},zero:function(){
switch(c){
case ".":
_19+=_1f();
_18="decimalPoint";
return;
case "e":
case "E":
_19+=_1f();
_18="decimalExponent";
return;
case "x":
case "X":
_19+=_1f();
_18="hexadecimal";
return;
}
return _20("numeric",_1b*0);
},decimalInteger:function(){
switch(c){
case ".":
_19+=_1f();
_18="decimalPoint";
return;
case "e":
case "E":
_19+=_1f();
_18="decimalExponent";
return;
}
if(_2.isDigit(c)){
_19+=_1f();
return;
}
return _20("numeric",_1b*Number(_19));
},decimalPointLeading:function(){
if(_2.isDigit(c)){
_19+=_1f();
_18="decimalFraction";
return;
}
throw _21(_1f());
},decimalPoint:function(){
switch(c){
case "e":
case "E":
_19+=_1f();
_18="decimalExponent";
return;
}
if(_2.isDigit(c)){
_19+=_1f();
_18="decimalFraction";
return;
}
return _20("numeric",_1b*Number(_19));
},decimalFraction:function(){
switch(c){
case "e":
case "E":
_19+=_1f();
_18="decimalExponent";
return;
}
if(_2.isDigit(c)){
_19+=_1f();
return;
}
return _20("numeric",_1b*Number(_19));
},decimalExponent:function(){
switch(c){
case "+":
case "-":
_19+=_1f();
_18="decimalExponentSign";
return;
}
if(_2.isDigit(c)){
_19+=_1f();
_18="decimalExponentInteger";
return;
}
throw _21(_1f());
},decimalExponentSign:function(){
if(_2.isDigit(c)){
_19+=_1f();
_18="decimalExponentInteger";
return;
}
throw _21(_1f());
},decimalExponentInteger:function(){
if(_2.isDigit(c)){
_19+=_1f();
return;
}
return _20("numeric",_1b*Number(_19));
},hexadecimal:function(){
if(_2.isHexDigit(c)){
_19+=_1f();
_18="hexadecimalInteger";
return;
}
throw _21(_1f());
},hexadecimalInteger:function(){
if(_2.isHexDigit(c)){
_19+=_1f();
return;
}
return _20("numeric",_1b*Number(_19));
},string:function(){
switch(c){
case "\\":
_1f();
_19+=_25();
return;
case "\"":
if(_1a){
_1f();
return _20("string",_19);
}
_19+=_1f();
return;
case "'":
if(!_1a){
_1f();
return _20("string",_19);
}
_19+=_1f();
return;
case "\n":
case "\r":
throw _21(_1f());
case " ":
case " ":
_26(c);
break;
case undefined:
throw _21(_1f());
}
_19+=_1f();
},start:function(){
switch(c){
case "{":
case "[":
return _20("punctuator",_1f());
}
_18="value";
},beforePropertyName:function(){
switch(c){
case "$":
case "_":
_19=_1f();
_18="identifierName";
return;
case "\\":
_1f();
_18="identifierNameStartEscape";
return;
case "}":
return _20("punctuator",_1f());
case "\"":
case "'":
_1a=(_1f()==="\"");
_18="string";
return;
}
if(_2.isIdStartChar(c)){
_19+=_1f();
_18="identifierName";
return;
}
throw _21(_1f());
},afterPropertyName:function(){
if(c===":"){
return _20("punctuator",_1f());
}
throw _21(_1f());
},beforePropertyValue:function(){
_18="value";
},afterPropertyValue:function(){
switch(c){
case ",":
case "}":
return _20("punctuator",_1f());
}
throw _21(_1f());
},beforeArrayValue:function(){
if(c==="]"){
return _20("punctuator",_1f());
}
_18="value";
},afterArrayValue:function(){
switch(c){
case ",":
case "]":
return _20("punctuator",_1f());
}
throw _21(_1f());
},end:function(){
throw _21(_1f());
}};
function _20(_27,_28){
return {type:_27,value:_28,line:_7,column:_8};
};
function _22(s){
for(var _29=0,s_1=s;_29<s_1.length;_29++){
var c_1=s_1[_29];
var p=_1c();
if(p!==c_1){
throw _21(_1f());
}
_1f();
}
};
function _25(){
var c=_1c();
switch(c){
case "b":
_1f();
return "\b";
case "f":
_1f();
return "\f";
case "n":
_1f();
return "\n";
case "r":
_1f();
return "\r";
case "t":
_1f();
return "\t";
case "v":
_1f();
return "\v";
case "0":
_1f();
if(_2.isDigit(_1c())){
throw _21(_1f());
}
return "\x00";
case "x":
_1f();
return _2a();
case "u":
_1f();
return _23();
case "\n":
case " ":
case " ":
_1f();
return "";
case "\r":
_1f();
if(_1c()==="\n"){
_1f();
}
return "";
case "1":
case "2":
case "3":
case "4":
case "5":
case "6":
case "7":
case "8":
case "9":
throw _21(_1f());
case undefined:
throw _21(_1f());
}
return _1f();
};
function _2a(){
var _2b="";
var c=_1c();
if(!_2.isHexDigit(c)){
throw _21(_1f());
}
_2b+=_1f();
c=_1c();
if(!_2.isHexDigit(c)){
throw _21(_1f());
}
_2b+=_1f();
return _1.fromCodePoint(parseInt(_2b,16));
};
function _23(){
var _2c="";
var _2d=4;
while(_2d-->0){
var c_2=_1c();
if(!_2.isHexDigit(c_2)){
throw _21(_1f());
}
_2c+=_1f();
}
return _1.fromCodePoint(parseInt(_2c,16));
};
var _10={start:function(){
if(_9.type==="eof"){
throw _2e();
}
_2f();
},beforePropertyName:function(){
switch(_9.type){
case "identifier":
case "string":
_a=_9.value;
_4="afterPropertyName";
return;
case "punctuator":
pop();
return;
case "eof":
throw _2e();
}
},afterPropertyName:function(){
if(_9.type==="eof"){
throw _2e();
}
_4="beforePropertyValue";
},beforePropertyValue:function(){
if(_9.type==="eof"){
throw _2e();
}
_2f();
},beforeArrayValue:function(){
if(_9.type==="eof"){
throw _2e();
}
if(_9.type==="punctuator"&&_9.value==="]"){
pop();
return;
}
_2f();
},afterPropertyValue:function(){
if(_9.type==="eof"){
throw _2e();
}
switch(_9.value){
case ",":
_4="beforePropertyName";
return;
case "}":
pop();
}
},afterArrayValue:function(){
if(_9.type==="eof"){
throw _2e();
}
switch(_9.value){
case ",":
_4="beforeArrayValue";
return;
case "]":
pop();
}
},end:function(){
}};
function _2f(){
var _30;
switch(_9.type){
case "punctuator":
switch(_9.value){
case "{":
_30={};
break;
case "[":
_30=[];
break;
}
break;
case "null":
case "boolean":
case "numeric":
case "string":
_30=_9.value;
break;
}
if(_b===undefined){
_b=_30;
}else{
var _31=_5[_5.length-1];
if(Array.isArray(_31)){
_31.push(_30);
}else{
_31[_a]=_30;
}
}
if(_30!==null&&typeof _30==="object"){
_5.push(_30);
if(Array.isArray(_30)){
_4="beforeArrayValue";
}else{
_4="beforePropertyName";
}
}else{
var _32=_5[_5.length-1];
if(_32==null){
_4="end";
}else{
if(Array.isArray(_32)){
_4="afterArrayValue";
}else{
_4="afterPropertyValue";
}
}
}
};
function pop(){
_5.pop();
var _33=_5[_5.length-1];
if(_33==null){
_4="end";
}else{
if(Array.isArray(_33)){
_4="afterArrayValue";
}else{
_4="afterPropertyValue";
}
}
};
function _21(c){
if(c===undefined){
return _34("JSON5: invalid end of input at "+_7+":"+_8);
}
return _34("JSON5: invalid character '"+_35(c)+"' at "+_7+":"+_8);
};
function _2e(){
return _34("JSON5: invalid end of input at "+_7+":"+_8);
};
function _24(){
_8-=5;
return _34("JSON5: invalid identifier character at "+_7+":"+_8);
};
function _26(c){
console.warn("JSON5: '"+_35(c)+"' in strings is not valid ECMAScript; consider escaping");
};
function _35(c){
var _36={"'":"\\'","\"":"\\\"","\\":"\\\\","\b":"\\b","\f":"\\f","\n":"\\n","\r":"\\r","\t":"\\t","\v":"\\v","\x00":"\\0"," ":"\\u2028"," ":"\\u2029"};
if(_36[c]){
return _36[c];
}
if(c<" "){
var _37=c.charCodeAt(0).toString(16);
return "\\x"+("00"+_37).substring(_37.length);
}
return c;
};
function _34(_38){
var err=new SyntaxError(_38);
err.lineNumber=_7;
err.columnNumber=_8;
return err;
};
return _c;
});
