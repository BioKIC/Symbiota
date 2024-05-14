//>>built
require({cache:{"url:dijit/form/templates/DropDownBox.html":"<div class=\"dijit dijitReset dijitInline dijitLeft\"\n\tid=\"widget_${id}\"\n\trole=\"combobox\"\n\taria-haspopup=\"true\"\n\tdata-dojo-attach-point=\"_popupStateNode\"\n\t><div class='dijitReset dijitRight dijitButtonNode dijitArrowButton dijitDownArrowButton dijitArrowButtonContainer'\n\t\tdata-dojo-attach-point=\"_buttonNode\" role=\"presentation\"\n\t\t><input class=\"dijitReset dijitInputField dijitArrowButtonInner\" value=\"&#9660; \" type=\"text\" tabIndex=\"-1\" readonly=\"readonly\" role=\"button presentation\" aria-hidden=\"true\"\n\t\t\t${_buttonInputDisabled}\n\t/></div\n\t><div class='dijitReset dijitValidationContainer'\n\t\t><input class=\"dijitReset dijitInputField dijitValidationIcon dijitValidationInner\" value=\"&#935; \" type=\"text\" tabIndex=\"-1\" readonly=\"readonly\" role=\"presentation\"\n\t/></div\n\t><div class=\"dijitReset dijitInputField dijitInputContainer\"\n\t\t><input class='dijitReset dijitInputInner' ${!nameAttrSetting} type=\"${type}\" autocomplete=\"off\"\n\t\t\tdata-dojo-attach-point=\"textbox,focusNode\" role=\"textbox\"\n\t/></div\n></div>\n"}});
define("dijit/form/_DateTimeTextBox",["dojo/date","dojo/date/locale","dojo/date/stamp","dojo/_base/declare","dojo/_base/lang","./RangeBoundTextBox","../_HasDropDown","dojo/text!./templates/DropDownBox.html"],function(_1,_2,_3,_4,_5,_6,_7,_8){
new Date("X");
var _9=_4("dijit.form._DateTimeTextBox",[_6,_7],{templateString:_8,hasDownArrow:true,cssStateNodes:{"_buttonNode":"dijitDownArrowButton"},_unboundedConstraints:{},pattern:_2.regexp,datePackage:"",postMixInProperties:function(){
this.inherited(arguments);
this._set("type","text");
},compare:function(_a,_b){
var _c=this._isInvalidDate(_a);
var _d=this._isInvalidDate(_b);
if(_c||_d){
return (_c&&_d)?0:(!_c?1:-1);
}
var _e=this.format(_a,this._unboundedConstraints),_f=this.format(_b,this._unboundedConstraints),_10=this.parse(_e,this._unboundedConstraints),_11=this.parse(_f,this._unboundedConstraints);
return _e==_f?0:_1.compare(_10,_11,this._selector);
},autoWidth:true,format:function(_12,_13){
if(!_12){
return "";
}
return this.dateLocaleModule.format(_12,_13);
},"parse":function(_14,_15){
return this.dateLocaleModule.parse(_14,_15)||(this._isEmpty(_14)?null:undefined);
},serialize:function(val,_16){
if(val.toGregorian){
val=val.toGregorian();
}
return _3.toISOString(val,_16);
},dropDownDefaultValue:new Date(),value:new Date(""),_blankValue:null,popupClass:"",_selector:"",constructor:function(_17){
_17=_17||{};
this.dateModule=_17.datePackage?_5.getObject(_17.datePackage,false):_1;
this.dateClassObj=this.dateModule.Date||Date;
if(!(this.dateClassObj instanceof Date)){
this.value=new this.dateClassObj(this.value);
}
this.dateLocaleModule=_17.datePackage?_5.getObject(_17.datePackage+".locale",false):_2;
this._set("pattern",this.dateLocaleModule.regexp);
this._invalidDate=this.constructor.prototype.value.toString();
},buildRendering:function(){
this.inherited(arguments);
if(!this.hasDownArrow){
this._buttonNode.style.display="none";
}
if(!this.hasDownArrow){
this._buttonNode=this.domNode;
this.baseClass+=" dijitComboBoxOpenOnClick";
}
},_setConstraintsAttr:function(_18){
_18.selector=this._selector;
_18.fullYear=true;
var _19=_3.fromISOString;
if(typeof _18.min=="string"){
_18.min=_19(_18.min);
if(!(this.dateClassObj instanceof Date)){
_18.min=new this.dateClassObj(_18.min);
}
}
if(typeof _18.max=="string"){
_18.max=_19(_18.max);
if(!(this.dateClassObj instanceof Date)){
_18.max=new this.dateClassObj(_18.max);
}
}
this.inherited(arguments);
this._unboundedConstraints=_5.mixin({},this.constraints,{min:null,max:null});
},_isDefinitelyOutOfRange:function(){
var _1a=this.inherited(arguments);
var _1b=false;
var _1c;
var _1d;
var _1e;
var _1f;
var _20;
var _21;
if(_1a&&(this.constraints.min||this.constraints.max)){
var _22=new RegExp(this._lastRegExp);
_1c=_22.exec(this._lastInputEventValue);
if(_1c!=null){
_1d=_1c[3];
if(this.constraints.min){
_21=this.constraints.min instanceof Date?this.constraints.min:new Date(String(this.constraints.min));
minYear=_21.getFullYear();
_1e=parseInt((_1d+"9999").substr(0,4),10);
_1b=_1e<minYear;
}
if(!_1b&&this.constraints.max){
_20=this.constraints.max instanceof Date?this.constraints.max:new Date(String(this.constraints.max));
maxYear=_20.getFullYear();
_1f=parseInt((_1d+"0000").substr(0,4),10);
_1b=_1f>maxYear;
}
_1a=_1b;
}
}
return _1a;
},_isInvalidDate:function(_23){
return !_23||isNaN(_23)||typeof _23!="object"||_23.toString()==this._invalidDate;
},_setValueAttr:function(_24,_25,_26){
if(_24!==undefined){
if(typeof _24=="string"){
_24=_3.fromISOString(_24);
}
if(this._isInvalidDate(_24)){
_24=null;
}
if(_24 instanceof Date&&!(this.dateClassObj instanceof Date)){
_24=new this.dateClassObj(_24);
}
}
this.inherited(arguments,[_24,_25,_26]);
if(this.value instanceof Date){
this.filterString="";
}
if(_25!==false&&this.dropDown){
this.dropDown.set("value",_24,false);
}
},_set:function(_27,_28){
if(_27=="value"){
if(_28 instanceof Date&&!(this.dateClassObj instanceof Date)){
_28=new this.dateClassObj(_28);
}
var _29=this._get("value");
if(_29 instanceof this.dateClassObj&&this.compare(_28,_29)==0){
return;
}
}
this.inherited(arguments);
},_setDropDownDefaultValueAttr:function(val){
if(this._isInvalidDate(val)){
val=new this.dateClassObj();
}
this._set("dropDownDefaultValue",val);
},openDropDown:function(_2a){
if(this.dropDown){
this.dropDown.destroy();
}
var _2b=_5.isString(this.popupClass)?_5.getObject(this.popupClass,false):this.popupClass,_2c=this,_2d=this.get("value");
this.dropDown=new _2b({onChange:function(_2e){
_2c.set("value",_2e,true);
},id:this.id+"_popup",dir:_2c.dir,lang:_2c.lang,value:_2d,textDir:_2c.textDir,currentFocus:!this._isInvalidDate(_2d)?_2d:this.dropDownDefaultValue,constraints:_2c.constraints,filterString:_2c.filterString,datePackage:_2c.datePackage,isDisabledDate:function(_2f){
return !_2c.rangeCheck(_2f,_2c.constraints);
}});
this.inherited(arguments);
},_getDisplayedValueAttr:function(){
return this.textbox.value;
},_setDisplayedValueAttr:function(_30,_31){
this._setValueAttr(this.parse(_30,this.constraints),_31,_30);
}});
return _9;
});
