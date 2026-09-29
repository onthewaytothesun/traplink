<?php
require_once __DIR__ . "/includes/zero-render.php";
require_once __DIR__ . "/includes/page-design.php";
// Renders a page by slug (/p/slug) or main page (/)
// Called from index.php or directly via rewrite rule

function sanitizeCssColor(string $value, string $fallback = '#ffffff'): string {
    $value = trim($value);
    if ($value === '') return $fallback;
    if (strcasecmp($value, 'transparent') === 0) return 'transparent';
    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
        return strtolower($value);
    }
    if (preg_match('/^rgba?\([^)]+\)$/i', $value)) {
        $clean = preg_replace('/[^0-9a-z(),. %]/i', '', $value);
        return $clean !== '' ? $clean : $fallback;
    }
    return $fallback;
}

function themeLinkCssVars(
    int $linkRadius,
    int $linkBorderWidth,
    string $shadowParams,
    string $shadowColor
): string {
    $r = (int)$linkRadius;
    $w = (int)$linkBorderWidth;
    $shadowParams = htmlspecialchars($shadowParams, ENT_QUOTES);
    $shadowColor = htmlspecialchars($shadowColor, ENT_QUOTES);
    return <<<CSS
  --theme-link-border-radius: {$r}px;
  --theme-link-border-width: {$w}px;
  --theme-link-border-width-offset: {$w}px;
  --theme-link-shadow-params: {$shadowParams};
  --theme-link-shadow-color: {$shadowColor};
  --block-link-border-radius: {$r}px;
  --block-link-border-width: {$w}px;
  --block-link-border-width-offset: {$w}px;
  --block-link-shadow-params: {$shadowParams};
  --block-link-shadow-color: {$shadowColor};
CSS;
}

function themeTextSizeCssVars(): string {
    return <<<'CSS'
  --theme-fontsize-factor: 1;
  --theme-lineheight-factor: 1;
  --theme-heading-font-weight: 400;
  --theme-text-size-h1: 50px;
  --theme-text-lineheight-h1: 1.15;
  --theme-text-size-h2: 30px;
  --theme-text-lineheight-h2: 1.25;
  --theme-text-size-h3: 24px;
  --theme-text-lineheight-h3: 1.4;
  --theme-text-size-sm: 14px;
  --theme-text-lineheight-sm: 1.45;
  --theme-text-size-md: 17px;
  --theme-text-lineheight-md: 1.45;
  --theme-text-size-lg: 20px;
  --theme-text-lineheight-lg: 1.45;
CSS;
}

function blockWrapClass(string $name, array $opts): string {
    $type = preg_replace('/[^a-z0-9]/', '', strtolower($name));
    $cls  = "block-item block-$type";
    if ($name === 'text') {
        $size = $opts['text_size'] ?? 'md';
        if (in_array($size, ['h1','h2','h3'], true)) $cls .= ' is-heading';
    }
    return $cls;
}

function getGlobalTheme(PDO $pdo): array {
    try {
        $rows = $pdo->query("SELECT `setting_key`, `setting_value` FROM `tap_settings`")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        return $rows ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

function renderPage(array $page, array $blocks, PDO $pdo): void {
    $theme = getPageTheme($pdo, $page);
    $seoTitle = trim($theme['seo_title'] ?? '');
    $title = htmlspecialchars($seoTitle ?: $page['title']);
    $seoDescription = trim($theme['seo_description'] ?? '');
    $screenBg   = sanitizeCssColor($theme['screen']     ?? '#ffffff', '#ffffff');
    $textColor  = sanitizeCssColor($theme['text_color'] ?? '#343a40', '#343a40');
    $linkBg     = sanitizeCssColor($theme['link_bg']    ?? '#ffffff', '#ffffff');
    $linkColor  = sanitizeCssColor($theme['link_color'] ?? '#343a40', '#343a40');
    $linkRadius      = max(0, min(100, (int)($theme['link_radius']      ?? 7)));
    $linkBorderWidth = max(0, min(10,  (int)($theme['link_border_width'] ?? 0)));
    $linkBorderColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $theme['link_border_color'] ?? '') ?: $linkBg;
    $linkShadow      = in_array($theme['link_shadow'] ?? '', ['none','light','medium','heavy']) ? $theme['link_shadow'] : 'none';
    $linkShadowColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $theme['link_shadow_color'] ?? '') ?: 'rgba(0,0,0,.15)';
    $pageFont        = preg_replace('/[^a-zA-Z0-9 ]/', '', trim($theme['page_font'] ?? ''));
    $shadowParamsMap = ['light'=>'0 2px 8px 0','medium'=>'0 4px 16px 0','heavy'=>'0 8px 28px 0'];
    $shadowParams    = $shadowParamsMap[$linkShadow] ?? '0 0 0 0';
    $shadowColor     = ($linkShadow === 'none') ? 'transparent' : $linkShadowColor;
    ?><!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?></title>
<?php if ($seoDescription !== ''): ?><meta name="description" content="<?= htmlspecialchars($seoDescription) ?>"><?php endif; ?>
<?php $faviconUrl = trim($theme['favicon_url'] ?? ''); if ($faviconUrl): ?><link rel="icon" href="<?= htmlspecialchars($faviconUrl) ?>"><?php endif; ?>
<link rel="stylesheet" href="/assets/taplink-frontend.css">
<style>
:root {
  --theme-text-color:        <?= htmlspecialchars($textColor) ?>;
  --theme-heading-color:     <?= htmlspecialchars($textColor) ?>;
  --theme-link-background:   <?= htmlspecialchars($linkBg) ?>;
  --theme-link-title-color:  <?= htmlspecialchars($linkColor) ?>;
  --theme-link-subtitle-color: <?= htmlspecialchars($linkColor) ?>;
  --theme-link-border-color: <?= htmlspecialchars($linkBg) ?>;
  --block-link-background:       var(--theme-link-background);
  --block-link-title-color:      var(--theme-link-title-color);
  --block-link-subtitle-color:   var(--theme-link-subtitle-color);
  --block-link-border-color:     var(--theme-link-border-color);
  --block-link-subtitle-fontsize: 13px;
  --block-link-icon-background:  rgba(52,58,64,.12);
  --block-link-border-color: <?= htmlspecialchars($linkBorderColor) ?>;
<?= themeLinkCssVars($linkRadius, $linkBorderWidth, $shadowParams, $shadowColor) ?>
  --theme-link-backdrop-filter: none;
<?= themeTextSizeCssVars() ?>
  --section-padding-top: 0px;
  --section-padding-right: 0px;
  --section-padding-bottom: 0px;
  --section-padding-left: 0px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; }
body { font-family: <?= $pageFont ? "'" . $pageFont . "'," : '' ?>-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: var(--theme-text-color); line-height: 1.6; }
.main { height: 100%; }
.page.vue { height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; }
.page-background { position: relative; min-height: 100%; }
.page-background:before { content: ''; position: fixed; inset: 0; z-index: -1; }
.main-theme .page-background:before { background-color: <?= htmlspecialchars($screenBg) ?>; }
.page-container { max-width: 640px; margin: 0 auto; }
</style>
<link rel="stylesheet" href="/assets/blocks.css">
<link rel="stylesheet" href="/assets/zero-public.css?v=1">
<?php
    // Page font via Google Fonts
    if ($pageFont) {
        echo "<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=" . rawurlencode($pageFont) . ":wght@400;600;700&display=swap\">\n";
    }
    // Collect Google Fonts used in text blocks
    $googleFonts = [];
    foreach ($blocks as $block) {
        if (!$block['is_visible'] || $block['block_type_name'] !== 'text') continue;
        $o = is_array($block['options']) ? $block['options'] : (json_decode($block['options'] ?? '{}', true) ?: []);
        $font = trim($o['font'] ?? '');
        if ($font) $googleFonts[$font] = true;
    }
    if ($googleFonts) {
        $families = implode('&family=', array_map(fn($f) => rawurlencode($f) . ':wght@400;600;700', array_keys($googleFonts)));
        echo "<link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family={$families}&display=swap\">\n";
    }
    // Detect if any visible video block needs Video.js player
    $needsVjs = false;
    foreach ($blocks as $block) {
        if (!$block['is_visible'] || $block['block_type_name'] !== 'video') continue;
        $o = is_array($block['options']) ? $block['options'] : (json_decode($block['options'] ?? '{}', true) ?: []);
        $vUrl = $o['url'] ?? '';
        if (($o['handler'] ?? '') === 'player' || preg_match('/\.(mp4|webm|ogg|mov)(\?.*)?$/i', $vUrl)) {
            $needsVjs = true;
            break;
        }
    }
    if ($needsVjs) {
        echo "<link rel=\"stylesheet\" href=\"https://vjs.zencdn.net/8.18.1/video-js.min.css\">\n";
        echo "<script src=\"https://vjs.zencdn.net/8.18.1/video.min.js\"></script>\n";
    }
    $headCode = trim($theme['head_code'] ?? '');
    if ($headCode !== '') {
        echo $headCode . "\n";
    }
?>
</head>
<body>
<div class="main base-theme main-theme">
<div class="page vue">
<div class="page-background">
<div class="page-content max-page-container-lg page-valign-top">
<?php
    // Load section meta (sort_order + options), keyed by id
    $sectionMeta = [];
    try {
        if (isset($page['_template_sections'])) {
            foreach ($page['_template_sections'] as $section) {
                $sectionMeta[$section['id']] = ['sort_order'=>(int)$section['sort_order'], 'options'=>$section['options']];
            }
        }
        $sids = isset($page['_template_sections']) ? [] : array_filter(array_unique(array_column($blocks, 'section_id')));
        if ($sids) {
            $in   = implode(',', array_fill(0, count($sids), '?'));
            $stmt = $pdo->prepare("SELECT id, sort_order, options FROM tap_sections WHERE id IN ($in)");
            $stmt->execute(array_values($sids));
            foreach ($stmt->fetchAll() as $row) {
                $sectionMeta[$row['id']] = [
                    'sort_order' => (int)$row['sort_order'],
                    'options'    => json_decode($row['options'] ?: '{}', true) ?: [],
                ];
            }
        }
    } catch (PDOException $e) {}

    // Group visible blocks: named sections share one group; solo blocks each get own group
    $groups = [];
    foreach ($blocks as $block) {
        if (!$block['is_visible']) continue;
        $key = $block['section_id'] ?: ('_solo_' . $block['id']);
        $groups[$key][] = $block;
    }

    // Sort groups by minimum block sort_order within each group
    uksort($groups, function($a, $b) use ($groups) {
        $oa = min(array_column($groups[$a], 'sort_order'));
        $ob = min(array_column($groups[$b], 'sort_order'));
        return $oa <=> $ob;
    });

    foreach ($groups as $sectionKey => $groupBlocks):
        $sectionClass = 'section-main blocks-section';
        $so = isset($sectionMeta[$sectionKey]) ? ($sectionMeta[$sectionKey]['options'] ?? []) : [];
        $styles = [];
        $pt = (int)($so['padding_top']    ?? 0);
        $pb = (int)($so['padding_bottom'] ?? 0);
        if ($pt) $styles[] = "--section-padding-top:{$pt}px";
        if ($pb) $styles[] = "--section-padding-bottom:{$pb}px";
        // Support both legacy bg_color and taplink-style bg.color / bg.picture
        $bgOpts = $so['bg'] ?? [];
        $bgColor = $bgOpts['color'] ?? ($so['bg_color'] ?? '');
        $bgFn    = $bgOpts['picture']['filename'] ?? '';
        if ($bgColor) $styles[] = 'background-color:' . htmlspecialchars($bgColor);
        $secLinkColor = $so['link']['color'] ?? '';
        $secLinkBg    = $so['link']['bg']    ?? '';
        if ($secLinkColor) {
            $secLinkColorClean = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $secLinkColor);
            $styles[] = '--theme-link-title-color:' . $secLinkColorClean;
            $styles[] = '--theme-link-subtitle-color:' . $secLinkColorClean;
            $styles[] = '--block-link-title-color:' . $secLinkColorClean;
            $styles[] = '--block-link-subtitle-color:' . $secLinkColorClean;
        }
        if ($secLinkBg) {
            $secLinkBgClean = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $secLinkBg);
            $styles[] = '--block-link-background:' . $secLinkBgClean . ';--block-link-border-color:' . $secLinkBgClean;
        }
        if ($bgFn) {
            $bgUrl   = str_starts_with($bgFn, '/') ? $bgFn : ('https://p.taplink.st/p/' . $bgFn);
            $bgSize  = ($bgOpts['size'] ?? 'cover') === 'width' ? 'cover' : htmlspecialchars($bgOpts['size'] ?? 'cover');
            $bgRpt   = htmlspecialchars($bgOpts['repeat']   ?? 'no-repeat');
            $bgPos   = htmlspecialchars($bgOpts['position'] ?? '50% 50%');
            $styles[] = 'background-image:url(' . htmlspecialchars($bgUrl) . ')';
            $styles[] = "background-size:$bgSize";
            $styles[] = "background-repeat:$bgRpt";
            $styles[] = "background-position:$bgPos";
        }
        $sectionStyle = $styles ? ' style="' . implode(';', $styles) . '"' : '';
        echo "<section class=\"$sectionClass\"$sectionStyle><div><div class=\"page-container\">";
        foreach ($groupBlocks as $block) {
            $opts = is_array($block['options']) ? $block['options'] : (json_decode($block['options'] ?? '{}', true) ?: []);
            $name = $block['block_type_name'];
            if ($name === 'form') {
                $opts['_block_id'] = $block['id'];
                $opts['_page_id']  = $block['page_id'] ?? '';
            }
            $html = renderBlock($name, $opts);
            if ($html !== '') {
                $wrapCls    = blockWrapClass($name, $opts) . ' bid-' . substr($block['id'], 0, 8);
                $anchorAttr = $block['anchor'] ? ' id="' . htmlspecialchars($block['anchor']) . '"' : '';
                echo "<div class=\"$wrapCls\"$anchorAttr>$html</div>";
            }
        }
        echo "</div></div></section>";
    endforeach;
    ?>
</div>
</div>
</div>
</div>
<?php
    // Form JS
    if (array_filter($blocks, fn($b) => $b['block_type_name'] === 'form' && $b['is_visible'])):
    ?>
<script>
document.querySelectorAll('.block-form').forEach(function(form){
  form.addEventListener('submit',async function(e){
    e.preventDefault();
    var btn=form.querySelector('button[type=submit]'),origText=btn.textContent;
    var data={};
    new FormData(form).forEach(function(v,k){data[k]=v;});
    btn.disabled=true;btn.textContent='Отправляю…';
    try{
      var r=await fetch('/submit.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});
      var d=await r.json();
      if(d.ok){
        if(d.form_url){window.location.href=d.form_url;}
        else{form.innerHTML='<p class="form-success">Спасибо! Заявка отправлена.</p>';}
      }
      else{btn.disabled=false;btn.textContent=origText;alert(d.error||'Ошибка отправки');}
    }catch(err){btn.disabled=false;btn.textContent=origText;alert('Ошибка сети');}
  });
});
</script>
    <?php endif; ?>
<?php
    // Timer JS
    if (array_filter($blocks, fn($b) => $b['block_type_name'] === 'timer' && $b['is_visible'])):
    ?>
<script>
document.querySelectorAll('.timer-widget[data-date]').forEach(function(el){
  var date=new Date(el.dataset.date).getTime();
  function tick(){
    var now=Date.now(),diff=date-now;
    if(diff<=0){el.querySelector('.timer-display').innerHTML='<span style="font-size:1.2rem">Время вышло</span>';return;}
    var d=Math.floor(diff/86400000),h=Math.floor(diff%86400000/3600000),m=Math.floor(diff%3600000/60000),s=Math.floor(diff%60000/1000);
    el.querySelectorAll('.timer-unit').forEach(function(u,i){
      u.querySelector('.num').textContent=String([d,h,m,s][i]).padStart(2,'0');
    });
  }
  tick();setInterval(tick,1000);
});
</script>
    <?php endif; ?>
</body>
</html>
    <?php
}

function renderBlock(string $name, array $opts): string {
    switch ($name) {

        case 'text':
            $size  = $opts['text_size'] ?? 'md';
            $align = $opts['text_align'] ?? 'left';
            $text  = nl2br(strip_tags($opts['text'] ?? '', '<b><strong><i><em><u><s><strike><del><ins><mark><small><sup><sub><br><span><font><a>'));
            $isHeading = in_array($size, ['h1', 'h2', 'h3'], true);
            $tag   = $isHeading ? $size : 'p';
            $sizeKey = in_array($size, ['h1', 'h2', 'h3', 'sm', 'md', 'lg'], true) ? $size : 'md';
            $styles = ['text-align:' . (in_array($align, ['left','center','right','justify'], true) ? $align : 'left')];
            $font = preg_replace('/[^a-zA-Z0-9 ]/', '', trim($opts['font'] ?? ''));
            if ($font) $styles[] = 'font-family:' . htmlspecialchars($font) . ',sans-serif';
            $defaultBold = in_array($size, ['h1','h2'], true);
            $isBold = array_key_exists('bold', $opts) ? !empty($opts['bold']) : $defaultBold;
            $styles[] = 'font-weight:' . ($isBold ? '700' : '400');
            if (!empty($opts['italic'])) $styles[] = 'font-style:italic';
            $deco = [];
            if (!empty($opts['underline'])) $deco[] = 'underline';
            if (!empty($opts['strikethrough'])) $deco[] = 'line-through';
            if ($deco) $styles[] = 'text-decoration:' . implode(' ', $deco);
            $color = preg_replace('/[^#a-zA-Z0-9]/', '', $opts['color'] ?? '');
            if ($color) $styles[] = 'color:' . $color;
            $styles[] = 'font-size:calc(var(--theme-text-size-' . $sizeKey . ') * var(--theme-fontsize-factor))';
            $styles[] = 'line-height:calc(var(--theme-text-lineheight-' . $sizeKey . ') * var(--theme-lineheight-factor))';
            $styleAttr = ' style="' . implode(';', $styles) . '"';
            return "<$tag$styleAttr>$text</$tag>";

        case 'link':
            $title    = htmlspecialchars($opts['title'] ?? '');
            $subtitle = htmlspecialchars($opts['subtitle'] ?? '');
            $style    = htmlspecialchars($opts['style'] ?? 'one');
            $icon     = $opts['icon'] ?? '';
            $action   = $opts['action'] ?? $opts['type'] ?? 'website';
            $rawUrl   = is_string($opts['value'] ?? '') ? ($opts['value'] ?? '') : '';
            $url = match($action) {
                'vcard'    => '#',
                'telegram' => str_starts_with($rawUrl, 'http') ? $rawUrl : 'https://t.me/' . ltrim($rawUrl, '@/ '),
                'phone'    => str_starts_with($rawUrl, 'tel:') ? $rawUrl : 'tel:' . preg_replace('/[^+0-9]/', '', $rawUrl),
                'email'    => str_starts_with($rawUrl, 'mailto:') ? $rawUrl : 'mailto:' . $rawUrl,
                'page'     => '/p/' . preg_replace('/[^a-z0-9\-]/', '', strtolower($rawUrl)),
                default    => $rawUrl ?: '#',
            };
            $url = htmlspecialchars($url);
            if (!$title) return '';
            $iconSvg = ($icon && $icon !== 'none') ? getLinkIconSvg($icon) : '';
            // taplink-style picture thumb: thumb.t==="p", thumb.p.filename
            $thumbPic = '';
            $thumbData = $opts['thumb'] ?? [];
            if (($thumbData['t'] ?? '') === 'p' && !empty($thumbData['p']['filename'])) {
                $tfn = $thumbData['p']['filename'];
                $turl = str_starts_with($tfn, '/') ? $tfn : ('https://p.taplink.st/p/' . $tfn);
                $thumbPic = htmlspecialchars($turl);
            }
            $styleClass = $style !== 'one' ? " style-$style" : '';
            $blockVars = [];
            $design = $opts['design'] ?? [];
            $cbg = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $opts['custom_bg'] ?? ((!empty($design['on']) ? ($design['bg'] ?? '') : '') ?: ''));
            if ($cbg) { $blockVars[] = "--block-link-background:$cbg"; $blockVars[] = "--block-link-border-color:$cbg"; }
            $cc = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $opts['custom_color'] ?? ((!empty($design['on']) ? ($design['color'] ?? '') : '') ?: ''));
            if ($cc) { $blockVars[] = "--block-link-title-color:$cc"; $blockVars[] = "--block-link-subtitle-color:$cc"; }
            if (isset($opts['custom_radius']) && $opts['custom_radius'] !== '') $blockVars[] = "--block-link-border-radius:" . max(0,min(100,(int)$opts['custom_radius'])) . "px";
            if (isset($opts['custom_border_width']) && $opts['custom_border_width'] !== '') { $bw = max(0,min(10,(int)$opts['custom_border_width'])); $blockVars[] = "--block-link-border-width:{$bw}px"; $blockVars[] = "--block-link-border-width-offset:{$bw}px"; }
            $cborderColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $opts['custom_border_color'] ?? '');
            if ($cborderColor) $blockVars[] = "--block-link-border-color:$cborderColor";
            $align = in_array($opts['text_align'] ?? '', ['left','center','right'], true) ? $opts['text_align'] : '';
            if ($align) $blockVars[] = "text-align:$align";
            $blockStyle = $blockVars ? ' style="' . htmlspecialchars(implode(';', $blockVars)) . '"' : '';
            $dir = 'is-ltr';
            if ($iconSvg || $thumbPic || $subtitle) {
                $thumbHtml = $thumbPic
                    ? "<div class=\"thumb\"><img src=\"$thumbPic\" alt=\"\" loading=\"lazy\"></div>"
                    : ($iconSvg ? "<div class=\"thumb\"><div class=\"is-icon\">$iconSvg</div></div>" : '');
                $textHtml  = "<div><div class=\"btn-link-title\">$title</div>"
                           . ($subtitle ? "<div class=\"btn-link-subtitle\">$subtitle</div>" : '')
                           . "</div>";
                $a = "<a href=\"$url\" class=\"btn-link with-thumb$styleClass\"$blockStyle>$thumbHtml$textHtml</a>";
            } else {
                $a = "<a href=\"$url\" class=\"btn-link$styleClass\"$blockStyle><div class=\"btn-link-title\">$title</div></a>";
            }
            return "<div><div class=\"$dir\">$a</div></div>";

        case 'break':
            $h = (int)($opts['height'] ?? $opts['break_size'] ?? 20);
            $s = htmlspecialchars($opts['style'] ?? 'none');
            return "<div class=\"break-line style-$s\" style=\"height:{$h}px\"></div>";

        case 'video':
            $url     = $opts['url'] ?? '';
            $handler = $opts['handler'] ?? '';
            $autoplay = !empty($opts['is_autoplay']);
            if (!$url) return '';
            // Direct file (mp4/webm/ogg) or explicit player handler → Video.js
            if ($handler === 'player' || preg_match('/\.(mp4|webm|ogg|mov)(\?.*)?$/i', $url)) {
                $src  = htmlspecialchars($url);
                $mime = htmlspecialchars($opts['type'] ?? 'video/mp4');
                $vid  = 'vjs' . substr(md5($url), 0, 8);
                $autoAttr = $autoplay ? ' autoplay muted' : '';
                return <<<HTML
<video id="{$vid}" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="auto" playsinline{$autoAttr} style="width:100%;border-radius:var(--block-radius,8px);">
<source src="{$src}" type="{$mime}">
</video>
<script>typeof videojs!=='undefined'&&videojs('{$vid}',{fluid:true,autoplay:false,controls:true});</script>
HTML;
            }
            $embed = videoEmbed($url);
            if (!$embed) return '';
            $apParam = $autoplay ? '?autoplay=1' : '';
            return "<iframe src=\"" . htmlspecialchars($embed . $apParam) . "\" allowfullscreen allow=\"autoplay; encrypted-media\"></iframe>";

        case 'zero':
            if (is_array($opts['zero'] ?? null)) return renderZeroBlock($opts['zero']);
            // Older zero blocks retain their original HTML.
        case 'html':
            $html = $opts['html'] ?? '';
            return $html !== '' ? "<div><div>$html</div></div>" : '';

        case 'pictures':
            $list = $opts['list'] ?? [];
            if (!$list) return '';
            $dotsOpts  = $opts['dots'] ?? [];
            $showDots  = $dotsOpts['show'] ?? true;
            $dotColor  = $dotsOpts['color'] ?? '';
            $dotShape  = $dotsOpts['shape'] ?? 'circle';
            $shapeClass = $dotShape === 'line' ? 'is-indicator-line' : ($dotShape === 'square' ? 'is-indicator-square' : 'is-indicator-dots');
            $dotStyle  = $dotColor ? ' style="--dot-color:' . htmlspecialchars($dotColor) . ';"' : '';
            $slides = '';
            $dots   = '';
            $i      = 0;
            foreach ($list as $item) {
                $fn  = $item['p']['filename'] ?? '';
                if (!$fn) continue;
                $src    = htmlspecialchars((str_starts_with($fn, '/') ? '' : 'https://p.taplink.st/p/') . $fn);
                $href   = $item['link']['value'] ?? '';
                $active = $i === 0 ? ' active' : '';
                $pic    = "<div class=\"picture-container lazy picture-cover none-slider is-loaded\" style=\"background-image:url({$src});\" data-src=\"{$src}\"><div class=\"overlay\"></div></div>";
                $inner  = $href ? "<a href=\"" . htmlspecialchars($href) . "\">$pic</a>" : $pic;
                $slides .= "<div class=\"slider-slide{$active}\">$inner</div>";
                $dots   .= "<div class=\"slider-dot{$active}\"></div>";
                $i++;
            }
            if (!$i) return '';
            $uid     = 'sl' . substr(md5(serialize($list)), 0, 8);
            $navHtml = ($showDots && $i > 1) ? "<div class=\"slider-nav\"{$dotStyle}>$dots</div>" : '';
            return <<<HTML
<div class="block-slider has-rtl has-cols-1 is-indicator-outside {$shapeClass} is-light" id="{$uid}">
<div class="block-slider-inner">
<div class="slider slider-pictures slider-has-border">
<div class="slider-inner">$slides</div></div></div>$navHtml</div>
<script>(function(){
var root=document.getElementById('{$uid}');
var inner=root.querySelector('.slider-inner');
var dots=root.querySelectorAll('.slider-dot');
var total=root.querySelectorAll('.slider-slide').length;
var cur=0;
function go(n){cur=(n+total)%total;inner.style.transform='translateX(-'+cur*100+'%)';dots.forEach(function(d,i){d.classList.toggle('active',i===cur);});}
dots.forEach(function(d,i){d.addEventListener('click',function(){go(i);});});
var sx=0;
inner.addEventListener('touchstart',function(e){sx=e.touches[0].clientX;},{passive:true});
inner.addEventListener('touchend',function(e){var dx=e.changedTouches[0].clientX-sx;if(Math.abs(dx)>40)go(dx<0?cur+1:cur-1);},{passive:true});
})();</script>
HTML;

        case 'banner':
            // support both legacy opts['picture'] and taplink opts['p']['filename']
            $fn   = $opts['p']['filename'] ?? '';
            $pic  = $fn
                ? ((str_starts_with($fn, '/') ? '' : 'https://p.taplink.st/p/') . $fn)
                : ($opts['picture'] ?? '');
            $pic  = htmlspecialchars($pic);
            $rawLink = $opts['link'] ?? '';
            $link = htmlspecialchars(is_array($rawLink) ? ($rawLink['value'] ?? '') : $rawLink);
            if (!$pic) return '';
            $img = "<img src=\"$pic\" alt=\"\" loading=\"lazy\">";
            return $link
                ? "<a href=\"$link\" class=\"banner-inner\">$img</a>"
                : "<div class=\"banner-inner\">$img</div>";

        case 'avatar':
            $pic = htmlspecialchars($opts['picture'] ?? '');
            if (!$pic) return '';
            return "<img src=\"$pic\" alt=\"\" loading=\"lazy\">";

        case 'map':
            $lat  = (float)($opts['lat'] ?? 0);
            $lng  = (float)($opts['lng'] ?? 0);
            $zoom = (int)($opts['zoom'] ?? 14);
            if (!$lat && !$lng) return '';
            $src = "https://maps.google.com/maps?q=$lat,$lng&z=$zoom&output=embed";
            return "<iframe src=\"" . htmlspecialchars($src) . "\" allowfullscreen loading=\"lazy\"></iframe>";

        case 'timer':
            $date = $opts['date'] ?? '';
            if (!$date && isset($opts['tms'])) {
                $date = date('c', (int)$opts['tms']);
            }
            $date = htmlspecialchars($date);
            if (!$date) return '';
            return "<div class=\"timer-widget\" data-date=\"$date\">
  <div class=\"timer-display\">
    " . implode("\n    ", array_map(fn($l) => "<div class=\"timer-unit\"><div class=\"num\">00</div><div class=\"label\">$l</div></div>",
        ['дней','часов','минут','секунд'])) . "
  </div>
</div>";

        case 'collapse':
            $fields = $opts['fields'] ?? [];
            if (!$fields) return '';
            $out = '';
            foreach ($fields as $f) {
                $t = htmlspecialchars($f['title'] ?? '');
                $b = $f['text'] ?? '';
                $open = !empty($f['opened']) ? ' open' : '';
                $out .= "<details class=\"collapse-item\"$open><summary>$t</summary><p>$b</p></details>\n";
            }
            return $out;

        case 'media':
            $fields = $opts['fields'] ?? [];
            if (!$fields) return '';
            $out = '';
            foreach ($fields as $f) {
                $t = htmlspecialchars($f['title'] ?? '');
                $b = nl2br(htmlspecialchars($f['text'] ?? ''));
                $td = $f['thumb'] ?? [];
                $thumbHtml = '';
                if (($td['t'] ?? '') === 'p' && !empty($td['p']['filename'])) {
                    $tfn = $td['p']['filename'];
                    $turl = htmlspecialchars(str_starts_with($tfn, '/') ? $tfn : ('https://p.taplink.st/p/' . $tfn));
                    $thumbHtml = "<div class=\"media-item-thumb\"><img src=\"$turl\" alt=\"\" loading=\"lazy\"></div>";
                } elseif (($td['t'] ?? '') === 'i' && !empty($td['i'])) {
                    $iconSvg = getMediaIconSvg($td['i']);
                    if ($iconSvg) $thumbHtml = "<div class=\"media-item-thumb\"><div class=\"media-item-icon\">$iconSvg</div></div>";
                }
                $out .= "<div class=\"media-item\">$thumbHtml<div class=\"media-item-text\"><div class=\"title\">$t</div><div class=\"text\">$b</div></div></div>\n";
            }
            return $out;

        case 'pricing':
            $fields = $opts['fields'] ?? [];
            if (!$fields) return '';
            $cur = htmlspecialchars($opts['currency'] ?? '₽');
            $out = '';
            foreach ($fields as $f) {
                $t = htmlspecialchars($f['title'] ?? '');
                $p = htmlspecialchars((string)($f['price'] ?? ''));
                $out .= "<div class=\"pricing-item\"><span>$t</span><span class=\"price\">$p $cur</span></div>\n";
            }
            return $out;

        case 'messenger':
            $items = $opts['items'] ?? [];
            if (!$items) return '';
            $labels = ['telegram'=>'Telegram','whatsapp'=>'WhatsApp','viber'=>'Viber','instagram'=>'Instagram','vk'=>'ВКонтакте','max'=>'MAX'];
            $out = '';
            foreach ($items as $item) {
                $m   = strtolower($item['messenger'] ?? 'default');
                $v   = htmlspecialchars($item['v'] ?? '');
                $lbl = $labels[$m] ?? ucfirst($m);
                $url = match($m) {
                    'telegram'  => "https://t.me/$v",
                    'whatsapp'  => "https://wa.me/$v",
                    'viber'     => "viber://chat?number=$v",
                    'instagram' => "https://instagram.com/$v",
                    'vk'        => "https://vk.com/$v",
                    'max'       => "https://max.ru/$v",
                    default     => "#"
                };
                $cls = in_array($m, ['telegram','whatsapp','viber','instagram','vk','max']) ? $m : 'default';
                $out .= "<a href=\"$url\" class=\"messenger-btn $cls\">$lbl</a>\n";
            }
            return $out;

        case 'form':
            $fields  = $opts['fields'] ?? [];
            $btnText = htmlspecialchars($opts['form_btn'] ?? 'Отправить');
            $blockId = htmlspecialchars($opts['_block_id'] ?? '');
            $pageId  = htmlspecialchars($opts['_page_id']  ?? '');
            if (!$fields || !$blockId) return '';
            $inputTypeMap = [1 => 'text', 2 => 'text', 3 => 'text', 5 => 'tel', 6 => 'email', 9 => 'date', 12 => 'number', 13 => 'time'];
            $phMap        = [1 => 'Имя', 2 => 'Фамилия Имя Отчество', 3 => 'Введите текст', 5 => '+7 (___) ___-__-__', 6 => 'email@example.com'];
            $countries    = ['Россия','Украина','Беларусь','Казахстан','Узбекистан','Азербайджан','Армения','Грузия','Кыргызстан','Молдова','Таджикистан','Туркменистан','Латвия','Литва','Эстония','Германия','Франция','Великобритания','США','Канада','Австралия','Турция','Китай','Япония','Южная Корея','Индия','Бразилия','Аргентина','Мексика','ОАЭ','Израиль','Италия','Испания','Польша','Чехия','Нидерланды','Швеция','Норвегия','Финляндия','Швейцария','Австрия','Португалия','Другая'];
            $productId = htmlspecialchars($opts['product_id'] ?? '');
            $out  = "<form class=\"block-form has-form-normal\">\n";
            $out .= "<input type=\"hidden\" name=\"block_id\" value=\"$blockId\">\n";
            $out .= "<input type=\"hidden\" name=\"page_id\" value=\"$pageId\">\n";
            if ($productId) {
                $out .= "<input type=\"hidden\" name=\"product_id\" value=\"$productId\">\n";
            }
            foreach ($fields as $field) {
                $tid  = (int)($field['type_id'] ?? 3);
                $lbl  = htmlspecialchars($field['title'] ?? '');
                $key  = 'field_' . ($field['idx'] ?? 0);
                $req  = !empty($field['required']) ? ' required' : '';
                $ph   = htmlspecialchars($phMap[$tid] ?? '');
                $fieldOpts = $field['options'] ?? [];

                $out .= "<div class=\"form-field\">";

                if ($tid === 11) {
                    // Checkbox
                    $out .= "<label class=\"form-checkbox-label\"><input type=\"checkbox\" name=\"$key\" value=\"Да\"$req> $lbl</label>";
                } elseif ($tid === 7) {
                    // Radio
                    if ($lbl) $out .= "<label>$lbl</label>";
                    $out .= "<div class=\"form-radio-group\">";
                    foreach ($fieldOpts as $oi => $optVal) {
                        $ov = htmlspecialchars($optVal);
                        $out .= "<label class=\"form-radio-label\"><input type=\"radio\" name=\"$key\" value=\"$ov\"$req> $ov</label>";
                    }
                    $out .= "</div>";
                } elseif ($tid === 10) {
                    // Select / list
                    if ($lbl) $out .= "<label>$lbl</label>";
                    $out .= "<select name=\"$key\"$req>";
                    $out .= "<option value=\"\" disabled selected>Выберите…</option>";
                    foreach ($fieldOpts as $optVal) {
                        $ov = htmlspecialchars($optVal);
                        $out .= "<option value=\"$ov\">$ov</option>";
                    }
                    $out .= "</select>";
                } elseif ($tid === 8) {
                    // Country
                    if ($lbl) $out .= "<label>$lbl</label>";
                    $out .= "<select name=\"$key\"$req>";
                    $out .= "<option value=\"\" disabled selected>Выберите страну…</option>";
                    foreach ($countries as $c) {
                        $cv = htmlspecialchars($c);
                        $out .= "<option value=\"$cv\">$cv</option>";
                    }
                    $out .= "</select>";
                } else {
                    // text, tel, email, date, number, time
                    $type = $inputTypeMap[$tid] ?? 'text';
                    if ($lbl) $out .= "<label>$lbl</label>";
                    $out .= "<input type=\"$type\" name=\"$key\" placeholder=\"$ph\"$req>";
                }

                $out .= "</div>\n";
            }
            $out .= "<button type=\"submit\" class=\"button btn-link btn-link-title\">$btnText</button>\n";
            $out .= "</form>";
            return $out;

        case 'socialnetworks':
            $items = $opts['items'] ?? [];
            if (!$items) return '';
            $labels = ['instagram'=>'Instagram','vk'=>'ВКонтакте','telegram'=>'Telegram','youtube'=>'YouTube','facebook'=>'Facebook','twitter'=>'Twitter','tiktok'=>'TikTok'];
            $out = "<div class=\"block-social\">\n";
            foreach ($items as $item) {
                $t   = strtolower($item['type'] ?? 'default');
                $url = htmlspecialchars($item['link'] ?? '#');
                $lbl = $labels[$t] ?? ucfirst($t);
                $cls = array_key_exists($t, $labels) ? $t : 'default';
                $out .= "<a href=\"$url\" class=\"social-btn $cls\">$lbl</a>\n";
            }
            return $out . "</div>";

        case 'music':
            $items = $opts['items'] ?? [];
            if (!$items) return '';
            $out = '';
            foreach ($items as $item) {
                $type  = strtolower($item['type'] ?? '');
                $value = trim($item['value'] ?? '');
                if (!$value) continue;
                if ($type === 'file') {
                    $url   = htmlspecialchars($value);
                    $title = htmlspecialchars($item['title'] ?? basename($value));
                    $out .= "<div class=\"music-player\"><div class=\"music-player-title\">$title</div><audio controls preload=\"metadata\" src=\"$url\"></audio></div>\n";
                } elseif ($type === 'spotify' && preg_match('#(track|album|playlist|episode)/([a-zA-Z0-9]+)#', $value, $m)) {
                    $out .= "<iframe style=\"border-radius:12px\" width=\"100%\" height=\"152\" frameborder=\"0\" allow=\"autoplay;clipboard-write;encrypted-media;fullscreen;picture-in-picture\" loading=\"lazy\" src=\"https://open.spotify.com/embed/{$m[1]}/{$m[2]}\"></iframe>\n";
                } elseif ($type === 'yandex' && preg_match('#(album/\d+(?:/track/\d+)?)#', $value, $m)) {
                    $src = htmlspecialchars($m[1]);
                    $out .= "<iframe frameborder=\"0\" style=\"border:none;width:100%;height:180px;border-radius:12px\" src=\"https://music.yandex.ru/iframe/#$src\"></iframe>\n";
                } elseif ($type === 'apple' && preg_match('#music\.apple\.com/(.+)#', $value, $m)) {
                    $path = htmlspecialchars($m[1]);
                    $out .= "<iframe allow=\"autoplay *;encrypted-media *;fullscreen *\" frameborder=\"0\" style=\"width:100%;max-height:175px;overflow:hidden;border-radius:12px;background:transparent\" sandbox=\"allow-forms allow-popups allow-same-origin allow-scripts allow-top-navigation-by-user-activation\" src=\"https://embed.music.apple.com/$path\"></iframe>\n";
                } else {
                    $url = htmlspecialchars($value);
                    $out .= "<a href=\"$url\" target=\"_blank\" class=\"messenger-btn default\" style=\"text-align:center\">🎵 " . htmlspecialchars(ucfirst($type ?: 'Музыка')) . "</a>\n";
                }
            }
            return $out;

        case 'plans':
            $fields = $opts['fields'] ?? [];
            if (!$fields) return '';
            $out = "<div class=\"plans-grid\">\n";
            foreach ($fields as $f) {
                $t = htmlspecialchars($f['title'] ?? '');
                $p = htmlspecialchars((string)($f['price'] ?? ''));
                $desc = htmlspecialchars($f['description'] ?? '');
                $out .= "<div class=\"plan-card\"><div class=\"plan-title\">$t</div>";
                if ($desc) $out .= "<div class=\"plan-desc\">$desc</div>";
                $out .= "<div class=\"plan-price\">$p</div></div>\n";
            }
            return $out . "</div>";

        default:
            return '';
    }
}

function getLinkIconSvg(string $icon): string {
    static $paths = [
        'link'     => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.1-1.1m-.757-4.9a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'telegram' => 'M21 5L2 12.5l7 1M21 5l-2.5 15-9.5-6.5M21 5L9.5 13.5m0 0v5.5l3-3',
        'whatsapp' => 'M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z',
        'phone'    => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        'email'    => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'globe'    => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        'camera'   => 'M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2zM12 17a4 4 0 100-8 4 4 0 000 8z',
        'youtube'  => 'M22.54 6.42a2.78 2.78 0 00-1.95-1.96C18.88 4 12 4 12 4s-6.88 0-8.59.46a2.78 2.78 0 00-1.95 1.96A29 29 0 001 12a29 29 0 00.46 5.58A2.78 2.78 0 003.41 19.6C5.12 20 12 20 12 20s6.88 0 8.59-.46a2.78 2.78 0 001.95-1.95A29 29 0 0023 12a29 29 0 00-.46-5.58zM9.75 15.02V8.98L15.5 12l-5.75 3.02z',
        'star'     => 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
        'layout'   => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 00-1 1v6a1 1 0 001 1h4a1 1 0 001-1v-6a1 1 0 00-1-1h-4z',
        'layers'   => 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
        'code'     => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
        'book'     => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
        'zap'      => 'M13 10V3L4 14h7v7l9-11h-7z',
        'heart'    => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
        'check'    => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'puzzle'   => 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
    ];
    $path = $paths[$icon] ?? '';
    if (!$path) return '';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" data-mode="stroke"><path d="'
         . htmlspecialchars($path) . '"/></svg>';
}

function getMediaIconSvg(string $icon): string {
    // strip "tabler/" prefix if present
    $key = str_starts_with($icon, 'tabler/') ? substr($icon, 7) : $icon;
    static $paths = [
        'phone'          => 'M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2',
        'mail'           => 'M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM3 7l9 6 9-6',
        'map-pin'        => 'M12 11m-3 0a3 3 0 106 0 3 3 0 00-6 0M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z',
        'clock'          => 'M12 3a9 9 0 100 18A9 9 0 0012 3zM12 7v5l3 3',
        'calendar'       => 'M4 7a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7zM16 3v4M8 3v4M4 11h16M11 15h1M12 15v3',
        'star'           => 'M12 17.75l-6.172 3.245 1.179-6.873-4.993-4.867 6.9-1.002L12 2.5l3.086 5.753 6.9 1.002-4.993 4.867 1.179 6.873z',
        'heart'          => 'M19.5 12.572l-7.5 7.428-7.5-7.428A5 5 0 1112 5.006a5 5 0 017.5 7.566',
        'check'          => 'M5 12l5 5L20 7',
        'check-circle'   => 'M12 12m-9 0a9 9 0 1018 0 9 9 0 00-18 0M9 12l2 2 4-4',
        'home'           => 'M5 12H3l9-9 9 9H18v6a2 2 0 01-2 2H8a2 2 0 01-2-2v-6',
        'user'           => 'M8 7a4 4 0 108 0 4 4 0 00-8 0M6 21v-2a4 4 0 014-4h4a4 4 0 014 4v2',
        'users'          => 'M9 7a4 4 0 108 0 4 4 0 00-8 0M3 21v-2a4 4 0 014-4h4M16 11a4 4 0 110 8M21 21v-2a4 4 0 00-3-3.85',
        'settings'       => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM12 15a3 3 0 100-6 3 3 0 000 6z',
        'zap'            => 'M13 3L4 14h7v7l9-11h-7z',
        'info'           => 'M12 9h.01M11 12h1v4h1M12 3a9 9 0 100 18A9 9 0 0012 3',
        'shield'         => 'M12 3l7 4v5c0 4.418-3.134 8.573-7 9.93C8.134 20.573 5 16.418 5 12V7l7-4z',
        'gift'           => 'M3 9h18v2H3zM12 9V5M8 9a4 4 0 010-8h.5A3.5 3.5 0 0112 4.5M16 9a4 4 0 000-8h-.5A3.5 3.5 0 0112 4.5M5 11v8a2 2 0 002 2h10a2 2 0 002-2v-8',
        'truck'          => 'M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h11v12H5zM9 17a2 2 0 104 0 2 2 0 00-4 0M15 17a2 2 0 104 0 2 2 0 00-4 0M15 5h6l2 4H15',
        'camera'         => 'M5 7h1a2 2 0 002-2 1 1 0 011-1h6a1 1 0 011 1 2 2 0 002 2h1a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9a2 2 0 012-2M9 13a3 3 0 106 0 3 3 0 00-6 0',
        'book'           => 'M3 19a9 9 0 019 0 9 9 0 019 0M3 6a9 9 0 019 0 9 9 0 019 0M3 6v13M12 6v13M21 6v13',
        'link'           => 'M10 14a3.5 3.5 0 005 0l4-4a3.5 3.5 0 00-5-5l-1.5 1.5M14 10a3.5 3.5 0 00-5 0l-4 4a3.5 3.5 0 005 5l1.5-1.5',
        'lock'           => 'M5 11V7a7 7 0 0114 0v4M5 11h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2zM12 16v.01',
        'award'          => 'M12 8m-6 0a6 6 0 1012 0 6 6 0 00-12 0M8.21 13.89L7 23l5-3 5 3-1.21-9.12',
        'thumb-up'       => 'M7 11v8a1 1 0 01-1 1H4a1 1 0 01-1-1v-7a1 1 0 011-1h2.172a2 2 0 001.414-.586l3-3A2 2 0 0114 8h.172a2 2 0 011.789 1.106l.5 1A2 2 0 0118.25 11H20a2 2 0 012 2 2 2 0 01-.188.836l-2.75 5.5A2 2 0 0117.25 20H11a2 2 0 01-2-2v-1a2 2 0 00-2-2',
        'message'        => 'M4 6a2 2 0 012-2h12a2 2 0 012 2v9a2 2 0 01-2 2H6l-4 4V6',
        'alert-circle'   => 'M12 3a9 9 0 100 18A9 9 0 0012 3zM12 8v4M12 16v.01',
        'alert-octagon'  => 'M8.56 2h6.88l4.56 4.56v6.88L15.44 18H8.56L4 13.44V6.56L8.56 2zM12 8v4M12 16v.01',
        '2fa'            => 'M7 16h-4l3.47-4.66A2 2 0 007 8H3M11 8h4a1 1 0 011 1v2a1 1 0 01-1 1h-3a1 1 0 00-1 1v2a1 1 0 001 1h4M16 8v8',
    ];
    $path = $paths[$key] ?? '';
    if (!$path) return '';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="' . htmlspecialchars($path) . '"/></svg>';
}

function videoEmbed(string $url): string {
    if (preg_match('/youtube\.com\/watch\?.*v=([a-zA-Z0-9_\-]+)/i', $url, $m)
        || preg_match('/youtu\.be\/([a-zA-Z0-9_\-]+)/i', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    if (preg_match('/vimeo\.com\/(\d+)/i', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return '';
}
