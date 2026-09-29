<?php
// Run with a disposable site so tests never touch real sessions.
$root=sys_get_temp_dir().'/traplink-session-test-'.bin2hex(random_bytes(6));
mkdir($root.'/site/admin',0700,true);
copy(__DIR__.'/../admin/_session.php',$root.'/site/admin/_session.php');
mkdir($root.'/.traplink-admin-sessions',0700);
$cases=['settings'=>0,'four-days'=>4*86400,'expired'=>8*86400];
try {
 foreach($cases as $name=>$age){
 $code='<?php
 $path='.var_export($root.'/.traplink-admin-sessions',true).';
 $id=bin2hex(random_bytes(16));
 session_save_path($path);session_id($id);session_start();
 $_SESSION=["admin_auth"=>true,"admin_last_activity"=>time()-'.$age.'];
 session_write_close();touch($path."/sess_".$id,time()-'.$age.');
 ini_set("session.gc_probability",1);ini_set("session.gc_divisor",1);
 require '.var_export($root.'/site/admin/_session.php',true).';
 $ok=session_get_cookie_params()["lifetime"]===604800 && realpath(session_save_path())===realpath($path);
 if ('.$age.' > 604800) $ok=$ok && empty($_SESSION["admin_auth"]);
 else $ok=$ok && !empty($_SESSION["admin_auth"]) && ($_SESSION["admin_last_activity"]??0)>=time()-2;
 session_write_close();
 if ('.$age.' === 345600) $ok=$ok && is_file($path."/sess_".$id);
 exit($ok?0:1);';
 file_put_contents($root.'/case.php',$code);
 passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($root.'/case.php'),$status);
 if($status!==0)throw new RuntimeException('FAIL '.$name);
 echo 'PASS '.$name."\n";
 }
} finally {
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $f){$f->isDir()?rmdir($f->getPathname()):unlink($f->getPathname());}rmdir($root);
}
