<?php
/**
 * Прийом заявок з форм сайту (той самий домен, https).
 * Куди йде заявка:
 *   1) Telegram — якщо є файл налаштувань поза public_html: /home/<user>/europarts-config.php
 *        <?php return ['tg_token' => 'ТОКЕН_БОТА', 'tg_chats' => ['CHAT_ID_1', 'CHAT_ID_2']];
 *      (токен у репозиторій не кладемо — репозиторій публічний)
 *   2) Пошта europartswest1@gmail.com — через mail() хостингу (від site@europartswest.com.ua), запасний — API.
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

// HTML-лист у стилі сайту: темний фон, акцент Manitou (червоний) або JCB (жовтий)
function lead_mail_body($name, $phone, $message, $page, $text, $boundary) {
    $e = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
    $jcb = stripos($message . ' ' . $page, 'JCB') !== false;
    $acc = $jcb ? '#F2B300' : '#C00000';
    $onAcc = $jcb ? '#141413' : '#ffffff';
    $brand = $jcb ? 'JCB' : 'Manitou';

    $digits = preg_replace('/\D/', '', $phone);
    if (strlen($digits) === 10 && $digits[0] === '0') $digits = '38' . $digits;
    if (strlen($digits) === 9) $digits = '380' . $digits;
    $tel = '+' . $digits;

    $pageTitle = $page; $pageUrl = '';
    if (preg_match('#^(.*)\s—\s(/\S*)$#u', $page, $m)) { $pageTitle = $m[1]; $pageUrl = 'https://www.europartswest.com.ua' . $m[2]; }

    $row = function ($label, $value) {
        return '<tr><td style="padding:12px 0;border-bottom:1px solid #2a2a28;color:#8d877e;font-size:13px;width:120px;vertical-align:top">' . $label . '</td>'
             . '<td style="padding:12px 0;border-bottom:1px solid #2a2a28;color:#F2EFEA;font-size:15px;vertical-align:top">' . $value . '</td></tr>';
    };
    $rows = $row('Ім’я', '<b>' . $e($name) . '</b>')
          . $row('Телефон', '<a href="tel:' . $e($tel) . '" style="color:#F2EFEA;text-decoration:none"><b>' . $e($phone) . '</b></a>')
          . ($message !== '' ? $row('Запит', nl2br($e($message))) : '')
          . $row('Сторінка', $pageUrl ? '<a href="' . $e($pageUrl) . '" style="color:#DAA640;text-decoration:none">' . $e($pageTitle) . '</a>' : $e($pageTitle))
          . $row('Час', date('d.m.Y, H:i'));

    $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width"></head>'
      . '<body style="margin:0;padding:0;background:#0e0e0d">'
      . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#0e0e0d;padding:24px 12px"><tr><td align="center">'
      . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#151514;border:1px solid #2e2e2b;border-radius:18px;overflow:hidden;font-family:Manrope,Segoe UI,Arial,sans-serif">'
      . '<tr><td style="height:4px;background:' . $acc . ';font-size:0;line-height:0">&nbsp;</td></tr>'
      . '<tr><td style="padding:22px 28px 0">'
      .   '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
      .   '<td style="color:#F2EFEA;font-size:13px;letter-spacing:2px;font-weight:800">ЄВРО ПАРТС ЗАХІД</td>'
      .   '<td align="right"><span style="display:inline-block;padding:5px 12px;border-radius:20px;background:' . $acc . ';color:' . $onAcc . ';font-size:12px;font-weight:800">' . $brand . '</span></td>'
      .   '</tr></table></td></tr>'
      . '<tr><td style="padding:22px 28px 6px;color:#F2EFEA;font-size:26px;font-weight:800;line-height:1.2">Нова заявка з сайту</td></tr>'
      . '<tr><td style="padding:0 28px 14px;color:#8d877e;font-size:14px">Клієнт чекає на дзвінок — передзвоніть якнайшвидше.</td></tr>'
      . '<tr><td style="padding:0 28px"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $rows . '</table></td></tr>'
      . '<tr><td style="padding:24px 28px 28px" align="center">'
      .   '<a href="tel:' . $e($tel) . '" style="display:block;background:' . $acc . ';color:' . $onAcc . ';text-decoration:none;font-weight:800;font-size:17px;padding:16px;border-radius:12px">📞 Подзвонити ' . $e($phone) . '</a>'
      . '</td></tr>'
      . '<tr><td style="padding:14px 28px;border-top:1px solid #2a2a28;color:#5f5a53;font-size:12px">Лист надіслано автоматично з форми на <a href="https://www.europartswest.com.ua/" style="color:#8d877e">europartswest.com.ua</a></td></tr>'
      . '</table></td></tr></table></body></html>';

    return "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
         . chunk_split(base64_encode($text))
         . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
         . chunk_split(base64_encode($html))
         . "--$boundary--\r\n";
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

// 2) Пошта: напряму через пошту хостингу від site@europartswest.com.ua (SPF/DKIM домену).
//    Лист у стилі сайту (HTML) + текстова версія. API api.europartswest.com.ua — лише запасний варіант.
$subjectText = 'Заявка з сайту: ' . $name . ', ' . $phone;
$mailed = @mail(
    'europartswest1@gmail.com',
    '=?UTF-8?B?' . base64_encode($subjectText) . '?=',
    lead_mail_body($name, $phone, $message, $page, $text, $boundary = 'ep' . md5(uniqid('', true))),
    "MIME-Version: 1.0\r\n"
    . "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n"
    . "From: =?UTF-8?B?" . base64_encode('Сайт Євро Партс Захід') . "?= <site@europartswest.com.ua>\r\n"
    . "Reply-To: site@europartswest.com.ua",
    '-fsite@europartswest.com.ua'
);
if (!$mailed) {
    $mailed = post_json('http://api.europartswest.com.ua/public/api/send-email', [
        'to_email' => 'europartswest1@gmail.com',
        'subject'  => $subjectText,
        'message'  => $text,
    ]);
}
if ($mailed) $delivered = true;

reply($delivered ? ['ok' => true] : ['ok' => false, 'error' => 'delivery'], $delivered ? 200 : 502);
