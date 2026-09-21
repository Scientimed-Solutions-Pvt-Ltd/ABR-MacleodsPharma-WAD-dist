<?php

declare(strict_types=1);

// The SPA is deployed separately from the Laravel API. This tiny PHP entry
// point lets social crawlers read live admin metadata before React runs.
$apiUrl = 'https://api-abr-rosumac-gold-wad.intechify.com/api/settings';
$settings = [];

$response = false;
if (function_exists('curl_init')) {
    $curl = curl_init($apiUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
} elseif (ini_get('allow_url_fopen')) {
    $response = @file_get_contents($apiUrl, false, stream_context_create([
        'http' => [
            'timeout' => 5,
            'header' => "Accept: application/json\r\n",
        ],
    ]));
}

if (is_string($response)) {
    $payload = json_decode($response, true);
    if (is_array($payload['data'] ?? null)) {
        $settings = $payload['data'];
    }
}

$escape = static fn (?string $value): string => htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
$title = (string) ($settings['site_name'] ?? $settings['meta_title'] ?? '');
$description = (string) ($settings['meta_description'] ?? '');
$keywords = (string) ($settings['meta_keywords'] ?? '');
$favicon = (string) ($settings['favicon_url'] ?? '');
$currentUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');

$scriptFiles = glob(__DIR__ . '/assets/index-*.js') ?: [];
$styleFiles = glob(__DIR__ . '/assets/index-*.css') ?: [];
$script = $scriptFiles[0] ?? null;
$style = $styleFiles[0] ?? null;

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="manifest" href="/manifest.json" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <?php if ($favicon !== ''): ?>
      <link rel="icon" href="<?= $escape($favicon) ?>" />
      <link rel="apple-touch-icon" href="<?= $escape($favicon) ?>" />
    <?php endif; ?>
    <meta name="description" content="<?= $escape($description) ?>" />
    <meta name="keywords" content="<?= $escape($keywords) ?>" />
    <meta property="og:title" content="<?= $escape($title) ?>" />
    <meta property="og:description" content="<?= $escape($description) ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?= $escape($currentUrl) ?>" />
    <?php if ($favicon !== ''): ?>
      <meta property="og:image" content="<?= $escape($favicon) ?>" />
    <?php endif; ?>
    <meta name="twitter:card" content="summary" />
    <meta name="theme-color" content="#663399" />
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-71CQRW9R0B"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-71CQRW9R0B');
    </script>
    <title><?= $escape($title) ?></title>
    <?php if ($style): ?><link rel="stylesheet" crossorigin href="/assets/<?= rawurlencode(basename($style)) ?>"><?php endif; ?>
  </head>
  <body>
    <div id="root"></div>
    <?php if ($script): ?><script type="module" crossorigin src="/assets/<?= rawurlencode(basename($script)) ?>"></script><?php endif; ?>
  </body>
</html>
