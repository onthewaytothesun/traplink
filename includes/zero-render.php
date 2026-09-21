<?php
// Structured zero blocks never execute user HTML or user CSS.
function zeroNumber($value, float $fallback, float $min = -10000, float $max = 10000): float {
    return is_numeric($value) && is_finite((float)$value) ? max($min, min($max, (float)$value)) : $fallback;
}
function zeroEscape($value): string { return htmlspecialchars(is_scalar($value) ? (string)$value : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function zeroColor($value, string $fallback): string {
    return is_string($value) && preg_match('/^(#[a-f0-9]{3}|#[a-f0-9]{6}|#[a-f0-9]{8}|transparent)$/iD', $value) ? $value : $fallback;
}
function zeroUrl($value, bool $image = false): string {
    if (!is_string($value) || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) return '';
    if (preg_match('~^https?://[^/]+~i', $value) || preg_match('~^/(?!/)~', $value)) return $value;
    if (!$image && preg_match('/^(#|mailto:|tel:)/i', $value)) return $value;
    return '';
}
function renderZeroBlock(array $data): string {
    $bg=zeroColor($data['background']??'', '#ffffff');
    $html='<div class="zero-public" style="container-type:inline-size;container-name:zero">';
    foreach (['desktop'=>1000,'mobile'=>375] as $device=>$width) {
        $board=is_array($data[$device]??null)?$data[$device]:[];
        $height=zeroNumber($board['height']??null,$device==='desktop'?600:700,100,5000);
        $html.='<div class="zero-public-board zero-public-'.$device.'" style="position:relative;overflow:hidden;isolation:isolate;aspect-ratio:'.$width.'/'.$height.';background:'.$bg.'">';
        foreach ((is_array($data['layers']??null)?$data['layers']:[]) as $layer) {
            if (!is_array($layer) || !empty($layer['hidden'])) continue;
            $type=$layer['type']??'';
            if (!in_array($type,['text','image','button','shape'],true)) continue;
            $g=is_array($layer[$device]??null)?$layer[$device]:[];
            $x=zeroNumber($g['x']??null,40);$y=zeroNumber($g['y']??null,40);
            $w=zeroNumber($g['width']??null,240,8,5000);$h=zeroNumber($g['height']??null,80,8,5000);
            $size=zeroNumber($g['fontSize']??null,24,6,300)/$width*100;
            $radius=zeroNumber($layer['radius']??null,0,0,1000)/$width*100;
            $opacity=zeroNumber($layer['opacity']??null,1,0,1);
            $font=in_array($layer['fontFamily']??'', ['Arial','Georgia','Verdana','Courier New'],true)?$layer['fontFamily']:'Arial';
            $weight=($layer['fontWeight']??'')==='700'?'700':'400';
            $align=in_array($layer['align']??'', ['left','center','right'],true)?$layer['align']:'left';
            $style='position:absolute;box-sizing:border-box;left:'.($x/$width*100).'%;top:'.($y/$height*100).'%;width:'.($w/$width*100).'%;height:'.($h/$height*100).'%;font-size:'.$size.'cqw;border-radius:'.$radius.'cqw;opacity:'.$opacity.';font-family:'.$font.';font-weight:'.$weight.';text-align:'.$align.';line-height:1.25;white-space:pre-wrap;overflow:hidden;overflow-wrap:anywhere;text-decoration:none;color:'.zeroColor($layer['color']??'','#18202c').';background:'.zeroColor($layer['fill']??'','transparent').';';
            if ($type==='button') $style.='display:flex;align-items:center;justify-content:'.(['left'=>'flex-start','center'=>'center','right'=>'flex-end'][$align]).';';
            $href=zeroUrl($layer['href']??'');$tag=$href!==''?'a':'div';
            $html.='<'.$tag.' class="zero-public-layer" style="'.$style.'"'.($href!==''?' href="'.zeroEscape($href).'"':'').'>';
            if ($type==='image') {
                $src=zeroUrl($layer['src']??'',true);
                if ($src!=='') $html.='<img src="'.zeroEscape($src).'" alt="'.zeroEscape($layer['text']??'').'" loading="lazy" style="display:block;width:100%;height:100%;object-fit:cover">';
            } elseif ($type!=='shape') $html.=zeroEscape($layer['text']??'');
            $html.='</'.$tag.'>';
        }
        $html.='</div>';
    }
    return $html.'</div>';
}
