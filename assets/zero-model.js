/* Shared, dependency-free document model. Array order is back-to-front. */
(function(root){
'use strict';
const clone=v=>JSON.parse(JSON.stringify(v));
const widths={desktop:1000,mobile:375};
const names={text:'Текст',image:'Изображение',button:'Кнопка',shape:'Фигура'};
const num=(v,d,min=-10000,max=10000)=>Number.isFinite(Number(v))?Math.min(max,Math.max(min,Number(v))):d;
const geometry=(g={},fallback={})=>({x:num(g.x,fallback.x??40),y:num(g.y,fallback.y??40),width:num(g.width,fallback.width??240,8,5000),height:num(g.height,fallback.height??80,8,5000),fontSize:num(g.fontSize,fallback.fontSize??24,6,300)});
const uid=()=>globalThis.crypto?.randomUUID?.()||('z'+Date.now().toString(36)+Math.random().toString(36).slice(2));
function normalize(input={}){
 input=input&&typeof input==='object'?input:{};
 const data={version:1,desktop:{height:num(input.desktop?.height,600,100,5000)},mobile:{height:num(input.mobile?.height,700,100,5000)},background:input.background||'#ffffff',layers:[]};
 const ids=new Set();
 for(const raw of (Array.isArray(input.layers)?input.layers:[])){
  if(!raw||!names[raw.type])continue;
  const layer={id:raw.id?String(raw.id):uid(),type:raw.type,name:raw.name||names[raw.type],text:raw.text||'',src:raw.src||'',href:raw.href||'',color:raw.color||'#18202c',fill:raw.fill||'transparent',radius:num(raw.radius,0,0,1000),opacity:num(raw.opacity,1,0,1),fontFamily:['Arial','Georgia','Verdana','Courier New'].includes(raw.fontFamily)?raw.fontFamily:'Arial',fontWeight:raw.fontWeight==='700'?'700':'400',align:['left','center','right'].includes(raw.align)?raw.align:'left',hidden:!!raw.hidden,locked:!!raw.locked,desktop:geometry(raw.desktop||{}),mobile:geometry(raw.mobile||{},{width:280,fontSize:20})};
  if(ids.has(layer.id))layer.id=uid();ids.add(layer.id);data.layers.push(layer);
 }
 return data;
}
class Model{
 constructor(input){this.data=normalize(input);this.id=this.data.layers.at(-1)?.id||null;this.past=[];this.future=[];}
 get selected(){return this.data.layers.find(l=>l.id===this.id);}
 export(){return clone(this.data);}
 select(id){this.id=id;}
 checkpoint(){this.past.push({data:this.export(),id:this.id});if(this.past.length>100)this.past.shift();this.future=[];}
 undo(){if(!this.past.length)return;this.future.push({data:this.export(),id:this.id});const s=this.past.pop();this.data=s.data;this.id=s.id;}
 redo(){if(!this.future.length)return;this.past.push({data:this.export(),id:this.id});const s=this.future.pop();this.data=s.data;this.id=s.id;}
 add(type){
  if(!names[type])return;this.checkpoint();
  const n=this.data.layers.length;const layer=normalize({layers:[{type,name:names[type]+' '+(n+1),text:type==='text'?'Ваш текст':type==='button'?'Перейти':'',fill:type==='button'?'#2563eb':type==='shape'?'#dbeafe':'transparent',color:type==='button'?'#ffffff':'#18202c',align:type==='button'?'center':'left',radius:type==='button'?12:0,desktop:{x:40+n%8*16,y:40+n%8*16,width:type==='shape'?200:280,height:type==='button'?56:type==='text'?100:200},mobile:{x:24,y:40+n%8*24,width:280,height:type==='button'?56:type==='text'?100:200,fontSize:20}}]}).layers[0];
  this.data.layers.push(layer);this.id=layer.id;
 }
 update(patch,device,record=true){
  const l=this.selected;if(!l)return;
  if(l.locked&&!(Object.keys(patch).length===1&&('locked' in patch||'hidden' in patch)))return;
  if(record)this.checkpoint();
  if(device)l[device]=geometry({...l[device],...patch},l[device]);else {
   Object.assign(l,patch);
   l.opacity=num(l.opacity,1,0,1);l.radius=num(l.radius,0,0,1000);
  }
 }
 board(patch,device){this.checkpoint();if(device)this.data[device].height=num(patch.height,this.data[device].height,100,5000);else if('background'in patch)this.data.background=patch.background;}
 remove(){if(!this.selected||this.selected.locked)return;this.checkpoint();this.data.layers=this.data.layers.filter(l=>l.id!==this.id);this.id=this.data.layers.at(-1)?.id||null;}
 duplicate(){if(!this.selected)return;this.checkpoint();const l=clone(this.selected);l.id=uid();l.name+=' — копия';l.locked=false;for(const d of Object.keys(widths)){l[d].x+=16;l[d].y+=16;}this.data.layers.push(l);this.id=l.id;}
 reorder(delta){if(!this.selected||this.selected.locked)return;const a=this.data.layers;const i=a.findIndex(l=>l.id===this.id),j=i+delta;if(j<0||j>=a.length)return;this.checkpoint();[a[i],a[j]]=[a[j],a[i]];}
}
const api={Model,widths,names};if(typeof module!=='undefined')module.exports=api;else root.ZeroModel=api;
})(typeof window!=='undefined'?window:globalThis);
