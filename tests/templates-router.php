<?php
// Local browser fixture only. Never load the production config or database.
if (PHP_SAPI !== 'cli-server' || !getenv('TEMPLATE_TEST_DB')) { http_response_code(404); exit; }
$root=dirname(__DIR__);
require_once $root.'/includes/page-templates.php';
require_once $root.'/page.php';
$dbFile=getenv('TEMPLATE_TEST_DB');$fresh=!file_exists($dbFile);
$pdo=new PDO('sqlite:'.$dbFile);$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
if($fresh){
 $pdo->exec('CREATE TABLE tap_pages (id TEXT PRIMARY KEY,title TEXT,slug TEXT,is_main INTEGER DEFAULT 0,sort_order INTEGER,folder_id TEXT,theme TEXT);CREATE TABLE tap_sections (id TEXT PRIMARY KEY,page_id TEXT,title TEXT,sort_order INTEGER,options TEXT);CREATE TABLE tap_blocks (id TEXT PRIMARY KEY,page_id TEXT,section_id TEXT,block_type_id INTEGER,block_type_name TEXT,options TEXT,is_visible INTEGER,sort_order INTEGER,anchor TEXT);CREATE TABLE tap_settings (setting_key TEXT PRIMARY KEY,setting_value TEXT);');
 $pdo->exec("INSERT INTO tap_pages VALUES ('fixture-page','Страница клиента','fixture',0,0,NULL,'{}');INSERT INTO tap_sections VALUES ('fixture-section','fixture-page','Моя секция',0,'{}');INSERT INTO tap_blocks VALUES ('fixture-block','fixture-page','fixture-section',1,'text','{\"text\":\"Мой контент\",\"text_size\":\"h1\"}',1,0,NULL)");
}
$service=new PageTemplateService($pdo);$service->ensureSchema();
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($path==='/admin/api.php'){
 header('Content-Type: application/json; charset=utf-8');$_SESSION=['admin_auth'=>true];$action=$_GET['action']??'';$body=json_decode(file_get_contents('php://input'),true)?:[];
 if($action==='fixturePages'){echo json_encode($pdo->query('SELECT * FROM tap_pages')->fetchAll());exit;}
 require $root.'/admin/template-api.php';exit;
}
if($path==='/admin/template-preview.php'){
 header("Content-Security-Policy: sandbox; script-src 'none'; form-action 'none'; base-uri 'none'");
 $snapshot=$service->get($_GET['id'])['snapshot'];$page=$snapshot['page'];$page['_template_sections']=$snapshot['sections'];$page['theme']['_template_preview']=true;$blocks=$snapshot['blocks'];
 foreach($blocks as &$b){$b['page_id']=$page['id'];$b['options']=json_encode($b['options']);}unset($b);
 renderPage($page,$blocks,$pdo);exit;
}
if($path==='/test-alpine.js'){header('Content-Type: text/javascript');readfile(getenv('TEMPLATE_TEST_ALPINE'));exit;}
if(str_starts_with($path,'/uploads/')||str_starts_with($path,'/assets/')||str_starts_with($path,'/admin/js/'))return false;
?>
<!DOCTYPE html><html lang="ru"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Templates fixture</title>
<style>*{box-sizing:border-box}body{margin:0;font-family:Arial;background:#f1f5f9}button,input,textarea{font:inherit}button{cursor:pointer;border:0}p,h1,h2,h3{margin:0}button:disabled{cursor:wait}[x-cloak]{display:none!important}</style>
<link rel="stylesheet" href="/assets/templates.css"><script src="/admin/js/templates.js"></script><script defer src="/test-alpine.js"></script>
<body x-data="{activeTab:'templates'}"><button id="save-fixture" @click="$dispatch('save-page-template',{page:{id:'fixture-page',title:'Страница клиента'}})">Сохранить как шаблон</button>
<?php require $root.'/admin/_templates.php';require $root.'/admin/_template-save.php'; ?>
</body></html>
