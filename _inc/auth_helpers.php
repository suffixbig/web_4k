<?php

/**
 * 認證輔助函數
 * 提供用戶會話驗證功能
 */


/**
 * 驗證用戶會話 (供其他 API 使用)
 *
 * @param PDO $conn 資料庫連線物件
 * @param string|null $token 認證 token
 * @return array|false 成功則回傳用戶資料，失敗則回傳 false
 */
function verifyUserSession($conn, $token)
{
    if (!$token) {
        return false;
    }

    try {
        $sql = "
            SELECT u.id, u.username, u.email, u.name, u.is_tester, u.total_chant_count, u.last_chant_date, u.consecutive_chant_days, u.card_draw_chances, s.expires_at 
            FROM users u 
            JOIN user_sessions s ON u.id = s.user_id 
            WHERE s.session_token = ? AND s.expires_at > NOW() AND u.is_active = 1
        ";
        return assoc_sql1p($conn, $sql, [$token]);

    } catch (PDOException $e) {
        // 記錄錯誤或進行其他處理
        // error_log("Error verifying user session: " . $e->getMessage());
        return false;
    }
}

/**
 * 驗證用戶並回傳用戶資料，如果未授權則發送錯誤回應
 *
 * @param PDO $conn 資料庫連線物件
 * @return array 用戶資料
 */
function authenticateUser($conn)
{
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

    if (!$token) {
        sendError('Authorization token required', 401);
    }

    $user = verifyUserSession($conn, $token);

    if (!$user) {
        sendError('Invalid or expired token', 401);
    }

    return $user;
}

/**
 * API 回應處理函數
 */
function sendResponse($data, $status_code = 200)
{
    http_response_code($status_code);
    header("Content-Type: application/json; charset=utf-8");

    // 設定 CORS 標頭
    header("Access-Control-Allow-Origin: *"); // 允許所有來源
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 錯誤回應函數
 */
function sendError($message, $status_code = 400)
{
    sendResponse(["success" => false, "message" => $message], $status_code);
}

/**
 * 成功回應函數
 */
function sendSuccess($data = [], $message = "Success")
{
    sendResponse([
        "success" => true,
        "message" => $message,
        "data" => $data
    ]);
}

/**
 * 驗證 JSON 輸入
 */
function getJsonInput()
{
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        sendError("Invalid JSON input");
    }

    return $data;
}

/**
 * 生成隨機 token
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * 密碼雜湊
 */
function hashPassword($password)
{
    return md5($password);
}

/**
 * 驗證密碼
 */
function verifyPassword($password, $hash)
{
    return md5($password) === $hash;
}

/**
 * 驗證 email 格式
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 清理輸入資料
 */
function sanitizeInput($data)
{
    if (is_array($data)) {
        return array_map("sanitizeInput", $data);
    }
    // 檢查是否為字串，如果不是，則直接返回原始值
    if (!is_string($data)) {
        return $data;
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, "UTF-8");
}

/**
 * 資料庫連線類別-測試資料庫連線
 */
class Database
{
    private $connection;

    public function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
        }
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
