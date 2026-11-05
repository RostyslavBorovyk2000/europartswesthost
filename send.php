<?php
/**
 * Прийом заявок з форм сайту (той самий домен, https).
 * Куди йде заявка:
 *   1) Telegram — якщо є файл налаштувань поза public_html: /home/<user>/europarts-config.php
 *        <?php return ['tg_token' => 'ТОКЕН_БОТА', 'tg_chats' => ['CHAT_ID_1', 'CHAT_ID_2']];
 *      (токен у репозиторій не кладемо — репозиторій публічний)
 *   2) Пошта europartswest1@gmail.com — через наявний API, а якщо він недоступний — через mail().
 *   3) Завжди дописується в журнал поза public_html: /home/<user>/europarts-leads.log
 *      — щоб жодна заявка не загубилась, навіть якщо пошта і Telegram не спрацювали.
 */

date_default_timezone_set('Europe/Kyiv');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function post_json($url, $payload) {
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 300;
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content' => $body,
        'timeout' => 10,
        'ignore_errors' => true,
    ]]);
    $res = @file_get_contents($url, false, $ctx);
    return $res !== false && isset($http_response_header[0]) && preg_match('#\s2\d\d\s#', $http_response_header[0]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(['ok' => false, 'error' => 'method'], 405);

// Заявки приймаємо лише з власного сайту
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? '')) {
    reply(['ok' => false, 'error' => 'origin'], 403);
}

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) $in = $_POST;

$clean = function ($v, $n) {
    $v = trim(strip_tags((string)($v ?? '')));
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $v);
    return function_exists('mb_substr') ? mb_substr($v, 0, $n) : substr($v, 0, $n);
};

$name    = $clean($in['name'] ?? '', 100);
$phone   = $clean($in['phone'] ?? '', 40);
$message = $clean($in['message'] ?? '', 2000);
$page    = $clean($in['page'] ?? '', 200);

// Боти: заповнюють форму миттєво
if (isset($in['elapsed']) && (int)$in['elapsed'] < 1500) reply(['ok' => true]);

$digits = preg_replace('/\D/', '', $phone);
if (strlen($name) < 2 || strlen($digits) < 9 || strlen($digits) > 13) {
    reply(['ok' => false, 'error' => 'invalid'], 422);
}

$text = "Нова заявка з сайту europartswest.com.ua\n"
      . "Ім'я: $name\n"
      . "Телефон: $phone\n"
      . ($message !== '' ? "Повідомлення: $message\n" : '')
      . "Сторінка: $page\n"
      . 'Час: ' . date('d.m.Y H:i');

$home = dirname(__DIR__);
@file_put_contents($home . '/europarts-leads.log', str_replace("\n", ' | ', $text) . PHP_EOL, FILE_APPEND | LOCK_EX);

$delivered = false;

// 1) Telegram
$cfgFile = $home . '/europarts-config.php';
if (is_file($cfgFile)) {
    $cfg = include $cfgFile;
    if (!empty($cfg['tg_token']) && !empty($cfg['tg_chats'])) {
        foreach ((array)$cfg['tg_chats'] as $chat) {
            if (post_json('https://api.telegram.org/bot' . $cfg['tg_token'] . '/sendMessage', [
                'chat_id' => $chat,
                'text' => $text,
                'disable_web_page_preview' => true,
            ])) $delivered = true;
        }
    }
}

// 2) Пошта через наявний API (сервер-сервер), запасний варіант — mail()
$mailed = post_json('http://api.europartswest.com.ua/public/api/send-email', [
    'to_email' => 'europartswest1@gmail.com',
    'subject'  => 'Заявка з сайту: ' . $name . ', ' . $phone,
    'message'  => $text,
]);
if (!$mailed) {
    $mailed = @mail(
        'europartswest1@gmail.com',
        '=?UTF-8?B?' . base64_encode('Заявка з сайту: ' . $name) . '?=',
        $text,
        "Content-Type: text/plain; charset=UTF-8\r\nFrom: site@europartswest.com.ua"
    );
}
if ($mailed) $delivered = true;

reply($delivered ? ['ok' => true] : ['ok' => false, 'error' => 'delivery'], $delivered ? 200 : 502);
