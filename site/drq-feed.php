<?php
// Only this explicitly approved post may be exposed. Never return Graph's raw response.
ini_set('display_errors', '0');
umask(0077);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); exit; }
$private = '/home/aldebara/drq-private';
$postId = '109709817601324_1719423730192457';
function respond($data) { echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function safeUrl($value, $image = false) {
    if (!is_string($value)) return false;
    $p = parse_url($value);
    if (!$p || ($p['scheme'] ?? '') !== 'https' || isset($p['user']) || isset($p['pass'])) return false;
    $host = strtolower($p['host'] ?? '');
    return $image ? (bool)preg_match('/(^|\.)fbcdn\.net$/', $host) : in_array($host, ['facebook.com', 'www.facebook.com'], true);
}
if (!function_exists('curl_init')) respond(['posts' => []]);
$lock = @fopen($private . '/feed.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) respond(['posts' => []]);
@chmod($private . '/feed.lock', 0600);
$cacheFile = $private . '/approved-feed-cache.json';
$cache = json_decode((string)@file_get_contents($cacheFile), true);
if (is_array($cache) && ($cache['post_id'] ?? '') === $postId && time() - ($cache['at'] ?? 0) < ($cache['ttl'] ?? 0)) respond($cache['payload']);
$payload = ['posts' => []];
$token = trim((string)@file_get_contents($private . '/facebook-page-token.txt'));
if ($token !== '' && !preg_match('/[\r\n]/', $token)) {
    $fields = 'id,message,created_time,permalink_url,attachments{media,type}';
    $ch = curl_init('https://graph.facebook.com/v26.0/' . $postId . '?fields=' . rawurlencode($fields));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8, CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token], CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $post = is_string($raw) ? json_decode($raw, true) : null;
    if ($status === 200 && is_array($post) && ($post['id'] ?? '') === $postId && safeUrl($post['permalink_url'] ?? null)) {
        foreach (($post['attachments']['data'] ?? []) as $attachment) {
            $src = $attachment['media']['image']['src'] ?? null;
            if (($attachment['type'] ?? '') === 'photo' && safeUrl($src, true) && !empty($post['message'])) {
                $payload['posts'][] = ['id' => $postId, 'message' => $post['message'], 'url' => $post['permalink_url'], 'image' => $src];
                break;
            }
        }
    }
}
// Short cache limits API traffic; failed requests never serve stale posts.
$encoded = json_encode(['post_id' => $postId, 'at' => time(), 'ttl' => empty($payload['posts']) ? 60 : 300, 'payload' => $payload]);
@file_put_contents($cacheFile, $encoded, LOCK_EX);
@chmod($cacheFile, 0600);
respond($payload);
