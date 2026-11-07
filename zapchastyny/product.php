<?php
/**
 * Сторінка товару: /zapchastyny/<id>-<назва-артикул>
 * Дані — з каталогу (адмінка api), шапка/підвал — zapchastyny/_layout.html (генерує tools/build_site.py).
 */
require __DIR__ . '/_lib.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$data = ep_catalog();
$p = $data['products'][$id] ?? null;

if (!$p) {
    http_response_code(404);
    readfile(__DIR__ . '/../404.html');
    exit;
}

// Одна адреса на товар: якщо назва в адресі застаріла — 301 на правильну
$canon = ep_url($p);
$reqPath = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
if (rawurldecode($reqPath) !== $canon) {
    header('Location: ' . EP_SITE . $canon, true, 301);
    exit;
}

$cat = $data['cats'][$p['cat']] ?? 'Запчастини';
$hay = $p['name'] . ' ' . $p['article'];
$brand = stripos($hay, 'jcb') !== false ? 'JCB' : (preg_match('/manitou|маніту/iu', $hay) ? 'Manitou' : 'Manitou / JCB');
$art = $p['article'];
$full = $p['name'] . ($art !== '' ? ', арт. ' . $art : '');

$title = $p['name'] . ($art !== '' ? ' ' . $art : '') . ' — ' . ($brand === 'Manitou / JCB' ? 'запчастини Маніту, JCB' : 'запчастина ' . $brand) . ' | Євро Партс Захід';
$desc = $p['name'] . ($art !== '' ? ', артикул ' . $art : '') . ' — запчастина для телескопічних навантажувачів Маніту (Manitou) та JCB. '
      . 'Розділ: ' . $cat . '. Ціна та наявність — за телефоном. Хмельницький, доставка по Україні.';

// Інші товари цієї категорії
$others = array_values(array_filter($data['products'], function ($o) use ($p) { return $o['cat'] === $p['cat'] && $o['id'] !== $p['id']; }));
$others = array_slice($others, 0, 8);

$img = $p['img'] !== '' ? $p['img'] : '/images/home/card-parts.jpg';
$imgAbs = (strpos($img, 'http') === 0 ? '' : EP_SITE) . $img;
$catUrl = '/page-parts/parts.html?categories=' . $p['cat'];

$ld = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Головна', 'item' => EP_SITE . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Каталог запчастин', 'item' => EP_SITE . '/page-parts/parts.html'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $cat, 'item' => EP_SITE . $catUrl],
        ['@type' => 'ListItem', 'position' => 4, 'name' => $p['name']],
    ],
];

$meta = "\n    <meta name=\"description\" content=\"" . ep_e($desc) . "\" />"
      . "\n    <link rel=\"canonical\" href=\"" . ep_e(EP_SITE . $canon) . "\" />"
      . "\n    <meta property=\"og:type\" content=\"product\" />"
      . "\n    <meta property=\"og:title\" content=\"" . ep_e($full) . "\" />"
      . "\n    <meta property=\"og:description\" content=\"" . ep_e($desc) . "\" />"
      . "\n    <meta property=\"og:url\" content=\"" . ep_e(EP_SITE . $canon) . "\" />"
      . "\n    <meta property=\"og:image\" content=\"" . ep_e($imgAbs) . "\" />"
      . "\n    <script type=\"application/ld+json\">" . json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "</script>";

$callMenu = '<div class="callpick-menu" role="menu"><a role="menuitem" href="tel:+380672808384"><b>Юрій</b><span>менеджер · +380 (67) 280 83 84</span></a>'
          . '<a role="menuitem" href="tel:+380983982533"><b>Олександр</b><span>директор · +380 (98) 398 25 33</span></a></div>';

ob_start();
$ico = [
  'search' => '<path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3"/>',
  'truck'  => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
  'check'  => '<path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
  'wrench' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/>',
];
$svg = function ($k) use ($ico) { return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $ico[$k] . '</svg>'; };
?>
      <section class="pdx">
        <div class="glowclip" aria-hidden="true"><div class="glow"></div></div>
        <div class="stripes" aria-hidden="true"></div>
        <?php if ($art !== ''): ?><div class="pdx-mark" aria-hidden="true"><?= ep_e($art) ?></div><?php endif; ?>
        <div class="wrap pdx-in">
          <div class="pdx-top in d1">
            <div class="crumbs pd-crumbs"><a href="/index.html">Головна</a><i>/</i><a href="/page-parts/parts.html">Каталог</a><i>/</i><a href="<?= ep_e($catUrl) ?>"><?= ep_e($cat) ?></a><i>/</i><span><?= ep_e($p['name']) ?></span></div>
            <div class="row" style="gap:10px">
              <button type="button" class="pill" data-brand="manitou" aria-pressed="true">MANITOU</button>
              <button type="button" class="pill" data-brand="jcb" aria-pressed="false">JCB LOADALL</button>
            </div>
          </div>
          <div class="pdx-grid">
            <div class="pdx-stage in d2">
              <div class="pdx-frame">
                <div class="pdx-photo">
                  <img src="<?= ep_e($img) ?>" alt="<?= ep_e($full) ?>" width="640" height="480" />
                  <div class="beam" aria-hidden="true"></div>
                </div>
              </div>
              <span class="pdx-tag"><?= ep_e($brand) ?></span>
              <span class="pdx-cat"><?= ep_e($cat) ?></span>
            </div>
            <div class="pdx-card in d3">
              <p class="eyebrow">ЗАПЧАСТИНА · <?= ep_e(mb_strtoupper($cat, 'UTF-8')) ?></p>
              <h1 class="pdx-title"><?= ep_e($p['name']) ?></h1>
              <?php if ($art !== ''): ?>
              <div class="pdx-art">
                <div><span>Артикул</span><b><?= ep_e($art) ?></b></div>
                <button type="button" class="pd-copy" data-copy="<?= ep_e($art) ?>" aria-label="Скопіювати артикул">Копіювати</button>
              </div>
              <?php endif; ?>
              <div class="pdx-price">
                <div>
                  <b>Ціна та наявність — за запитом</b>
                  <span>Менеджер перевірить склад і назве ціну на оригінал та аналог</span>
                </div>
              </div>
              <div class="pdx-btns">
                <button type="button" class="btn cta shine product-btn pdx-ask" data-product="<?= ep_e($full) ?>">Запитати ціну<span class="gl"></span></button>
                <details class="callpick"><summary class="btn btn-ghost">Зателефонувати</summary><?= $callMenu ?></details>
              </div>
              <div class="pdx-feats">
                <div><i><?= $svg('search') ?></i><span><b>Підбір за артикулом</b>або серійним номером техніки</span></div>
                <div><i><?= $svg('truck') ?></i><span><b>Нова пошта</b>по всій Україні</span></div>
                <div><i><?= $svg('check') ?></i><span><b>Оригінал або аналог</b>на ваш вибір і бюджет</span></div>
                <div><i><?= $svg('wrench') ?></i><span><b>Встановимо</b>виїзний сервіс Manitou і JCB</span></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="wrap pdx-steps rv">
        <div class="pdx-step"><b>01</b><h3>Залиште заявку</h3><p>Або зателефонуйте — назва й артикул уже у формі</p></div>
        <div class="pdx-step"><b>02</b><h3>Узгоджуємо ціну</h3><p>Перевіряємо наявність, пропонуємо оригінал або аналог</p></div>
        <div class="pdx-step"><b>03</b><h3>Відправляємо</h3><p>Новою поштою або самовивіз: Хмельницький, вул. Київська, 4</p></div>
      </section>

      <div class="pdx-bar" role="region" aria-label="Швидке замовлення">
        <button type="button" class="btn btn-red product-btn pdx-ask" data-product="<?= ep_e($full) ?>">Запитати ціну</button>
        <details class="callpick up"><summary class="btn btn-ghost pdx-bar-call" aria-label="Подзвонити"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg></summary><?= $callMenu ?></details>
      </div>
<?php if ($others): ?>
      <section class="wrap pd-more rv">
        <div class="car-head"><div><p class="eyebrow">ЦЕЙ ЖЕ РОЗДІЛ</p><h2 class="h2" style="margin:0">Інші запчастини: <?= ep_e($cat) ?></h2></div><a class="back" href="<?= ep_e($catUrl) ?>">Увесь розділ →</a></div>
        <div class="all-products">
<?php foreach ($others as $i => $o): $u = ep_url($o); ?>
          <article class="product" style="animation-delay:<?= $i * 40 ?>ms">
            <a class="pimg" href="<?= ep_e($u) ?>"><img src="<?= ep_e($o['img'] ?: '/images/home/card-parts.jpg') ?>" alt="<?= ep_e($o['name']) ?>" loading="lazy"></a>
            <div class="product-info">
              <h3 class="product-title"><a href="<?= ep_e($u) ?>"><?= ep_e($o['name']) ?></a></h3>
              <p class="product-price"><?= ep_e($o['article']) ?></p>
              <button type="button" class="product-btn">Замовити</button>
            </div>
          </article>
<?php endforeach; ?>
        </div>
      </section>
<?php endif;
$main = ob_get_clean();

$layout = (string)@file_get_contents(__DIR__ . '/_layout.html');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=600');
echo str_replace(['%%TITLE%%', '%%META%%', '%%MAIN%%'], [ep_e($title), $meta, $main], $layout);
