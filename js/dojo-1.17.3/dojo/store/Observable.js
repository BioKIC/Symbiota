/*
	Copyright (c) 2004-2016, The JS Foundation All Rights Reserved.
	Available via Academic Free License >= 2.1 OR the modified BSD license.
	see: http://dojotoolkit.org/license for details
*/

//>>built
define("dojo/store/Observable",["../_base/lang","../when","../_base/array"],function(_1,_2,_3){
function _4(_5,_6,id,_7,_8){
var i;
_7=_7==undefined?0:_7;
_8=_8==undefined?_6.length:_8;
for(i=_7;i<_8;++i){
if(_5.getIdentity(_6[i])===id){
return i;
}
}
return -1;
};
var _9=function(_a){
var _b,_c=[],_d=0;
_a=_1.delegate(_a);
_a.notify=function(_e,_f,_10){
_d++;
var _11=_c.slice();
for(var i=0,l=_11.length;i<l;i++){
_11[i](_e,_f,_10);
}
};
var _12=_a.query;
_a.query=function(_13,_14){
_14=_14||{};
var _15=_12.apply(this,arguments);
if(_15&&_15.forEach){
var _16=_1.mixin({},_14);
delete _16.start;
delete _16.count;
var _17=_a.queryEngine&&_a.queryEngine(_13,_16);
var _18=_d;
var _19=[],_1a;
_15.observe=function(_1b,_1c){
if(_19.push(_1b)==1){
_c.push(_1a=function(_1d,_1e,_1f){
var _20=_1f&&_1f.before&&_a.getIdentity(_1f.before);
_2(_15,function(_21){
var _22=_21.length!=_14.count;
var i,l,_1b;
if(++_18!=_d){
throw new Error("Query is out of date, you must observe() the query prior to any data modifications");
}
var _23,_24=-1,_25=-1;
var _26;
if(_1e!==_b){
var _27=[].concat(_21);
if(_17&&!_1d){
_27=_17(_21);
}
for(i=0,l=_21.length;i<l;i++){
var _28=_21[i];
if(_a.getIdentity(_28)==_1e){
if(_27.indexOf(_28)<0){
continue;
}
_23=_28;
_24=i;
if(_17||!_1d){
_21.splice(i,1);
}
break;
}
}
}
if(_17){
if(_1d&&(_17.matches?_17.matches(_1d):_17([_1d]).length)){
var _29=_24>-1?_24:_21.length;
_21.splice(_29,0,_1d);
_25=_3.indexOf(_17(_21),_1d);
_21.splice(_29,1);
if((_14.start&&_25==0)||(!_22&&_25==_21.length)){
_25=-1;
}else{
if(_1f&&_1f.before!==undefined){
_26=_1f.before===null?_21.length:_4(_a,_21,_20);
if(_26!==-1){
_25=_26;
}
}
_21.splice(_25,0,_1d);
}
}
}else{
if(_1d){
if(_1e!==_b){
_25=_24;
}else{
if(!_14.start){
_25=_a.defaultIndex||0;
_21.splice(_25,0,_1d);
}
}
}
}
if((_24>-1||_25>-1)&&(_1c||!_17||(_24!=_25))){
var _2a=_19.slice();
for(i=0;_1b=_2a[i];i++){
_1b(_1d||_23,_24,_25);
}
}
});
});
}
var _2b={};
_2b.remove=_2b.cancel=function(){
var _2c=_3.indexOf(_19,_1b);
if(_2c>-1){
_19.splice(_2c,1);
if(!_19.length){
_c.splice(_3.indexOf(_c,_1a),1);
}
}
};
return _2b;
};
}
return _15;
};
var _2d;
function _2e(_2f,_30){
var _31=_a[_2f];
if(_31){
_a[_2f]=function(_32,_33){
var _34;
if(_2f==="put"){
_34=_a.getIdentity(_32);
}
if(_2d){
return _31.apply(this,arguments);
}
_2d=true;
try{
var _35=_31.apply(this,arguments);
_2(_35,function(_36){
_30((typeof _36=="object"&&_36)||_32,_34,_33);
});
return _35;
}
finally{
_2d=false;
}
};
}
};
_2e("put",function(_37,_38,_39){
_a.notify(_37,_38,_39);
});
_2e("add",function(_3a,_3b,_3c){
_a.notify(_3a,_3b,_3c);
});
_2e("remove",function(id){
_a.notify(undefined,id);
});
return _a;
};
_1.setObject("dojo.store.Observable",_9);
return _9;
});
