<?php
// Карта сторінок товарів для Google: /zapchastyny/sitemap.xml
require __DIR__ . '/_lib.php';
$data = ep_catalog();
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($data['products'] as $p) {
    echo '  <url><loc>' . ep_e(EP_SITE . ep_url($p)) . '</loc>'
       . (preg_match('/^\d{4}-\d{2}-\d{2}$/', $p['updated']) ? '<lastmod>' . $p['updated'] . '</lastmod>' : '')
       . "</url>\n";
}
echo "</urlset>\n";
