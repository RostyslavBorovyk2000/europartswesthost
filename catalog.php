<?php
/**
 * Каталог запчастин через той самий домен (https).
 * Браузер блокує запити з https-сайту на http://api..., тому каталог раніше не відкривався.
 * Цей файл бере дані з API на сервері й віддає їх сайту.
 *   /catalog.php            — список категорій
 *   /catalog.php?id=5       — категорія з товарами
 *   /catalog.php?img=/path  — картинка товару з API
 */

const API_HOST = 'api.europartswest.com.ua';
const API_BASE = 'http://api.europartswest.com.ua/public/api';

function fetch_url($url, &$type = null) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => ['Accept: application/json, image/*'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        return ($body !== false && $code >= 200 && $code < 300) ? $body : null;
    }
    $body = @file_get_contents($url);
    foreach ($http_response_header ?? [] as $h) {
        if (stripos($h, 'Content-Type:') === 0) $type = trim(substr($h, 13));
    }
    return $body === false ? null : $body;
}

// Картинка товару
if (isset($_GET['img'])) {
    $path = '/' . ltrim((string)$_GET['img'], '/');
    if (strpos($path, '..') !== false || !preg_match('#^/[\w\-./%]+\.(jpe?g|png|webp|gif)$#i', $path)) {
        http_response_code(400);
        exit;
    }
    $type = null;
    $data = fetch_url('http://' . API_HOST . $path, $type);
    if ($data === null || stripos((string)$type, 'image/') !== 0) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: ' . $type);
    header('Cache-Control: public, max-age=604800');
    echo $data;
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$json = fetch_url($id > 0 ? API_BASE . '/categories/' . $id : API_BASE . '/categories');
if ($json === null) {
    http_response_code(502);
    echo json_encode(['error' => 'catalog_unavailable']);
    exit;
}

// Посилання на картинки переводимо на цей же домен
$data = json_decode($json, true);
$rewrite = function ($url) {
    if (!is_string($url) || $url === '') return $url;
    $p = parse_url($url);
    if (!empty($p['host']) && strcasecmp($p['host'], API_HOST) !== 0) {
        return preg_replace('#^http://#i', 'https://', $url);
    }
    return '/catalog.php?img=' . rawurlencode($p['path'] ?? '');
};
array_walk_recursive($data, function (&$v, $k) use ($rewrite) {
    if (in_array($k, ['image_url', 'image', 'img'], true)) $v = $rewrite($v);
});

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
