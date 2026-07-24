<?php
declare(strict_types=1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

define('API_VERSION', '1.2.0');

$jsonInput = json_decode((string) file_get_contents('php://input'), true);
if (is_array($jsonInput)) {
    $_REQUEST = array_merge($_REQUEST, $jsonInput);
    $_POST = array_merge($_POST, $jsonInput);
}

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/api/index.php')), '/') . '/';
$path = str_starts_with($requestPath, $basePath) ? substr($requestPath, strlen($basePath)) : '';
$pathParts = array_values(array_filter(explode('/', trim($path, '/')), static fn(string $part): bool => $part !== ''));
$endpoint = $pathParts[0] ?? ($_REQUEST['endpoint'] ?? '');
$action = $pathParts[1] ?? ($_REQUEST['action'] ?? '');

switch ($endpoint) {
    case '': sendSuccess(['version' => API_VERSION, 'endpoints' => ['catalog', 'stats', 'preferences', 'automation', 'users', 'contributions', 'ads']], 'API service is healthy'); break;
    case 'catalog': require __DIR__ . '/v_catalog.php'; break;
    case 'stats': require __DIR__ . '/v_stats.php'; break;
    case 'preferences': require __DIR__ . '/v_preferences.php'; break;
    case 'automation': require __DIR__ . '/v_automation.php'; break;
    case 'users': require __DIR__ . '/v_users.php'; break;
    case 'contributions': require __DIR__ . '/v_contributions.php'; break;
    case 'ads': require __DIR__ . '/v_ads.php'; break;
    case 'help': sendSuccess(['version' => API_VERSION, 'endpoints' => ['catalog', 'stats', 'preferences', 'automation', 'users']], 'API service is healthy'); break;
    default: sendError('Endpoint not found: ' . ($endpoint ?: '(empty)'), 404);
}

function sendSuccess(array $data = [], string $message = ''): never { sendResponse(['success' => true, 'data' => $data, 'message' => $message], 200); }
function sendError(string $message, int $status = 400): never { sendResponse(['success' => false, 'message' => $message], $status); }
function sendResponse(array $payload, int $status): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
