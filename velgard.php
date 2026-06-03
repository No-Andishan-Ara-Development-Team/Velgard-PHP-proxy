<?php
function resolveRelativeUrl($baseUrl, $relativeUrl) {
    $parsedBase = parse_url($baseUrl);
    $scheme = $parsedBase['scheme'] . '://' . $parsedBase['host'];
    $path = isset($parsedBase['path']) ? rtrim(dirname($parsedBase['path']), '/') : '';

    if (preg_match('#^https?://#', $relativeUrl)) {
        return $relativeUrl;
    } elseif (substr($relativeUrl, 0, 1) === '/') {
        return $scheme . '/' . ltrim($relativeUrl, '/');
    } else {
        return $scheme . $path . '/' . $relativeUrl;
    }
}
function fetchUrl($targetUrl) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
    curl_setopt($ch, CURLOPT_HEADER, true); // شامل هدرها
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headers = substr($response, 0, $headerSize);
    $content = substr($response, $headerSize);
    if (strpos($headers, 'text/html') !== false) {
        header('Content-Type: text/html; charset=utf-8');
    } elseif (strpos($headers, 'application/javascript') !== false) {
        header('Content-Type: application/javascript; charset=utf-8');
    } elseif (strpos($headers, 'text/css') !== false) {
        header('Content-Type: text/css; charset=utf-8');
    } elseif (strpos($headers, 'image/') !== false) {
        header('Content-Type: ' . curl_getinfo($ch, CURLINFO_CONTENT_TYPE));
    }
    return $content;
}
function rewriteUrls($html, $proxyScript, $baseUrl) {
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//a[@href] | //img[@src] | //script[@src] | //link[@href] | //form[@action] | //iframe[@src]');
    foreach ($nodes as $node) {
        $attr = match(true) {
            $node->hasAttribute('href') => 'href',
            $node->hasAttribute('src') => 'src',
            $node->hasAttribute('action') => 'action',
            default => null,
        };
        if (!$attr) continue;
        $original = $node->getAttribute($attr);
        if (str_contains($original, 'data:') || str_contains($original, '#')) continue;
        $fullUrl = resolveRelativeUrl($baseUrl, $original);
        $proxiedUrl = $proxyScript . '?url=' . urlencode($fullUrl);
        $node->setAttribute($attr, $proxiedUrl);
    }
    return $dom->saveHTML();
}
$proxyScript = basename($_SERVER['PHP_SELF']);
$url = $_GET['url'] ?? '';
if (!empty($url)) {
    if (!preg_match('#^https?://#', $url)) {
        $url = 'http://' . $url;
    }
    $html = fetchUrl($url);
    $rewrittenHtml = rewriteUrls($html, $proxyScript, $url);
    header("Content-Security-Policy: default-src * 'unsafe-inline' 'unsafe-eval'; script-src * 'unsafe-inline' 'unsafe-eval'; connect-src *; img-src * data:; style-src * 'unsafe-inline';");
    echo $rewrittenHtml;
    exit; } ?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <title>ولگرد</title>
    <style>
        body {
            direction: rtl;
            font-family: Tahoma, sans-serif;
            background: #1f1f2e;
            color: #fff;
            padding: 30px;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: #2b2b3e;
            padding: 20px;
            border-radius: 12px;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
        }
        button {
            margin-top: 10px;
            padding: 10px 24px;
            background: #00ffae;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #00cc90;
        }
        hr {
            margin: 25px 0;
            border-color: #444;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>ولگرد</h2>
        <form method="get">
            <label>آدرس سایتی که میخوای واردش بشی:</label>
            <input type="text" name="url" placeholder="مثلاً https://example.com " required>
            <button type="submit">رفتن</button>
        </form>
    </div>
</body>
</html>
