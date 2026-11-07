<?php
/**
 * Дані каталогу для сторінок товарів (/zapchastyny/...).
 * Беремо з того ж API, що й каталог, і кешуємо на годину у файлі поза public_html.
 * Якщо API недоступний — віддаємо останній збережений кеш.
 */

const EP_SITE = 'https://www.europartswest.com.ua';
const EP_API_HOST = 'api.europartswest.com.ua';
const EP_API = 'http://api.europartswest.com.ua/public/api';
const EP_CACHE_TTL = 3600;

function ep_fetch($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? $body : null;
    }
    $body = @file_get_contents($url);
    return $body === false ? null : $body;
}

function ep_img($url) {
    if (!is_string($url) || $url === '') return '';
    $p = parse_url($url);
    if (!empty($p['host']) && strcasecmp($p['host'], EP_API_HOST) !== 0) {
        return preg_replace('#^http://#i', 'https://', $url);
    }
    return '/catalog.php?img=' . rawurlencode($p['path'] ?? '');
}

/** Транслітерація для адрес сторінок (така ж у Card-parts.js). */
function ep_slug($text) {
    $map = ['а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g','д'=>'d','е'=>'e','є'=>'ie','ж'=>'zh','з'=>'z','и'=>'y','і'=>'i','ї'=>'i','й'=>'i',
            'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'kh','ц'=>'ts','ч'=>'ch',
            'ш'=>'sh','щ'=>'shch','ь'=>'','ю'=>'iu','я'=>'ia','ы'=>'y','э'=>'e','ё'=>'e','ъ'=>''];
    $t = mb_strtolower((string)$text, 'UTF-8');
    $t = strtr($t, $map);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    $t = trim($t, '-');
    if (strlen($t) > 70) $t = rtrim(substr($t, 0, 70), '-');
    return $t;
}

function ep_url($p) {
    $slug = ep_slug($p['name'] . ' ' . $p['article']);
    return '/zapchastyny/' . $p['id'] . ($slug !== '' ? '-' . $slug : '');
}

/** Усі товари: ['cats' => [id => name], 'products' => [id => [...]]] */
function ep_catalog() {
    $cache = dirname($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . '/..') . '/europarts-catalog-cache.json';
    $old = is_file($cache) ? json_decode((string)@file_get_contents($cache), true) : null;
    if ($old && (time() - (int)($old['time'] ?? 0)) < EP_CACHE_TTL) return $old;

    $list = json_decode((string)ep_fetch(EP_API . '/categories'), true);
    if (!is_array($list)) return $old ?: ['cats' => [], 'products' => []];
    if (isset($list['data']) && is_array($list['data'])) $list = $list['data'];

    $cats = []; $products = [];
    foreach ($list as $c) {
        if (empty($c['id'])) continue;
        $cats[(int)$c['id']] = trim((string)($c['name'] ?? ''));
        $full = json_decode((string)ep_fetch(EP_API . '/categories/' . (int)$c['id']), true);
        if (!is_array($full) || empty($full['products'])) continue;
        foreach ($full['products'] as $p) {
            if (empty($p['id']) || trim((string)($p['name'] ?? '')) === '') continue;
            $products[(int)$p['id']] = [
                'id'       => (int)$p['id'],
                'name'     => trim((string)$p['name']),
                'article'  => trim((string)($p['article'] ?? '')),
                'cat'      => (int)$c['id'],
                'img'      => ep_img($p['image_url'] ?? ($p['image'] ?? '')),
                'updated'  => substr((string)($p['updated_at'] ?? ''), 0, 10),
                'brand'    => trim((string)($p['brand'] ?? '')),
                'desc'     => trim((string)($p['description'] ?? '')),
            ];
        }
    }
    if (!$products && $old) return $old;
    $data = ['time' => time(), 'cats' => $cats, 'products' => $products];
    @file_put_contents($cache, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $data;
}

function ep_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
