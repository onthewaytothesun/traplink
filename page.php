<?php
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
    $seoTitle = trim($theme['seo_title'] ?? '');
    $title = htmlspecialchars($seoTitle ?: $page['title']);
    $seoDescription = trim($theme['seo_description'] ?? '');
    $theme = getGlobalTheme($pdo);
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
<link rel="stylesheet" href="/assets/taplink-frontend.css">
<style>
:root {
  --theme-screen-background: <?= htmlspecialchars($screenBg) ?>;
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
body { font-family: <?= $pageFont ? "'" . $pageFont . "'," : '' ?>-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: var(--theme-screen-background); color: var(--theme-text-color); line-height: 1.6; }
.page-container { max-width: 640px; margin: 0 auto; }
.block-form{display:flex;flex-direction:column;gap:12px}
.form-field{display:flex;flex-direction:column;gap:5px}
.form-field label{font-size:13px;opacity:.65}
.form-field input{padding:10px 14px;border:1.5px solid rgba(128,128,128,.25);border-radius:var(--theme-link-border-radius,8px);background:rgba(128,128,128,.07);color:inherit;font-size:15px;font-family:inherit;outline:none;transition:border-color .2s}
.form-field input:focus{border-color:var(--theme-link-background)}
.block-form button[type=submit]{padding:12px;border:none;border-radius:var(--theme-link-border-radius,8px);background:var(--theme-link-background);color:var(--theme-link-title-color);font-size:15px;font-weight:600;cursor:pointer;transition:opacity .15s;font-family:inherit;width:100%}
.block-form button[type=submit]:disabled{opacity:.6;cursor:default}
.form-success{text-align:center;padding:20px 0;font-size:15px;opacity:.65}
</style>
<link rel="stylesheet" href="/assets/blocks.css">
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
    $headCode = trim($theme['head_code'] ?? '');
    if ($headCode !== '') {
        echo $headCode . "\n";
    }
?>
</head>
<body>
<div class="page-container">
<?php
    // Group visible blocks by section_id (null → own group)
    $groups = [];
    foreach ($blocks as $block) {
        if (!$block['is_visible']) continue;
        $key = $block['section_id'] ?: '';
        $groups[$key][] = $block;
    }
    foreach ($groups as $groupBlocks):
        echo "<section class=\"section-main\">\n<div>\n<div>\n";
        foreach ($groupBlocks as $block) {
            $opts = is_array($block['options']) ? $block['options'] : (json_decode($block['options'] ?? '{}', true) ?: []);
            $name = $block['block_type_name'];
            if ($name === 'form') {
                $opts['_block_id'] = $block['id'];
                $opts['_page_id']  = $block['page_id'] ?? '';
            }
            $html = renderBlock($name, $opts);
            if ($html !== '') {
                $wrapCls    = blockWrapClass($name, $opts);
                $anchorAttr = $block['anchor'] ? ' id="' . htmlspecialchars($block['anchor']) . '"' : '';
                echo "<div class=\"$wrapCls\"$anchorAttr>$html</div>\n";
            }
        }
        echo "</div>\n</div>\n</section>\n";
    endforeach;
    ?>
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
      if(d.ok){form.innerHTML='<p class="form-success">Спасибо! Заявка отправлена.</p>';}
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
            $text  = nl2br(htmlspecialchars($opts['text'] ?? ''));
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
            $rawUrl   = $opts['value'] ?? '#';
            $url = match($action) {
                'telegram' => str_starts_with($rawUrl, 'http') ? $rawUrl : 'https://t.me/' . ltrim($rawUrl, '@/ '),
                'phone'    => str_starts_with($rawUrl, 'tel:') ? $rawUrl : 'tel:' . preg_replace('/[^+0-9]/', '', $rawUrl),
                'email'    => str_starts_with($rawUrl, 'mailto:') ? $rawUrl : 'mailto:' . $rawUrl,
                'page'     => '/p/' . preg_replace('/[^a-z0-9\-]/', '', strtolower($rawUrl)),
                default    => $rawUrl ?: '#',
            };
            $url = htmlspecialchars($url);
            if (!$title) return '';
            $iconSvg = ($icon && $icon !== 'none') ? getLinkIconSvg($icon) : '';
            $styleClass = $style !== 'one' ? " style-$style" : '';
            $blockVars = [];
            $cbg = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $opts['custom_bg'] ?? '');
            if ($cbg) { $blockVars[] = "--block-link-background:$cbg"; $blockVars[] = "--block-link-border-color:$cbg"; }
            $cc = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $opts['custom_color'] ?? '');
            if ($cc) { $blockVars[] = "--block-link-title-color:$cc"; $blockVars[] = "--block-link-subtitle-color:$cc"; }
            if (isset($opts['custom_radius']) && $opts['custom_radius'] !== '') $blockVars[] = "--block-link-border-radius:" . max(0,min(100,(int)$opts['custom_radius'])) . "px";
            if (isset($opts['custom_border_width']) && $opts['custom_border_width'] !== '') { $bw = max(0,min(10,(int)$opts['custom_border_width'])); $blockVars[] = "--block-link-border-width:{$bw}px"; $blockVars[] = "--block-link-border-width-offset:{$bw}px"; }
            $cborderColor = preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $opts['custom_border_color'] ?? '');
            if ($cborderColor) $blockVars[] = "--block-link-border-color:$cborderColor";
            $align = in_array($opts['text_align'] ?? '', ['left','center','right'], true) ? $opts['text_align'] : '';
            if ($align) $blockVars[] = "text-align:$align";
            $blockStyle = $blockVars ? ' style="' . htmlspecialchars(implode(';', $blockVars)) . '"' : '';
            if ($iconSvg || $subtitle) {
                $thumbHtml = $iconSvg
                    ? "<div class=\"thumb\"><div class=\"is-icon\">$iconSvg</div></div>"
                    : '';
                $textHtml  = "<div><span class=\"btn-link-title\">$title</span>"
                           . ($subtitle ? "<span class=\"btn-link-subtitle\">$subtitle</span>" : '')
                           . "</div>";
                return "<a href=\"$url\" class=\"btn-link with-thumb$styleClass\"$blockStyle>$thumbHtml$textHtml</a>";
            }
            return "<a href=\"$url\" class=\"btn-link$styleClass\"$blockStyle><span class=\"btn-link-title\">$title</span></a>";

        case 'break':
            $h = (int)($opts['height'] ?? 20);
            $s = htmlspecialchars($opts['style'] ?? 'none');
            return "<div class=\"break-line style-$s\" style=\"height:{$h}px\"></div>";

        case 'video':
            $url = $opts['url'] ?? '';
            $embed = videoEmbed($url);
            if (!$embed) return '';
            return "<iframe src=\"" . htmlspecialchars($embed) . "\" allowfullscreen allow=\"autoplay\"></iframe>";

        case 'html':
        case 'zero':
            $html = $opts['html'] ?? '';
            return $html;

        case 'banner':
            $pic  = htmlspecialchars($opts['picture'] ?? '');
            $link = htmlspecialchars($opts['link'] ?? '');
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
            $date = htmlspecialchars($opts['date'] ?? '');
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
                $b = nl2br(htmlspecialchars($f['text'] ?? ''));
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
                $out .= "<div class=\"media-item\"><div class=\"media-item-text\"><div class=\"title\">$t</div><div class=\"text\">$b</div></div></div>\n";
            }
            return $out;

        case 'pricing':
            $fields = $opts['fields'] ?? [];
            if (!$fields) return '';
            $out = '';
            foreach ($fields as $f) {
                $t = htmlspecialchars($f['title'] ?? '');
                $p = htmlspecialchars((string)($f['price'] ?? ''));
                $out .= "<div class=\"pricing-item\"><span>$t</span><span class=\"price\">$p</span></div>\n";
            }
            return $out;

        case 'messenger':
            $items = $opts['items'] ?? [];
            if (!$items) return '';
            $labels = ['telegram'=>'Telegram','whatsapp'=>'WhatsApp','viber'=>'Viber','instagram'=>'Instagram','vk'=>'ВКонтакте'];
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
                    default     => "#"
                };
                $cls = in_array($m, ['telegram','whatsapp','viber','instagram','vk']) ? $m : 'default';
                $out .= "<a href=\"$url\" class=\"messenger-btn $cls\">$lbl</a>\n";
            }
            return $out;

        case 'form':
            $fields  = $opts['fields'] ?? [];
            $btnText = htmlspecialchars($opts['form_btn'] ?? 'Отправить');
            $blockId = htmlspecialchars($opts['_block_id'] ?? '');
            $pageId  = htmlspecialchars($opts['_page_id']  ?? '');
            if (!$fields || !$blockId) return '';
            $typeMap  = [3 => 'text', 5 => 'tel', 6 => 'email'];
            $phMap    = [3 => 'Имя', 5 => '+7 (___) ___-__-__', 6 => 'email@example.com'];
            $out  = "<form class=\"block-form\">\n";
            $out .= "<input type=\"hidden\" name=\"block_id\" value=\"$blockId\">\n";
            $out .= "<input type=\"hidden\" name=\"page_id\" value=\"$pageId\">\n";
            foreach ($fields as $field) {
                $tid  = (int)($field['type_id'] ?? 3);
                $type = $typeMap[$tid] ?? 'text';
                $lbl  = htmlspecialchars($field['title'] ?? '');
                $key  = 'field_' . ($field['idx'] ?? 0);
                $req  = !empty($field['required']) ? ' required' : '';
                $ph   = htmlspecialchars($phMap[$tid] ?? '');
                $out .= "<div class=\"form-field\">";
                if ($lbl) $out .= "<label>$lbl</label>";
                $out .= "<input type=\"$type\" name=\"$key\" placeholder=\"$ph\"$req>";
                $out .= "</div>\n";
            }
            $out .= "<button type=\"submit\">$btnText</button>\n";
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
