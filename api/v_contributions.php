<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function contributionReply(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    contributionReply(['ok' => false, 'error' => '請使用 POST 提交。'], 405);
}

$title = trim((string) ($_POST['title'] ?? ''));
$creator = trim((string) ($_POST['creator'] ?? ''));
$category = trim((string) ($_POST['category'] ?? ''));
$colors = trim((string) ($_POST['colors'] ?? ''));
$file = $_FILES['wallpaper'] ?? null;

if ($title === '' || $creator === '' || !$file || (int) $file['error'] !== UPLOAD_ERR_OK) {
    contributionReply(['ok' => false, 'error' => '請填寫作品名稱、作者並上傳 JPG 圖片。'], 422);
}

if ((int) $file['size'] > 20 * 1024 * 1024 || !@getimagesize((string) $file['tmp_name'])) {
    contributionReply(['ok' => false, 'error' => '圖片必須是 20 MB 以下的有效 JPG 檔案。'], 422);
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
if ($mime !== 'image/jpeg') {
    contributionReply(['ok' => false, 'error' => '桌布只接受 JPG 格式。'], 422);
}

$dir = dirname(__DIR__) . '/uploads/pending';
if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
    contributionReply(['ok' => false, 'error' => '無法建立投稿目錄。'], 500);
}

$id = 'contribution-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));
$name = $id . '.jpg';
if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
    contributionReply(['ok' => false, 'error' => '無法儲存投稿圖片。'], 500);
}

$log = dirname(__DIR__) . '/json/contributions.json';
$data = json_decode((string) @file_get_contents($log), true);
if (!is_array($data)) {
    $data = ['contributions' => []];
}
$data['contributions'][] = [
    'id' => $id,
    'title' => $title,
    'creator' => $creator,
    'category' => $category,
    'colors' => $colors,
    'file' => '/uploads/pending/' . $name,
    'status' => 'pending',
    'submitted_at' => date(DATE_ATOM),
];
file_put_contents($log, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

contributionReply(['ok' => true, 'message' => '投稿已送出，審核通過後才會公開。', 'id' => $id]);
