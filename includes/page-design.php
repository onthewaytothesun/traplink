<?php
// Only presentation settings belong to a reusable page, never site scripts or integrations.
function pageDesignKeys(): array {
    return array_fill_keys(['screen','text_color','link_bg','link_color','link_radius','link_border_width','link_border_color','link_shadow','link_shadow_color','page_font'], true);
}
function resolvePageDesign(array $global, array $page): array {
    $theme = is_array($page['theme'] ?? null) ? $page['theme'] : (json_decode($page['theme'] ?? '{}', true) ?: []);
    $overrides = is_array($theme['_template_design'] ?? null) ? $theme['_template_design'] : [];
    $result = array_replace($global, array_intersect_key($overrides, pageDesignKeys()));
    if (!empty($theme['_template_preview'])) unset($result['head_code']);
    return $result;
}
function getPageTheme(PDO $pdo, array $page): array {
    return resolvePageDesign(getGlobalTheme($pdo), $page);
}
