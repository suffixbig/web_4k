<?php
/**
 * Instagram publishing helpers for scheduled posts.
 */

if (!function_exists('ig_publish_api_post')) {
    function ig_publish_api_post(string $url, array $params): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        if ($response === false) {
            throw new \Exception('Instagram API 請求失敗：' . $curlError);
        }

        $data = json_decode((string)$response, true);
        if ($httpCode >= 400 || isset($data['error'])) {
            $error = $data['error'] ?? [];
            $message = is_array($error)
                ? ($error['message'] ?? $error['error_user_msg'] ?? ('HTTP ' . $httpCode))
                : (string)$error;
            throw new \Exception($message);
        }

        return is_array($data) ? $data : [];
    }
}

if (!function_exists('ig_publish_api_get')) {
    function ig_publish_api_get(string $url, array $params): array {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        if ($response === false) {
            throw new \Exception('Instagram API 請求失敗：' . $curlError);
        }

        $data = json_decode((string)$response, true);
        if ($httpCode >= 400 || isset($data['error'])) {
            $error = $data['error'] ?? [];
            $message = is_array($error)
                ? ($error['message'] ?? $error['error_user_msg'] ?? ('HTTP ' . $httpCode))
                : (string)$error;
            throw new \Exception($message);
        }

        return is_array($data) ? $data : [];
    }
}

if (!function_exists('ig_publish_wait_container_ready')) {
    function ig_publish_wait_container_ready(string $apiVersion, string $containerId, string $accessToken, int $maxAttempts = 6, int $waitSeconds = 5): string {
        $maxAttempts = max(1, min(10, $maxAttempts));
        $waitSeconds = max(1, min(10, $waitSeconds));
        $statusCode = 'UNKNOWN';

        for ($i = 1; $i <= $maxAttempts; $i++) {
            if ($i > 1) {
                sleep($waitSeconds);
            }

            $status = ig_publish_api_get('https://graph.instagram.com/' . $apiVersion . '/' . rawurlencode($containerId), [
                'fields' => 'status_code,status',
                'access_token' => $accessToken,
            ]);
            $statusCode = strtoupper((string)($status['status_code'] ?? ''));

            if (in_array($statusCode, ['FINISHED', 'PUBLISHED'], true)) {
                return $statusCode;
            }
            if (in_array($statusCode, ['ERROR', 'EXPIRED'], true)) {
                throw new \Exception('IG 媒體容器處理失敗，狀態：' . $statusCode);
            }
        }

        throw new \Exception('IG 媒體容器尚未準備好，狀態：' . $statusCode);
    }
}

if (!function_exists('ig_publish_is_image_url')) {
    function ig_publish_is_image_url(string $url): bool {
        return (bool)preg_match('/\.(jpg|jpeg|png|webp)(\?|#|$)/i', $url);
    }
}

if (!function_exists('ig_publish_is_video_url')) {
    function ig_publish_is_video_url(string $url): bool {
        return (bool)preg_match('/\.(mp4|mov)(\?|#|$)/i', $url);
    }
}

if (!function_exists('ig_publish_build_caption')) {
    function ig_publish_build_caption(?string $title, string $content): string {
        $title = trim((string)$title);
        $content = trim($content);
        if ($title === '') return $content;
        return trim($title . "\n\n" . $content);
    }
}

if (!function_exists('ig_publish_get_account_ids')) {
    function ig_publish_get_account_ids(array $post): array {
        $ids = [];
        if (!empty($post['ig_account_ids'])) {
            $raw = $post['ig_account_ids'];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $raw = $decoded;
                } else {
                    $raw = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);
                }
            }
            if (is_array($raw)) {
                foreach ($raw as $id) {
                    $id = (int)$id;
                    if ($id > 0) $ids[] = $id;
                }
            }
        }

        if (!$ids && !empty($post['ig_account_id'])) {
            $ids[] = (int)$post['ig_account_id'];
        }

        return array_values(array_unique(array_filter($ids)));
    }
}

if (!function_exists('ig_publish_assert_supported_media')) {
    function ig_publish_assert_supported_media(array $mediaUrls): string {
        $mediaUrls = array_values(array_filter(array_map('strval', $mediaUrls)));
        if (!$mediaUrls) {
            throw new \Exception('IG 同步發文需要至少一個圖片或影片素材');
        }
        if (count($mediaUrls) > 10) {
            throw new \Exception('IG 圖片輪播最多支援 10 個素材');
        }

        $imageCount = 0;
        $videoCount = 0;
        foreach ($mediaUrls as $url) {
            if (ig_publish_is_image_url($url)) {
                $imageCount++;
            } elseif (ig_publish_is_video_url($url)) {
                $videoCount++;
            } else {
                throw new \Exception('IG 同步發文只支援 jpg/jpeg/png/webp 圖片，或單支 mp4/mov 影片');
            }
        }

        if ($videoCount > 0 && $imageCount > 0) {
            throw new \Exception('IG 同步發文不支援圖片與影片混合，請拆成不同排程');
        }
        if ($videoCount > 1) {
            throw new \Exception('IG Reels 一次只支援一支影片，請拆成多篇排程');
        }

        return $videoCount === 1 ? 'reel' : 'image';
    }
}

if (!function_exists('ig_publish_to_account')) {
    function ig_publish_to_account(array $account, array $post, array $mediaUrls): array {
        if (empty($account['access_token'])) {
            throw new \Exception('找不到可用的 IG access token');
        }

        $mediaMode = ig_publish_assert_supported_media($mediaUrls);
        $apiVersion = defined('IG_GRAPH_API_VERSION') ? IG_GRAPH_API_VERSION : 'v23.0';
        $igAccountOwnerId = trim((string)($account['page_id'] ?? '')) ?: (string)$account['ig_user_id'];
        $base = 'https://graph.instagram.com/' . $apiVersion . '/' . rawurlencode($igAccountOwnerId);
        $accessToken = $account['access_token'];
        $caption = ig_publish_build_caption($post['title'] ?? '', (string)($post['content'] ?? ''));

        if ($mediaMode === 'reel') {
            $container = ig_publish_api_post($base . '/media', [
                'media_type' => 'REELS',
                'video_url' => $mediaUrls[0],
                'caption' => $caption,
                'access_token' => $accessToken,
            ]);
        } elseif (count($mediaUrls) === 1) {
            $container = ig_publish_api_post($base . '/media', [
                'image_url' => $mediaUrls[0],
                'caption' => $caption,
                'access_token' => $accessToken,
            ]);
        } else {
            $children = [];
            foreach ($mediaUrls as $url) {
                $child = ig_publish_api_post($base . '/media', [
                    'image_url' => $url,
                    'is_carousel_item' => 'true',
                    'access_token' => $accessToken,
                ]);
                if (empty($child['id'])) {
                    throw new \Exception('IG 輪播子素材容器建立失敗');
                }
                ig_publish_wait_container_ready($apiVersion, (string)$child['id'], $accessToken);
                $children[] = $child['id'];
            }

            $container = ig_publish_api_post($base . '/media', [
                'media_type' => 'CAROUSEL',
                'children' => implode(',', $children),
                'caption' => $caption,
                'access_token' => $accessToken,
            ]);
        }

        if (empty($container['id'])) {
            throw new \Exception('IG 媒體容器建立失敗');
        }

        ig_publish_wait_container_ready($apiVersion, (string)$container['id'], $accessToken);

        $publish = ig_publish_api_post($base . '/media_publish', [
            'creation_id' => $container['id'],
            'access_token' => $accessToken,
        ]);
        if (empty($publish['id'])) {
            throw new \Exception('IG 發文失敗：' . json_encode($publish, JSON_UNESCAPED_UNICODE));
        }

        return [
            'success' => true,
            'id' => $publish['id'],
            'account_id' => (int)($account['id'] ?? 0),
            'ig_username' => (string)($account['ig_username'] ?? ''),
            'media_mode' => $mediaMode,
        ];
    }
}

if (!function_exists('ig_publish_scheduled_post')) {
    function ig_publish_scheduled_post(\PDO $conn, array $post, array $mediaUrls): array {
        $postId = (int)($post['id'] ?? 0);
        if (empty($post['publish_to_ig'])) {
            return ['success' => true, 'skipped' => true];
        }

        try {
            $teamId = (int)($post['team_id'] ?? 0);
            $igAccountIds = ig_publish_get_account_ids($post);
            if (!$teamId || !$igAccountIds) {
                throw new \Exception('請選擇 IG 授權帳號');
            }

            $mediaUrls = array_values(array_filter(array_map('strval', $mediaUrls)));
            ig_publish_assert_supported_media($mediaUrls);

            $placeholders = implode(',', array_fill(0, count($igAccountIds), '?'));
            $params = array_merge($igAccountIds, [$teamId]);
            $accounts = \assoc_sql_all(
                $conn,
                "SELECT * FROM th_instagram_accounts WHERE id IN ($placeholders) AND team_id = ?",
                $params
            );
            $accountMap = [];
            foreach ($accounts as $account) {
                $accountMap[(int)$account['id']] = $account;
            }

            $published = [];
            $errors = [];
            foreach ($igAccountIds as $igAccountId) {
                if (empty($accountMap[$igAccountId])) {
                    $errors[] = "#{$igAccountId}: 找不到可用的 IG 授權帳號";
                    continue;
                }
                try {
                    $published[] = ig_publish_to_account($accountMap[$igAccountId], $post, $mediaUrls);
                } catch (\Exception $accountError) {
                    $label = $accountMap[$igAccountId]['ig_username'] ?? $igAccountId;
                    $errors[] = "@{$label}: " . $accountError->getMessage();
                }
            }

            $firstMediaId = $published[0]['id'] ?? null;
            if ($postId) {
                if ($errors) {
                    \mysql_up($conn, "UPDATE th_threads_scheduled_posts SET ig_status = 'failed', ig_media_id = ?, ig_error_message = ? WHERE id = ?", [
                        $firstMediaId,
                        implode("\n", $errors),
                        $postId
                    ]);
                } else {
                    \mysql_up($conn, "UPDATE th_threads_scheduled_posts SET ig_status = 'posted', ig_media_id = ?, ig_error_message = NULL WHERE id = ?", [$firstMediaId, $postId]);
                }
            }

            if ($errors) {
                throw new \Exception('IG 發文部分或全部失敗：' . implode('；', $errors));
            }

            return [
                'success' => true,
                'id' => $firstMediaId,
                'ids' => array_column($published, 'id'),
                'published' => $published,
            ];
        } catch (\Exception $e) {
            if ($postId) {
                \mysql_up($conn, "UPDATE th_threads_scheduled_posts SET ig_status = 'failed', ig_error_message = ? WHERE id = ?", [$e->getMessage(), $postId]);
            }
            throw $e;
        }
    }
}
