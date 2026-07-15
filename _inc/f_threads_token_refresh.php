<?php
/**
 * Threads 長效權杖續期輔助函式
 */

if (!function_exists('threadsApiErrorMessage')) {
    function threadsApiErrorMessage($result, string $fallback = '未知錯誤'): string {
        if (is_string($result) && trim($result) !== '') return trim($result);
        if (!is_array($result)) return $fallback;

        $message = $result['error']['message'] ?? $result['message'] ?? $result['error_msg'] ?? '';
        if ((!$message || $message === 'Unknown Error') && isset($result['raw']) && is_array($result['raw'])) {
            $message = $result['raw']['error']['message'] ?? $result['raw']['message'] ?? $result['raw']['error_msg'] ?? '';
        }
        if (!$message) $message = $fallback;
        if (!empty($result['status'])) $message .= " (HTTP {$result['status']})";
        return $message;
    }
}

if (!function_exists('threadsRefreshExpiringTokens')) {
    function threadsRefreshExpiringTokens(PDO $conn, $tm, int $daysBeforeExpire = 14): array {
        $daysBeforeExpire = max(1, min(30, $daysBeforeExpire));
        $threshold = date('Y-m-d H:i:s', strtotime("+{$daysBeforeExpire} days"));
        $stats = ['checked' => 0, 'refreshed' => 0, 'failed' => 0, 'expired' => 0, 'errors' => []];

        $stats['expired'] = (int)(row_sql1p($conn, "SELECT COUNT(*) FROM th_threads_accounts WHERE access_token <> '' AND token_expires_at IS NOT NULL AND token_expires_at <= NOW()", 0) ?: 0);
        $accounts = assoc_sql_all($conn, "SELECT id, username, threads_user_id, access_token, token_expires_at FROM th_threads_accounts WHERE access_token <> '' AND token_expires_at IS NOT NULL AND token_expires_at > NOW() AND token_expires_at <= ? AND (updated_at IS NULL OR updated_at <= DATE_SUB(NOW(), INTERVAL 1 DAY)) ORDER BY token_expires_at ASC", [$threshold]);
        $stats['checked'] = count($accounts);

        foreach ($accounts as $account) {
            $label = '@' . ($account['username'] ?: $account['threads_user_id']);
            try {
                $result = $tm->refreshLongLivedToken((string)$account['access_token']);
                if (!empty($result['error'])) {
                    throw new Exception(threadsApiErrorMessage($result));
                }
                if (empty($result['access_token'])) {
                    throw new Exception('Meta 未回傳新的 access_token');
                }

                $expiresIn = (int)($result['expires_in'] ?? (60 * 24 * 60 * 60));
                $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
                mysql_up($conn, "UPDATE th_threads_accounts SET access_token = ?, token_expires_at = ?, updated_at = NOW() WHERE id = ?", [
                    $result['access_token'],
                    $expiresAt,
                    $account['id']
                ]);
                $stats['refreshed']++;
            } catch (Exception $e) {
                $stats['failed']++;
                $stats['errors'][] = "{$label}: " . $e->getMessage();
            }
        }

        return $stats;
    }
}
