<?php
/**
 * 發文大師 - Session 持久化管理
 * 設定 Session Cookie 有效期為 60 天
 */
namespace wind_master;

// 60 天 = 60 * 24 * 60 * 60 = 5184,000 秒
$sessionLifetime = 60 * 24 * 60 * 60;

// 設定 Session Cookie 參數
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => '', // 預設為當前網域
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// 設定伺服器端 Session 資料保存時間
ini_set('session.gc_maxlifetime', $sessionLifetime);

// 啟動 Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
