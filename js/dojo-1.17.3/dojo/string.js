/*
	Copyright (c) 2004-2016, The JS Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/

//>>built
define("dojo/string",["./_base/kernel","./_base/lang"],function(_1,_2){
var _3=/[&<>'"\/]/g;
var _4={"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#x27;","/":"&#x2F;"};
var _5={};
_2.setObject("dojo.string",_5);
_5.escape=function(_6){
if(!_6){
return "";
}
return _6.replace(_3,function(c){
return _4[c];
});
};
_5.codePointAt=String.prototype.codePointAt?function(_7,_8){
return String.prototype.codePointAt.call(_7,_8);
}:function(_9,_a){
if(_9==null){
throw new TypeError("codePointAt called on null or undefined");
}
var _b;
var _c;
var _d;
var _e;
_9=String(_9);
_b=_9.length;
_e=_a?Number(_a):0;
if(_e!=_e){
_e=0;
}
if(_e<0||_e>=_b){
return undefined;
}
_c=_9.charCodeAt(_e);
if(_c>=55296&&_c<=56319&&_b>_e+1){
_d=_9.charCodeAt(_e+1);
if(_d>=56320&&_d<=57343){
return (_c-55296)*1024+_d-56320+65536;
}
}
return _c;
};
_5.fromCodePoint=String.fromCodePoint||function(){
var _f=[];
var _10=0;
var _11="";
var _12;
var _13;
for(_13=0,len=arguments.length;_13!==len;++_13){
_12=+arguments[_13];
if(!(_12<1114111&&(_12>>>0)===_12)){
throw RangeError("Invalid code point: "+_12);
}
if(_12<=65535){
_10=_f.push(_12);
}else{
_12-=65536;
_10=_f.push((_12>>10)+55296,(_12%1024)+56320);
}
if(_10>=16383){
_11+=String.fromCharCode.apply(null,_f);
_f.length=0;
}
}
return _11+String.fromCharCode.apply(null,_f);
};
_5.rep=function(str,num){
if(num<=0||!str){
return "";
}
var buf=[];
for(;;){
if(num&1){
buf.push(str);
}
if(!(num>>=1)){
break;
}
str+=str;
}
return buf.join("");
};
_5.pad=function(_14,_15,ch,end){
if(!ch){
ch="0";
}
var out=String(_14),pad=_5.rep(ch,Math.ceil((_15-out.length)/ch.length));
return end?out+pad:pad+out;
};
_5.substitute=function(_16,map,_17,_18){
_18=_18||_1.global;
_17=_17?_2.hitch(_18,_17):function(v){
return v;
};
return _16.replace(/\$\{([^\s\:\}]*)(?:\:([^\s\:\}]+))?\}/g,function(_19,key,_1a){
if(key==""){
return "$";
}
var _1b=_2.getObject(key,false,map);
if(_1a){
_1b=_2.getObject(_1a,false,_18).call(_18,_1b,key);
}
var _1c=_17(_1b,key);
if(typeof _1c==="undefined"){
throw new Error("string.substitute could not find key \""+key+"\" in template");
}
return _1c.toString();
});
};
_5.trim=String.prototype.trim?_2.trim:function(str){
str=str.replace(/^\s+/,"");
for(var i=str.length-1;i>=0;i--){
if(/\S/.test(str.charAt(i))){
str=str.substring(0,i+1);
break;
}
}
return str;
};
return _5;
});
