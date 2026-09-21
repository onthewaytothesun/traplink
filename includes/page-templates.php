<?php
require_once __DIR__ . '/page-design.php';

final class PageTemplateService {
    private PDO $db;
    private array $presets;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->presets = require __DIR__ . '/page-template-presets.php';
    }

    public function ensureSchema(): void {
        $suffix = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';
        $this->db->exec("CREATE TABLE IF NOT EXISTS tap_templates (
            id varchar(64) NOT NULL PRIMARY KEY,
            title varchar(255) NOT NULL,
            description TEXT NOT NULL,
            snapshot LONGTEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )" . $suffix);
    }

    private function query(string $sql, array $args = []): PDOStatement {
        $q = $this->db->prepare($sql);
        $q->execute($args);
        return $q;
    }

    private function json($value): string {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    private function decode($value): array {
        if (is_array($value)) return $value;
        return json_decode($value ?: '{}', true, 512, JSON_THROW_ON_ERROR) ?: [];
    }

    private function metadata(array $template): array {
        $snapshot = $template['snapshot'];
        return [
            'id'=>$template['id'], 'title'=>$template['title'],
            'description'=>$template['description'], 'kind'=>$template['kind'],
            'block_count'=>count($snapshot['blocks']), 'section_count'=>count($snapshot['sections']),
        ];
    }

    public function list(): array {
        $result = [];
        foreach ($this->presets as $template) $result[] = $this->metadata($template);
        $rows = $this->query('SELECT * FROM tap_templates ORDER BY created_at DESC, id')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $row['snapshot'] = $this->decode($row['snapshot']);
            $row['kind'] = 'custom';
            $result[] = $this->metadata($row);
        }
        return $result;
    }

    public function get(string $id): array {
        if (isset($this->presets[$id])) return $this->presets[$id];
        $row = $this->query('SELECT * FROM tap_templates WHERE id=?', [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new OutOfBoundsException('Шаблон не найден. Обновите список.');
        $row['snapshot'] = $this->decode($row['snapshot']);
        $row['kind'] = 'custom';
        return $row;
    }

    public function save(string $pageId, string $title, string $description): array {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 255 || mb_strlen($description) > 1000) {
            throw new InvalidArgumentException('Укажите название до 255 символов и описание до 1000 символов.');
        }
        $this->db->beginTransaction();
        try {
            $page = $this->query('SELECT * FROM tap_pages WHERE id=?', [$pageId])->fetch(PDO::FETCH_ASSOC);
            if (!$page) throw new OutOfBoundsException('Исходная страница не найдена.');
            $settings = $this->query('SELECT setting_key, setting_value FROM tap_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            $theme = $this->decode($page['theme'] ?? '{}');
            $theme['_template_design'] = array_intersect_key(resolvePageDesign($settings, $page), pageDesignKeys());
            unset($theme['_template_source']);
            $snapshot = [
                'version'=>1,
                'page'=>['id'=>$page['id'], 'title'=>$page['title'], 'slug'=>$page['slug'], 'theme'=>$theme],
                'sections'=>$this->query('SELECT id, title, sort_order, options FROM tap_sections WHERE page_id=? ORDER BY sort_order, id', [$pageId])->fetchAll(PDO::FETCH_ASSOC),
                'blocks'=>$this->query('SELECT id, section_id, block_type_id, block_type_name, options, is_visible, sort_order, anchor FROM tap_blocks WHERE page_id=? ORDER BY sort_order, id', [$pageId])->fetchAll(PDO::FETCH_ASSOC),
            ];
            foreach ($snapshot['sections'] as &$section) $section['options'] = $this->decode($section['options']);
            unset($section);
            foreach ($snapshot['blocks'] as &$block) $block['options'] = $this->decode($block['options']);
            unset($block);
            $id = 'tpl-' . bin2hex(random_bytes(12));
            $this->query('INSERT INTO tap_templates (id,title,description,snapshot) VALUES (?,?,?,?)', [$id,$title,trim($description),$this->json($snapshot)]);
            $this->db->commit();
            return $this->metadata(['id'=>$id,'title'=>$title,'description'=>trim($description),'kind'=>'custom','snapshot'=>$snapshot]);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function rewrite($value, array $ids, string $oldSlug, string $newSlug) {
        if (is_array($value)) {
            foreach ($value as $key=>$child) $value[$key] = $this->rewrite($child,$ids,$oldSlug,$newSlug);
            return $value;
        }
        if (!is_string($value)) return $value;
        if (isset($ids[$value])) return $ids[$value];
        if ($oldSlug !== '' && preg_match('~^/p/' . preg_quote($oldSlug,'~') . '([?#].*)?$~D', $value, $match)) {
            return '/p/' . $newSlug . ($match[1] ?? '');
        }
        return $value;
    }

    private function existing(string $id, string $templateId): ?array {
        $page = $this->query('SELECT * FROM tap_pages WHERE id=?', [$id])->fetch(PDO::FETCH_ASSOC);
        if (!$page) return null;
        if (($this->decode($page['theme'])['_template_source'] ?? '') !== $templateId) {
            throw new InvalidArgumentException('Этот запрос уже использован для другого шаблона.');
        }
        return $page;
    }

    public function create(string $templateId, string $requestId): array {
        if (!preg_match('/^[a-f0-9]{32}$/D', $requestId)) throw new InvalidArgumentException('Неверный идентификатор запроса.');
        $id = 'p-' . $requestId;
        if ($existing = $this->existing($id, $templateId)) return $existing;
        $template = $this->get($templateId);
        $snapshot = $template['snapshot'];
        $slug = 'page-' . $requestId;
        $ids = [$snapshot['page']['id']=>$id];
        foreach ($snapshot['sections'] as $s) $ids[$s['id']] = 's-' . bin2hex(random_bytes(12));
        foreach ($snapshot['blocks'] as $b) $ids[$b['id']] = bin2hex(random_bytes(12));
        $theme = $snapshot['page']['theme'];
        $theme['_template_source'] = $templateId;
        $this->db->beginTransaction();
        try {
            $order = (int)$this->query('SELECT COALESCE(MAX(sort_order),0) FROM tap_pages')->fetchColumn() + 10;
            $this->query('INSERT INTO tap_pages (id,title,slug,is_main,sort_order,folder_id,theme) VALUES (?,?,?,0,?,NULL,?)', [$id,$template['title'],$slug,$order,$this->json($theme)]);
            foreach ($snapshot['sections'] as $s) {
                $options = $this->rewrite($s['options'],$ids,$snapshot['page']['slug'],$slug);
                $this->query('INSERT INTO tap_sections (id,page_id,title,sort_order,options) VALUES (?,?,?,?,?)', [$ids[$s['id']],$id,$s['title'],(int)$s['sort_order'],$this->json($options)]);
            }
            foreach ($snapshot['blocks'] as $b) {
                $options = $this->rewrite($b['options'],$ids,$snapshot['page']['slug'],$slug);
                $sectionId = !empty($b['section_id']) ? ($ids[$b['section_id']] ?? null) : null;
                $this->query('INSERT INTO tap_blocks (id,page_id,section_id,block_type_id,block_type_name,options,is_visible,sort_order,anchor) VALUES (?,?,?,?,?,?,?,?,?)', [$ids[$b['id']],$id,$sectionId,(int)$b['block_type_id'],$b['block_type_name'],$this->json($options),(int)$b['is_visible'],(int)$b['sort_order'],$b['anchor'] ?? null]);
            }
            $page = $this->query('SELECT * FROM tap_pages WHERE id=?', [$id])->fetch(PDO::FETCH_ASSOC);
            $this->db->commit();
            return $page;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            // Concurrent requests with one token may race on the page primary key.
            if ($e instanceof PDOException && in_array((string)$e->getCode(), ['23000','23505'], true)) {
                if ($existing = $this->existing($id, $templateId)) return $existing;
            }
            throw $e;
        }
    }

    public function saveDesign(string $pageId, array $design): void {
        $this->db->beginTransaction();
        try {
            $lock = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $page = $this->query('SELECT theme FROM tap_pages WHERE id=?' . $lock, [$pageId])->fetch(PDO::FETCH_ASSOC);
            if (!$page) throw new OutOfBoundsException('Страница не найдена.');
            $theme = $this->decode($page['theme']);
            $values = array_filter(array_intersect_key($design,pageDesignKeys()), 'is_scalar');
            $theme['_template_design'] = array_replace($theme['_template_design'] ?? [], $values);
            $this->query('UPDATE tap_pages SET theme=? WHERE id=?', [$this->json($theme),$pageId]);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(string $id): void {
        if (isset($this->presets[$id])) throw new InvalidArgumentException('Предустановленные шаблоны нельзя удалить.');
        $this->query('DELETE FROM tap_templates WHERE id=?', [$id]);
    }
}
