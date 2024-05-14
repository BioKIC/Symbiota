//>>built
define("dijit/layout/BorderContainer",["dojo/_base/array","dojo/cookie","dojo/_base/declare","dojo/dom-class","dojo/dom-construct","dojo/dom-geometry","dojo/dom-style","dojo/keys","dojo/_base/lang","dojo/on","dojo/touch","../_WidgetBase","../_Widget","../_TemplatedMixin","./LayoutContainer","./utils"],function(_1,_2,_3,_4,_5,_6,_7,_8,_9,on,_a,_b,_c,_d,_e,_f){
var _10=_3("dijit.layout._Splitter",[_c,_d],{live:true,templateString:"<div class=\"dijitSplitter\" data-dojo-attach-event=\"onkeydown:_onKeyDown,press:_startDrag,onmouseenter:_onMouse,onmouseleave:_onMouse\" tabIndex=\"0\" role=\"separator\"><div class=\"dijitSplitterThumb\"></div></div>",constructor:function(){
this._handlers=[];
},postMixInProperties:function(){
this.inherited(arguments);
this.horizontal=/top|bottom/.test(this.region);
this._factor=/top|left/.test(this.region)?1:-1;
this._cookieName=this.container.id+"_"+this.region;
},buildRendering:function(){
this.inherited(arguments);
_4.add(this.domNode,"dijitSplitter"+(this.horizontal?"H":"V"));
if(this.container.persist){
var _11=this._getPersistentSplit();
if(_11){
this.child.domNode.style[this.horizontal?"height":"width"]=_11;
}
}
},_computeMaxSize:function(){
var dim=this.horizontal?"h":"w",_12=_6.getMarginBox(this.child.domNode)[dim],_13=_1.filter(this.container.getChildren(),function(_14){
return _14.region=="center";
})[0];
var _15=_6.getContentBox(_13.domNode)[dim]-10;
return Math.min(this.child.maxSize,_12+_15);
},_startDrag:function(e){
if(!this.cover){
this.cover=_5.place("<div class=dijitSplitterCover></div>",this.child.domNode,"after");
}
_4.add(this.cover,"dijitSplitterCoverActive");
if(this.fake){
_5.destroy(this.fake);
}
if(!(this._resize=this.live)){
(this.fake=this.domNode.cloneNode(true)).removeAttribute("id");
_4.add(this.domNode,"dijitSplitterShadow");
_5.place(this.fake,this.domNode,"after");
}
_4.add(this.domNode,"dijitSplitterActive dijitSplitter"+(this.horizontal?"H":"V")+"Active");
if(this.fake){
_4.remove(this.fake,"dijitSplitterHover dijitSplitter"+(this.horizontal?"H":"V")+"Hover");
}
var _16=this._factor,_17=this.horizontal,_18=_17?"pageY":"pageX",_19=e[_18],_1a=this.domNode.style,dim=_17?"h":"w",_1b=_7.getComputedStyle(this.child.domNode),_1c=_6.getMarginBox(this.child.domNode,_1b)[dim],max=this._computeMaxSize(),min=Math.max(this.child.minSize,_6.getPadBorderExtents(this.child.domNode,_1b)[dim]+10),_1d=this.region,_1e=_1d=="top"||_1d=="bottom"?"top":"left",_1f=parseInt(_1a[_1e],10),_20=this._resize,_21=_9.hitch(this.container,"_layoutChildren",this.child.id),de=this.ownerDocument;
this._handlers=this._handlers.concat([on(de,_a.move,this._drag=function(e,_22){
var _23=e[_18]-_19,_24=_16*_23+_1c,_25=Math.max(Math.min(_24,max),min);
if(_20||_22){
_21(_25);
}
_1a[_1e]=_23+_1f+_16*(_25-_24)+"px";
}),on(de,"dragstart",function(e){
e.stopPropagation();
e.preventDefault();
}),on(this.ownerDocumentBody,"selectstart",function(e){
e.stopPropagation();
e.preventDefault();
}),on(de,_a.release,_9.hitch(this,"_stopDrag"))]);
e.stopPropagation();
e.preventDefault();
},_onMouse:function(e){
var o=(e.type=="mouseover"||e.type=="mouseenter");
_4.toggle(this.domNode,"dijitSplitterHover",o);
_4.toggle(this.domNode,"dijitSplitter"+(this.horizontal?"H":"V")+"Hover",o);
},_getPersistentSplit:function(){
return _2(this._cookieName);
},_setPersistentSplit:function(_26){
_2(this._cookieName,_26,{expires:365});
},_stopDrag:function(e){
try{
if(this.cover){
_4.remove(this.cover,"dijitSplitterCoverActive");
}
if(this.fake){
_5.destroy(this.fake);
}
_4.remove(this.domNode,"dijitSplitterActive dijitSplitter"+(this.horizontal?"H":"V")+"Active dijitSplitterShadow");
this._drag(e);
this._drag(e,true);
}
finally{
this._cleanupHandlers();
delete this._drag;
}
if(this.container.persist){
this._setPersistentSplit(this.child.domNode.style[this.horizontal?"height":"width"]);
}
},_cleanupHandlers:function(){
var h;
while(h=this._handlers.pop()){
h.remove();
}
},_onKeyDown:function(e){
this._resize=true;
var _27=this.horizontal;
var _28=1;
switch(e.keyCode){
case _27?_8.UP_ARROW:_8.LEFT_ARROW:
_28*=-1;
case _27?_8.DOWN_ARROW:_8.RIGHT_ARROW:
break;
default:
return;
}
var _29=_6.getMarginSize(this.child.domNode)[_27?"h":"w"]+this._factor*_28;
this.container._layoutChildren(this.child.id,Math.max(Math.min(_29,this._computeMaxSize()),this.child.minSize));
e.stopPropagation();
e.preventDefault();
},destroy:function(){
this._cleanupHandlers();
delete this.child;
delete this.container;
delete this.cover;
delete this.fake;
this.inherited(arguments);
}});
var _2a=_3("dijit.layout._Gutter",[_c,_d],{templateString:"<div class=\"dijitGutter\" role=\"presentation\"></div>",postMixInProperties:function(){
this.inherited(arguments);
this.horizontal=/top|bottom/.test(this.region);
},buildRendering:function(){
this.inherited(arguments);
_4.add(this.domNode,"dijitGutter"+(this.horizontal?"H":"V"));
}});
var _2b=_3("dijit.layout.BorderContainer",_e,{gutters:true,liveSplitters:true,persist:false,baseClass:"dijitBorderContainer",_splitterClass:_10,postMixInProperties:function(){
if(!this.gutters){
this.baseClass+="NoGutter";
}
this.inherited(arguments);
},_setupChild:function(_2c){
this.inherited(arguments);
var _2d=_2c.region,ltr=_2c.isLeftToRight();
if(_2d=="leading"){
_2d=ltr?"left":"right";
}
if(_2d=="trailing"){
_2d=ltr?"right":"left";
}
if(_2d){
if(_2d!="center"&&(_2c.splitter||this.gutters)&&!_2c._splitterWidget){
var _2e=_2c.splitter?this._splitterClass:_2a;
if(_9.isString(_2e)){
_2e=_9.getObject(_2e);
}
var _2f=new _2e({id:_2c.id+"_splitter",container:this,child:_2c,region:_2d,live:this.liveSplitters});
_2f.isSplitter=true;
_2c._splitterWidget=_2f;
var _30=_2d=="bottom"||_2d==(this.isLeftToRight()?"right":"left");
_5.place(_2f.domNode,_2c.domNode,_30?"before":"after");
_2f.startup();
}
}
},layout:function(){
this._layoutChildren();
},removeChild:function(_31){
var _32=_31._splitterWidget;
if(_32){
_32.destroy();
delete _31._splitterWidget;
}
this.inherited(arguments);
},getChildren:function(){
return _1.filter(this.inherited(arguments),function(_33){
return !_33.isSplitter;
});
},getSplitter:function(_34){
return _1.filter(this.getChildren(),function(_35){
return _35.region==_34;
})[0]._splitterWidget;
},resize:function(_36,_37){
if(!this.cs||!this.pe){
var _38=this.domNode;
this.cs=_7.getComputedStyle(_38);
this.pe=_6.getPadExtents(_38,this.cs);
this.pe.r=_7.toPixelValue(_38,this.cs.paddingRight);
this.pe.b=_7.toPixelValue(_38,this.cs.paddingBottom);
_7.set(_38,"padding","0px");
}
this.inherited(arguments);
},_layoutChildren:function(_39,_3a){
if(!this._borderBox||!this._borderBox.h){
return;
}
var _3b=[];
_1.forEach(this._getOrderedChildren(),function(_3c){
_3b.push(_3c);
if(_3c._splitterWidget){
_3b.push(_3c._splitterWidget);
}
});
var dim={l:this.pe.l,t:this.pe.t,w:this._borderBox.w-this.pe.w,h:this._borderBox.h-this.pe.h};
_f.layoutChildren(this.domNode,dim,_3b,_39,_3a);
},destroyRecursive:function(){
_1.forEach(this.getChildren(),function(_3d){
var _3e=_3d._splitterWidget;
if(_3e){
_3e.destroy();
}
delete _3d._splitterWidget;
});
this.inherited(arguments);
}});
_2b.ChildWidgetProperties={splitter:false,minSize:0,maxSize:Infinity};
_9.mixin(_2b.ChildWidgetProperties,_e.ChildWidgetProperties);
_9.extend(_b,_2b.ChildWidgetProperties);
_2b._Splitter=_10;
_2b._Gutter=_2a;
return _2b;
});
