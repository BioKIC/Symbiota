/*
	Copyright (c) 2004-2016, The JS Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/

//>>built
define("dojo/store/Memory",["../_base/declare","./util/QueryResults","./util/SimpleQueryEngine"],function(_1,_2,_3){
var _4=null;
return _1("dojo.store.Memory",_4,{constructor:function(_5){
for(var i in _5){
this[i]=_5[i];
}
this.setData(this.data||[]);
},data:null,idProperty:"id",index:null,queryEngine:_3,get:function(id){
return this.data[this.index[id]];
},getIdentity:function(_6){
return _6[this.idProperty];
},put:function(_7,_8){
var _9=this.data;
var _a=this.index;
var _b=this.idProperty;
var id=_7[_b]=(_8&&"id" in _8)?_8.id:_b in _7?_7[_b]:Math.random();
var _c=_9.length;
var _d;
var _e;
var _f=id in _a?"update":"add";
if(_f==="update"){
if(_8&&_8.overwrite===false){
throw new Error("Object already exists");
}else{
_e=_a[id];
_c=_e;
}
}
if(_8&&"before" in _8){
if(_8.before==null){
_d=_9.length;
if(_f==="update"){
--_d;
}
}else{
_d=_a[this.getIdentity(_8.before)];
if(_e<_d){
--_d;
}
}
}else{
_d=_c;
}
if(_d===_e){
_9[_d]=_7;
}else{
if(_e!==undefined){
_9.splice(_e,1);
}
_9.splice(_d,0,_7);
this._rebuildIndex(_e===undefined?_d:Math.min(_e,_d));
}
return id;
},add:function(_10,_11){
(_11=_11||{}).overwrite=false;
return this.put(_10,_11);
},remove:function(id){
var _12=this.index;
var _13=this.data;
if(id in _12){
_13.splice(_12[id],1);
this.index={};
this._rebuildIndex();
return true;
}
},query:function(_14,_15){
return _2(this.queryEngine(_14,_15)(this.data));
},setData:function(_16){
if(_16.items){
this.idProperty=_16.identifier||this.idProperty;
_16=this.data=_16.items;
}else{
this.data=_16;
}
this.index={};
this._rebuildIndex();
},_rebuildIndex:function(_17){
var _18=this.data;
var _19=_18.length;
var i;
_17=_17||0;
for(i=_17;i<_19;i++){
this.index[_18[i][this.idProperty]]=i;
}
}});
});
