<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/auth.php';

$user_id = require_login();

/**
 * Best-effort Open Graph preview fetch. Returns [title, image, site].
 * Safe-ish: https/http only, short timeout, size cap. Failures = empty preview.
 */
function fetch_og(string $url): array {
    $out = ['title' => '', 'image' => '', 'site' => ''];
    if (!preg_match('#^https?://#i', $url)) return $out;

    $html = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; HaparlamentBot/1.0)',
            CURLOPT_RANGE          => '0-262144', // first 256KB
        ]);
        $html = (string)curl_exec($ch);
        $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 6, 'user_agent' => 'Mozilla/5.0 (compatible; HaparlamentBot/1.0)']]);
        $html = (string)@file_get_contents($url, false, $ctx, 0, 262144);
        $final = $url;
    }
    if ($html === '') return $out;

    $meta = function(string $prop) use ($html): string {
        foreach (["property", "name"] as $attr) {
            if (preg_match('#<meta[^>]+' . $attr . '=["\']' . preg_quote($prop, '#') . '["\'][^>]+content=["\']([^"\']*)["\']#i', $html, $m)) return $m[1];
            if (preg_match('#<meta[^>]+content=["\']([^"\']*)["\'][^>]+' . $attr . '=["\']' . preg_quote($prop, '#') . '["\']#i', $html, $m)) return $m[1];
        }
        return '';
    };

    $title = $meta('og:title') ?: $meta('twitter:title');
    if (!$title && preg_match('#<title[^>]*>([^<]*)</title>#i', $html, $m)) $title = trim($m[1]);
    $image = $meta('og:image') ?: $meta('twitter:image');
    $site  = $meta('og:site_name');

    // resolve relative image against final host
    if ($image && !preg_match('#^https?://#i', $image)) {
        $p = parse_url($final);
        if (!empty($p['scheme']) && !empty($p['host'])) {
            $base = $p['scheme'] . '://' . $p['host'];
            $image = ($image[0] === '/') ? $base . $image : $base . '/' . $image;
        }
    }
    if (!$site) {
        $p = parse_url($final);
        $site = $p['host'] ?? '';
        $site = preg_replace('#^www\.#', '', (string)$site);
    }

    $out['title'] = mb_substr(html_entity_decode($title, ENT_QUOTES, 'UTF-8'), 0, 200);
    $out['image'] = mb_substr($image, 0, 500);
    $out['site']  = mb_substr(html_entity_decode($site, ENT_QUOTES, 'UTF-8'), 0, 120);
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? 'add';

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);
        $db = get_db();
        $stmt = $db->prepare("DELETE FROM suggestions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        json_response(['ok' => true]);
    }

    $name = trim($body['name'] ?? '');
    $url  = trim($body['url']  ?? '');
    $note = trim($body['note'] ?? '');

    if (strlen($url) < 1)     json_error('הדביקו קישור למקום');
    if (strlen($url) > 500)   json_error('קישור ארוך מדי');
    if (strlen($name) > 200)  json_error('שם ארוך מדי');
    if (strlen($note) > 500)  json_error('הערה ארוכה מדי');
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . $url;
    }

    $db = get_db();
    $cnt = $db->prepare("SELECT COUNT(*) FROM suggestions WHERE user_id = ?");
    $cnt->execute([$user_id]);
    if ((int)$cnt->fetchColumn() >= MAX_SUGGESTIONS_PER_USER) {
        json_error('הגעת למקסימום ' . MAX_SUGGESTIONS_PER_USER . ' הצעות');
    }

    // best-effort preview
    $og = fetch_og($url);
    if (!$name) $name = $og['title'];

    $stmt = $db->prepare("INSERT INTO suggestions (user_id, name, url, note, og_image, og_site) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $url, $note, $og['image'], $og['site']]);
    json_response(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $db = get_db();
    $rows = $db->query("SELECT * FROM suggestions ORDER BY created_at DESC")->fetchAll();

    $suggestions = array_map(function($s) use ($user_id) {
        return [
            'id'    => (int)$s['id'],
            'name'  => $s['name'],
            'url'   => $s['url'],
            'note'  => $s['note'],
            'image' => $s['og_image'] ?? '',
            'site'  => $s['og_site'] ?? '',
            'by'    => USERS[$s['user_id']]['name'] ?? $s['user_id'],
            'mine'  => $s['user_id'] === $user_id,
        ];
    }, $rows);

    $myCount = 0;
    foreach ($rows as $r) if ($r['user_id'] === $user_id) $myCount++;

    json_response([
        'suggestions' => $suggestions,
        'my_count'    => $myCount,
        'max'         => MAX_SUGGESTIONS_PER_USER,
    ]);
}

json_error('Method not allowed', 405);
