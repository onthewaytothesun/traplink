const {test}=require('node:test');
const assert=require('node:assert/strict');
const Z=require('../assets/zero-model.js');
test('draft does not mutate saved options; mobile layout is independent',()=>{
 const m=new Z.Model();m.add('text'); const saved=m.export(); const edit=new Z.Model(saved);
 edit.update({x:123},'mobile');assert.notEqual(edit.selected.desktop.x,123);
 assert.notEqual(saved.layers[0].mobile.x,123);
});
test('layer order, duplication, undo and redo survive serialization',()=>{
 const m=new Z.Model();m.add('text');const first=m.selected.id;m.add('shape');m.reorder(-1);
 assert.equal(m.data.layers[0].type,'shape'); m.select(first);m.duplicate();
 assert.equal(new Set(m.data.layers.map(x=>x.id)).size,3);
 m.undo();assert.equal(m.data.layers.length,2);m.redo();assert.equal(m.data.layers.length,3);
 assert.deepEqual(new Z.Model(m.export()).export(),m.export());
});
test('locked layers cannot move or delete; new edit clears redo',()=>{
 const m=new Z.Model();m.add('text');m.update({locked:true});const x=m.selected.desktop.x;
 m.update({x:500},'desktop');m.remove();assert.equal(m.selected.desktop.x,x);assert.equal(m.data.layers.length,1);
 m.undo();m.update({name:'Title'});assert.equal(m.future.length,0);
});
test('geometry remains finite and positive',()=>{
 const m=new Z.Model();m.add('shape');m.update({width:-3,height:Infinity,x:NaN},'desktop');
 assert.ok(m.selected.desktop.width>0);assert.ok(Number.isFinite(m.selected.desktop.height));assert.ok(Number.isFinite(m.selected.desktop.x));
});
test('malformed stored fields cannot crash editor or inject canvas styles',()=>{
 assert.equal(new Z.Model(null).data.layers.length,0);
 const m=new Z.Model({layers:[{id:12,type:'text',desktop:null,mobile:null,align:'left;position:fixed',opacity:300}]});
 m.select('12');assert.ok(m.selected);assert.equal(m.selected.align,'left');assert.equal(m.selected.opacity,1);
 m.update({opacity:4,radius:-10});assert.equal(m.selected.opacity,1);assert.equal(m.selected.radius,0);
});
