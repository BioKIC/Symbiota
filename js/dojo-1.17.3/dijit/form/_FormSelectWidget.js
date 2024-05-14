//>>built
define("dijit/form/_FormSelectWidget",["dojo/_base/array","dojo/_base/Deferred","dojo/aspect","dojo/data/util/sorter","dojo/_base/declare","dojo/dom","dojo/dom-class","dojo/_base/kernel","dojo/_base/lang","dojo/query","dojo/when","dojo/store/util/QueryResults","./_FormValueWidget"],function(_1,_2,_3,_4,_5,_6,_7,_8,_9,_a,_b,_c,_d){
var _e=_5("dijit.form._FormSelectWidget",_d,{multiple:false,options:null,store:null,_setStoreAttr:function(_f){
if(this._created){
this._deprecatedSetStore(_f);
}
},query:null,_setQueryAttr:function(_10){
if(this._created){
this._deprecatedSetStore(this.store,this.selectedValue,{query:_10});
}
},queryOptions:null,_setQueryOptionsAttr:function(_11){
if(this._created){
this._deprecatedSetStore(this.store,this.selectedValue,{queryOptions:_11});
}
},labelAttr:"",onFetch:null,sortByLabel:true,loadChildrenOnOpen:false,onLoadDeferred:null,getOptions:function(_12){
var _13=this.options||[];
if(_12==null){
return _13;
}
if(_9.isArrayLike(_12)){
return _1.map(_12,function(_14){
return this.getOptions(_14);
},this);
}
if(_9.isString(_12)){
_12={value:_12};
}
if(_9.isObject(_12)){
if(!_1.some(_13,function(_15,idx){
for(var a in _12){
if(!(a in _15)||_15[a]!=_12[a]){
return false;
}
}
_12=idx;
return true;
})){
_12=-1;
}
}
if(_12>=0&&_12<_13.length){
return _13[_12];
}
return null;
},addOption:function(_16){
_1.forEach(_9.isArrayLike(_16)?_16:[_16],function(i){
if(i&&_9.isObject(i)){
this.options.push(i);
}
},this);
this._loadChildren();
},removeOption:function(_17){
var _18=this.getOptions(_9.isArrayLike(_17)?_17:[_17]);
_1.forEach(_18,function(_19){
if(_19){
this.options=_1.filter(this.options,function(_1a){
return (_1a.value!==_19.value||_1a.label!==_19.label);
});
this._removeOptionItem(_19);
}
},this);
this._loadChildren();
},updateOption:function(_1b){
_1.forEach(_9.isArrayLike(_1b)?_1b:[_1b],function(i){
var _1c=this.getOptions({value:i.value}),k;
if(_1c){
for(k in i){
_1c[k]=i[k];
}
}
},this);
this._loadChildren();
},setStore:function(_1d,_1e,_1f){
_8.deprecated(this.declaredClass+"::setStore(store, selectedValue, fetchArgs) is deprecated. Use set('query', fetchArgs.query), set('queryOptions', fetchArgs.queryOptions), set('store', store), or set('value', selectedValue) instead.","","2.0");
this._deprecatedSetStore(_1d,_1e,_1f);
},_deprecatedSetStore:function(_20,_21,_22){
var _23=this.store;
_22=_22||{};
if(_23!==_20){
var h;
while((h=this._notifyConnections.pop())){
h.remove();
}
if(!_20.get){
_9.mixin(_20,{_oldAPI:true,get:function(id){
var _24=new _2();
this.fetchItemByIdentity({identity:id,onItem:function(_25){
_24.resolve(_25);
},onError:function(_26){
_24.reject(_26);
}});
return _24.promise;
},query:function(_27,_28){
var _29=new _2(function(){
if(_2a.abort){
_2a.abort();
}
});
_29.total=new _2();
var _2a=this.fetch(_9.mixin({query:_27,onBegin:function(_2b){
_29.total.resolve(_2b);
},onComplete:function(_2c){
_29.resolve(_2c);
},onError:function(_2d){
_29.reject(_2d);
}},_28));
return new _c(_29);
}});
if(_20.getFeatures()["dojo.data.api.Notification"]){
this._notifyConnections=[_3.after(_20,"onNew",_9.hitch(this,"_onNewItem"),true),_3.after(_20,"onDelete",_9.hitch(this,"_onDeleteItem"),true),_3.after(_20,"onSet",_9.hitch(this,"_onSetItem"),true)];
}
}
this._set("store",_20);
}
if(this.options&&this.options.length){
this.removeOption(this.options);
}
if(this._queryRes&&this._queryRes.close){
this._queryRes.close();
}
if(this._observeHandle&&this._observeHandle.remove){
this._observeHandle.remove();
this._observeHandle=null;
}
if(_22.query){
this._set("query",_22.query);
}
if(_22.queryOptions){
this._set("queryOptions",_22.queryOptions);
}
if(_20&&_20.query){
this._loadingStore=true;
this.onLoadDeferred=new _2();
this._queryRes=_20.query(this.query,this.queryOptions);
_b(this._queryRes,_9.hitch(this,function(_2e){
if(this.sortByLabel&&!_22.sort&&_2e.length){
if(_20.getValue){
_2e.sort(_4.createSortFunction([{attribute:_20.getLabelAttributes(_2e[0])[0]}],_20));
}else{
var _2f=this.labelAttr;
_2e.sort(function(a,b){
return a[_2f]>b[_2f]?1:b[_2f]>a[_2f]?-1:0;
});
}
}
if(_22.onFetch){
_2e=_22.onFetch.call(this,_2e,_22);
}
_1.forEach(_2e,function(i){
this._addOptionForItem(i);
},this);
if(this._queryRes.observe){
this._observeHandle=this._queryRes.observe(_9.hitch(this,function(_30,_31,_32){
if(_31==_32){
this._onSetItem(_30);
}else{
if(_31!=-1){
this._onDeleteItem(_30);
}
if(_32!=-1){
this._onNewItem(_30);
}
}
}),true);
}
this._loadingStore=false;
this.set("value","_pendingValue" in this?this._pendingValue:_21);
delete this._pendingValue;
if(!this.loadChildrenOnOpen){
this._loadChildren();
}else{
this._pseudoLoadChildren(_2e);
}
this.onLoadDeferred.resolve(true);
this.onSetStore();
}),_9.hitch(this,function(err){
console.error("dijit.form.Select: "+err.toString());
this.onLoadDeferred.reject(err);
}));
}
return _23;
},_setValueAttr:function(_33,_34){
if(!this._onChangeActive){
_34=null;
}
if(this._loadingStore){
this._pendingValue=_33;
return;
}
if(_33==null){
return;
}
if(_9.isArrayLike(_33)){
_33=_1.map(_33,function(_35){
return _9.isObject(_35)?_35:{value:_35};
});
}else{
if(_9.isObject(_33)){
_33=[_33];
}else{
_33=[{value:_33}];
}
}
_33=_1.filter(this.getOptions(_33),function(i){
return i&&i.value;
});
var _36=this.getOptions()||[];
if(!this.multiple&&(!_33[0]||!_33[0].value)&&!!_36.length){
_33[0]=_36[0];
}
_1.forEach(_36,function(opt){
opt.selected=_1.some(_33,function(v){
return v.value===opt.value;
});
});
var val=_1.map(_33,function(opt){
return opt.value;
});
if(typeof val=="undefined"||typeof val[0]=="undefined"){
return;
}
var _37=_1.map(_33,function(opt){
return opt.label;
});
this._setDisplay(this.multiple?_37:_37[0]);
this.inherited(arguments,[this.multiple?val:val[0],_34]);
this._updateSelection();
},_getDisplayedValueAttr:function(){
var ret=_1.map([].concat(this.get("selectedOptions")),function(v){
if(v&&"label" in v){
return v.label;
}else{
if(v){
return v.value;
}
}
return null;
},this);
return this.multiple?ret:ret[0];
},_setDisplayedValueAttr:function(_38){
this.set("value",this.getOptions(typeof _38=="string"?{label:_38}:_38));
},_loadChildren:function(){
if(this._loadingStore){
return;
}
_1.forEach(this._getChildren(),function(_39){
_39.destroyRecursive();
});
_1.forEach(this.options,this._addOptionItem,this);
this._updateSelection();
},_updateSelection:function(){
this.focusedChild=null;
this._set("value",this._getValueFromOpts());
var val=[].concat(this.value);
if(val&&val[0]){
var _3a=this;
_1.forEach(this._getChildren(),function(_3b){
var _3c=_1.some(val,function(v){
return _3b.option&&(v===_3b.option.value);
});
if(_3c&&!_3a.multiple){
_3a.focusedChild=_3b;
}
_7.toggle(_3b.domNode,this.baseClass.replace(/\s+|$/g,"SelectedOption "),_3c);
_3b.domNode.setAttribute("aria-selected",_3c?"true":"false");
},this);
}
},_getValueFromOpts:function(){
var _3d=this.getOptions()||[];
if(!this.multiple&&_3d.length){
var opt=_1.filter(_3d,function(i){
return i.selected;
})[0];
if(opt&&opt.value){
return opt.value;
}else{
_3d[0].selected=true;
return _3d[0].value;
}
}else{
if(this.multiple){
return _1.map(_1.filter(_3d,function(i){
return i.selected;
}),function(i){
return i.value;
})||[];
}
}
return "";
},_onNewItem:function(_3e,_3f){
if(!_3f||!_3f.parent){
this._addOptionForItem(_3e);
}
},_onDeleteItem:function(_40){
var _41=this.store;
this.removeOption({value:_41.getIdentity(_40)});
},_onSetItem:function(_42){
this.updateOption(this._getOptionObjForItem(_42));
},_getOptionObjForItem:function(_43){
var _44=this.store,_45=(this.labelAttr&&this.labelAttr in _43)?_43[this.labelAttr]:_44.getLabel(_43),_46=(_45?_44.getIdentity(_43):null);
return {value:_46,label:_45,item:_43};
},_addOptionForItem:function(_47){
var _48=this.store;
if(_48.isItemLoaded&&!_48.isItemLoaded(_47)){
_48.loadItem({item:_47,onItem:function(i){
this._addOptionForItem(i);
},scope:this});
return;
}
var _49=this._getOptionObjForItem(_47);
this.addOption(_49);
},constructor:function(_4a){
this._oValue=(_4a||{}).value||null;
this._notifyConnections=[];
},buildRendering:function(){
this.inherited(arguments);
_6.setSelectable(this.focusNode,false);
},_fillContent:function(){
if(!this.options){
this.options=this.srcNodeRef?_a("> *",this.srcNodeRef).map(function(_4b){
if(_4b.getAttribute("type")==="separator"){
return {value:"",label:"",selected:false,disabled:false};
}
return {value:(_4b.getAttribute("data-"+_8._scopeName+"-value")||_4b.getAttribute("value")),label:String(_4b.innerHTML),selected:_4b.getAttribute("selected")||false,disabled:_4b.getAttribute("disabled")||false};
},this):[];
}
if(!this.value){
this._set("value",this._getValueFromOpts());
}else{
if(this.multiple&&typeof this.value=="string"){
this._set("value",this.value.split(","));
}
}
},postCreate:function(){
this.inherited(arguments);
_3.after(this,"onChange",_9.hitch(this,"_updateSelection"));
var _4c=this.store;
if(_4c&&(_4c.getIdentity||_4c.getFeatures()["dojo.data.api.Identity"])){
this.store=null;
this._deprecatedSetStore(_4c,this._oValue,{query:this.query,queryOptions:this.queryOptions});
}
this._storeInitialized=true;
},startup:function(){
this._loadChildren();
this.inherited(arguments);
},destroy:function(){
var h;
while((h=this._notifyConnections.pop())){
h.remove();
}
if(this._queryRes&&this._queryRes.close){
this._queryRes.close();
}
if(this._observeHandle&&this._observeHandle.remove){
this._observeHandle.remove();
this._observeHandle=null;
}
this.inherited(arguments);
},_addOptionItem:function(){
},_removeOptionItem:function(){
},_setDisplay:function(){
},_getChildren:function(){
return [];
},_getSelectedOptionsAttr:function(){
return this.getOptions({selected:true});
},_pseudoLoadChildren:function(){
},onSetStore:function(){
}});
return _e;
});
