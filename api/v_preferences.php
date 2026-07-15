<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Taipei');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$dataFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'user-preferences.json';
$catalogFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'json' . DIRECTORY_SEPARATOR . 'wallpapers.json';
$catalog = json_decode((string) @file_get_contents($catalogFile), true);
$validIds = array_values(array_filter(array_map(
    static fn(array $wallpaper): string => (string) ($wallpaper['id'] ?? ''),
    is_array($catalog['wallpapers'] ?? null) ? $catalog['wallpapers'] : []
)));

function output(array $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
function client_id(mixed $value): string {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value) ?? '';
    return substr($id, 0, 80);
}
function defaults(): array {
    return [
        'favorites' => [],
        'playlist' => [],
        'schedule' => ['weekday_mode' => 'weekly', 'time' => '06:00', 'weekly_day' => 1],
        'current_wallpaper_id' => null,
        'playlist_cursor' => 0,
    ];
}
function public_user(array $user): array {
    unset($user['last_run']);
    $user['rules'] = [
        'weekday_source' => 'user_playlist',
        'weekday_modes' => ['never', 'weekly', 'daily'],
        'weekly_change_day' => 'monday',
        'change_time' => '06:00',
        'weekend_source' => 'system_recommendation',
        'weekend_override_locked' => true,
    ];
    return $user;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = $method === 'POST' ? json_decode(file_get_contents('php://input') ?: '{}', true) : $_GET;
if (!is_array($input)) output(['ok' => false, 'error' => 'JSON 格式錯誤'], 400);
$clientId = client_id($input['client_id'] ?? '');
if ($clientId === '') output(['ok' => false, 'error' => '缺少 client_id'], 422);

$handle = fopen($dataFile, 'c+');
if ($handle === false || !flock($handle, $method === 'POST' ? LOCK_EX : LOCK_SH)) output(['ok' => false, 'error' => '偏好服務忙碌中'], 503);
rewind($handle);
$store = json_decode(stream_get_contents($handle) ?: '{}', true);
if (!is_array($store)) $store = ['updated_at' => date(DATE_ATOM), 'users' => []];
if (!isset($store['users']) || !is_array($store['users'])) $store['users'] = [];
$user = array_replace_recursive(defaults(), $store['users'][$clientId] ?? []);

if ($method === 'GET') {
    flock($handle, LOCK_UN); fclose($handle);
    output(['ok' => true, 'profile' => public_user($user)]);
}
if ($method !== 'POST') {
    flock($handle, LOCK_UN); fclose($handle);
    output(['ok' => false, 'error' => '不支援的請求方式'], 405);
}

$action = (string) ($input['action'] ?? '');
$id = (string) ($input['id'] ?? '');
if (in_array($action, ['favorite_toggle', 'playlist_add', 'playlist_remove'], true) && !in_array($id, $validIds, true)) {
    flock($handle, LOCK_UN); fclose($handle); output(['ok' => false, 'error' => '無效的桌布 ID'], 422);
}

switch ($action) {
    case 'favorite_toggle':
        $index = array_search($id, $user['favorites'], true);
        if ($index === false) $user['favorites'][] = $id;
        else array_splice($user['favorites'], $index, 1);
        break;
    case 'playlist_add':
        if (!in_array($id, $user['playlist'], true)) $user['playlist'][] = $id;
        break;
    case 'playlist_remove':
        $user['playlist'] = array_values(array_filter($user['playlist'], fn($value) => $value !== $id));
        $user['playlist_cursor'] = min((int) $user['playlist_cursor'], max(0, count($user['playlist']) - 1));
        break;
    case 'playlist_save':
        $ids = array_map('strval', is_array($input['ids'] ?? null) ? $input['ids'] : []);
        $user['playlist'] = array_values(array_unique(array_filter($ids, fn($value) => in_array($value, $validIds, true))));
        $user['playlist_cursor'] = min((int) $user['playlist_cursor'], max(0, count($user['playlist']) - 1));
        break;
    case 'schedule_set':
        $mode = (string) ($input['weekday_mode'] ?? '');
        if (!in_array($mode, ['never', 'weekly', 'daily'], true)) {
            flock($handle, LOCK_UN); fclose($handle); output(['ok' => false, 'error' => '排程只能是 never、weekly 或 daily'], 422);
        }
        $user['schedule'] = ['weekday_mode' => $mode, 'time' => '06:00', 'weekly_day' => 1];
        break;
    case 'current_set':
        if ($id !== '' && !in_array($id, $validIds, true)) {
            flock($handle, LOCK_UN); fclose($handle); output(['ok' => false, 'error' => '無效的桌布 ID'], 422);
        }
        $user['current_wallpaper_id'] = $id === '' ? null : $id;
        break;
    default:
        flock($handle, LOCK_UN); fclose($handle); output(['ok' => false, 'error' => '不支援的偏好動作'], 422);
}

$store['users'][$clientId] = $user;
$store['updated_at'] = date(DATE_ATOM);
rewind($handle); ftruncate($handle, 0);
$written = fwrite($handle, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
fflush($handle); flock($handle, LOCK_UN); fclose($handle);
if ($written === false) output(['ok' => false, 'error' => '偏好寫入失敗'], 500);
output(['ok' => true, 'profile' => public_user($user)]);
