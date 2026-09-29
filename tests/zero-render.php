<?php
require __DIR__ . '/../includes/zero-render.php';
function check($v, $msg) { if (!$v) throw new Exception($msg); echo "OK $msg\n"; }
$layer=['id'=>'a','type'=>'text','text'=>'<script>alert(1)</script>', 'color'=>'red;position:fixed', 'href'=>'javascript:alert(1)', 'desktop'=>['x'=>10,'y'=>20,'width'=>200,'height'=>60], 'mobile'=>['x'=>5,'y'=>10,'width'=>150,'height'=>60]];
$opts=['version'=>1,'desktop'=>['height'=>500],'mobile'=>['height'=>600],'layers'=>[$layer]];
$html=renderZeroBlock($opts);
check(strpos($html,'<script>')===false,'text escaped');
check(strpos($html,'&lt;script&gt;')!==false,'text retained');
check(strpos($html,'javascript:')===false,'unsafe URL removed');
check(strpos($html,'position:fixed')===false,'unsafe CSS removed');
check(strpos($html,'zero-public-mobile')!==false,'mobile board rendered');
$opts['layers'][0]['hidden']=true;
check(strpos(renderZeroBlock($opts),'&lt;script&gt;')===false,'hidden layer omitted');
$opts['layers'][0]=array_merge($layer,['type'=>'button','href'=>'https://example.com/?a=1&b=2','text'=>'Open']);
check(strpos(renderZeroBlock($opts),'href="https://example.com/?a=1&amp;b=2"')!==false,'safe link preserved');
require __DIR__ . '/../page.php';
check(renderBlock('zero',['html'=>'<b>Legacy</b>'])==='<div><div><b>Legacy</b></div></div>','legacy zero HTML preserved');
check(strpos(renderBlock('zero',['html'=>'<script>legacy</script>','zero'=>$opts]),'<script>')===false,'structured document takes precedence over legacy HTML');
check(strpos(renderZeroBlock(['layers'=>[['type'=>'image','src'=>'javascript:alert(1)','desktop'=>[],'mobile'=>[]]]]),'<img')===false,'unsafe image source omitted');
check(zeroUrl('/\\evil.example')==='','backslash URL rejected');
check(zeroUrl('//evil.example')==='','protocol-relative URL rejected');
