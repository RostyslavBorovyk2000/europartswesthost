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

/** Усі товари: ['cats' => [id => name], 'products' => [id => [...]]]
 *  Швидко: якщо кеш є — віддаємо його одразу; застарілий кеш оновлюємо ПІСЛЯ відповіді відвідувачу. */
function ep_catalog() {
    $cache = dirname($_SERVER['DOCUMENT_ROOT'] ?: __DIR__ . '/..') . '/europarts-catalog-cache.json';
    $old = is_file($cache) ? json_decode((string)@file_get_contents($cache), true) : null;
    if ($old && !empty($old['products'])) {
        if ((time() - (int)($old['time'] ?? 0)) >= EP_CACHE_TTL) {
            register_shutdown_function(function () use ($cache) {
                if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
                $lock = $cache . '.lock';
                if (is_file($lock) && time() - filemtime($lock) < 120) return; // уже оновлює інший запит
                @touch($lock);
                ep_build_catalog($cache);
                @unlink($lock);
            });
        }
        return $old;
    }
    return ep_build_catalog($cache) ?: ['cats' => [], 'products' => []];
}

/** Паралельні запити до API (усі категорії одночасно). */
function ep_fetch_many(array $urls) {
    if (!function_exists('curl_multi_init')) return array_map('ep_fetch', $urls);
    $mh = curl_multi_init(); $hs = [];
    foreach ($urls as $k => $u) {
        $h = curl_init($u);
        curl_setopt_array($h, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
        curl_multi_add_handle($mh, $h); $hs[$k] = $h;
    }
    do { $st = curl_multi_exec($mh, $run); if ($run) curl_multi_select($mh, 1.0); } while ($run && $st == CURLM_OK);
    $out = [];
    foreach ($hs as $k => $h) {
        $code = curl_getinfo($h, CURLINFO_HTTP_CODE);
        $out[$k] = ($code >= 200 && $code < 300) ? curl_multi_getcontent($h) : null;
        curl_multi_remove_handle($mh, $h); curl_close($h);
    }
    curl_multi_close($mh);
    return $out;
}

function ep_build_catalog($cache) {
    $list = json_decode((string)ep_fetch(EP_API . '/categories'), true);
    if (!is_array($list)) return null;
    if (isset($list['data']) && is_array($list['data'])) $list = $list['data'];

    $cats = []; $urls = [];
    foreach ($list as $c) {
        if (empty($c['id'])) continue;
        $cats[(int)$c['id']] = trim((string)($c['name'] ?? ''));
        $urls[(int)$c['id']] = EP_API . '/categories/' . (int)$c['id'];
    }
    $products = [];
    foreach (ep_fetch_many($urls) as $cid => $body) {
        $full = json_decode((string)$body, true);
        if (!is_array($full) || empty($full['products'])) continue;
        foreach ($full['products'] as $p) {
            if (empty($p['id']) || trim((string)($p['name'] ?? '')) === '') continue;
            $products[(int)$p['id']] = [
                'id'       => (int)$p['id'],
                'name'     => trim((string)$p['name']),
                'article'  => trim((string)($p['article'] ?? '')),
                'cat'      => (int)$cid,
                'img'      => ep_img($p['image_url'] ?? ($p['image'] ?? '')),
                'updated'  => substr((string)($p['updated_at'] ?? ''), 0, 10),
                'brand'    => trim((string)($p['brand'] ?? '')),
                'desc'     => trim((string)($p['description'] ?? '')),
            ];
        }
    }
    if (!$products) return null;
    $data = ['time' => time(), 'cats' => $cats, 'products' => $products];
    @file_put_contents($cache . '.tmp', json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @rename($cache . '.tmp', $cache);
    return $data;
}

function ep_e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
