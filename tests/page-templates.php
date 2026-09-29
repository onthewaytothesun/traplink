<?php
require __DIR__.'/../includes/page-templates.php';
require __DIR__.'/../page.php';
function verify($ok,$message){if(!$ok)throw new Exception($message);echo "PASS $message\n";}
function database(): PDO {
 $db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
 $db->exec('CREATE TABLE tap_pages (id TEXT PRIMARY KEY,title TEXT,slug TEXT,is_main INTEGER DEFAULT 0,sort_order INTEGER,folder_id TEXT,theme TEXT);CREATE TABLE tap_sections (id TEXT PRIMARY KEY,page_id TEXT,title TEXT,sort_order INTEGER,options TEXT);CREATE TABLE tap_blocks (id TEXT PRIMARY KEY,page_id TEXT,section_id TEXT,block_type_id INTEGER,block_type_name TEXT,options TEXT,is_visible INTEGER,sort_order INTEGER,anchor TEXT);CREATE TABLE tap_settings (setting_key TEXT PRIMARY KEY,setting_value TEXT);');
 $db->exec("INSERT INTO tap_pages VALUES ('p-old','Original','original',1,10,NULL,'{}');INSERT INTO tap_sections VALUES ('s-old','p-old','Section',2,'{\"background\":\"#ffeeaa\"}');INSERT INTO tap_settings VALUES ('screen','#fafafa'),('head_code','<script>secret()</script>');");
 $q=$db->prepare('INSERT INTO tap_blocks VALUES (?,?,?,?,?,?,?,?,?)');
 $q->execute(['b-old','p-old','s-old',51,'zero',json_encode(['zero'=>['layers'=>[['id'=>'layer1','type'=>'button','text'=>'Go','href'=>'/p/original#contact']]],'_page_id'=>'p-old','_block_id'=>'b-old']),0,3,'contact']);
 return $db;
}
$db=database();$service=new PageTemplateService($db);$service->ensureSchema();
$template=$service->save('p-old','Мой шаблон','Описание');
$db->exec("UPDATE tap_blocks SET options='{}' WHERE id='b-old'");
$page=$service->create($template['id'],str_repeat('a',32));
$blocks=$db->query("SELECT * FROM tap_blocks WHERE page_id='{$page['id']}'")->fetchAll();$sections=$db->query("SELECT * FROM tap_sections WHERE page_id='{$page['id']}'")->fetchAll();
verify(count($blocks)===1&&count($sections)===1,'all blocks and sections copied');
verify($blocks[0]['id']!=='b-old'&&$sections[0]['id']!=='s-old'&&$blocks[0]['section_id']===$sections[0]['id'],'fresh IDs and section mapping');
$opts=json_decode($blocks[0]['options'],true);
verify($opts['zero']['layers'][0]['text']==='Go','snapshot survives source edits');
verify($opts['zero']['layers'][0]['href']==='/p/'.$page['slug'].'#contact','self page links remapped');
verify($opts['_page_id']===$page['id']&&$opts['_block_id']===$blocks[0]['id'],'embedded references remapped');
verify((int)$page['is_main']===0&&(int)$blocks[0]['is_visible']===0&&$blocks[0]['anchor']==='contact','visibility and anchors preserved; new page not main');
$design=json_decode($page['theme'],true)['_template_design'];verify($design['screen']==='#fafafa'&&!isset($design['head_code']),'appearance captured without site scripts');
verify(getPageTheme($db,$page)['screen']==='#fafafa','page renderer uses template appearance');
$again=$service->create($template['id'],str_repeat('a',32));verify($again['id']===$page['id']&&(int)$db->query('SELECT COUNT(*) FROM tap_pages')->fetchColumn()===2,'retry does not duplicate page');
$db->exec("DELETE FROM tap_pages WHERE id='p-old'");$another=$service->create($template['id'],str_repeat('b',32));verify($another['id']!==$page['id'],'template survives deleted source');
$service->delete($template['id']);verify((int)$db->query('SELECT COUNT(*) FROM tap_pages')->fetchColumn()===2,'deleting template preserves created pages');
try{$service->create('missing',str_repeat('c',32));throw new Exception('Missing template accepted');}catch(OutOfBoundsException $e){echo "PASS missing template rejected\n";}
try{$service->delete('builtin-profile');throw new Exception('Preset deleted');}catch(InvalidArgumentException $e){echo "PASS presets immutable\n";}
$db2=database();$s2=new PageTemplateService($db2);$s2->ensureSchema();$t2=$s2->save('p-old','Rollback','');$db2->exec("CREATE TRIGGER reject_clone BEFORE INSERT ON tap_blocks WHEN NEW.id != 'b-old' BEGIN SELECT RAISE(ABORT,'failure'); END");
try{$s2->create($t2['id'],str_repeat('d',32));throw new Exception('Expected failure');}catch(PDOException $e){verify((int)$db2->query('SELECT COUNT(*) FROM tap_pages')->fetchColumn()===1&&(int)$db2->query('SELECT COUNT(*) FROM tap_sections')->fetchColumn()===1,'failed clone rolls back page and sections');}
$presets=array_filter($service->list(),fn($t)=>$t['kind']==='builtin');verify(count($presets)>=7,'seven predefined templates');
foreach($presets as $preset){$p=$service->create($preset['id'],bin2hex(random_bytes(16)));verify(!empty($p['slug']),'preset creates editable page: '.$preset['title']);}
foreach($presets as $preset){
 $snapshot=$service->get($preset['id'])['snapshot'];
 foreach($snapshot['blocks'] as $block){
  $options=$block['options'];if($block['block_type_name']==='form')$options['_block_id']='preview';
  $html=renderBlock($block['block_type_name'],$options);
  verify($html!=='','preset block renders: '.$preset['id'].'/'.$block['id']);
  if($block['block_type_name']==='link')verify(strpos($html,'href="'.htmlspecialchars($options['value'],ENT_QUOTES).'"')!==false,'preset link target rendered');
 }
}
$service->saveDesign($page['id'],['screen'=>'#123456','head_code'=>'bad']);
$changed=$db->query("SELECT * FROM tap_pages WHERE id='{$page['id']}'")->fetch();
verify(getPageTheme($db,$changed)['screen']==='#123456','template page design can be edited independently');
verify($db->query("SELECT setting_value FROM tap_settings WHERE setting_key='screen'")->fetchColumn()==='#fafafa','editing template design leaves global design unchanged');
require __DIR__.'/../includes/page-metadata.php';
updatePageMetadata($db,['id'=>$page['id'],'title'=>'Renamed','slug'=>'renamed','folder_id'=>null]);
$renamed=$db->query("SELECT * FROM tap_pages WHERE id='{$page['id']}'")->fetch();
verify($renamed['title']==='Renamed'&&$renamed['theme']===$changed['theme'],'rename and folder change preserve template appearance and origin');

foreach ($presets as $preset) {
 $snapshot=$service->get($preset['id'])['snapshot'];
 $pictures=[];
 foreach($snapshot['blocks'] as $block) if(in_array($block['block_type_name'],['avatar','banner'],true)) $pictures[]=$block['options']['picture']??'';
 verify(count($pictures)>0,'template includes imagery: '.$preset['id']);
 foreach($pictures as $picture) verify(is_file(dirname(__DIR__).$picture)&&getimagesize(dirname(__DIR__).$picture)!==false,'image exists and decodes: '.$picture);
}
