<?php
/* 
相關API在這篇https://developers.facebook.com/docs/threads/retrieve-and-manage-replies/create-replies
 */
namespace wind_master;

use Exception;

class ThreadsMaster {
    private $appId;
    private $appSecret;
    private $redirectUri;
    private $apiBase = 'https://graph.threads.net/v1.0/';
    private $authBase = 'https://threads.net/oauth/authorize';
    private $tokenBase = 'https://graph.threads.net/oauth/access_token';

    public function __construct($appId, $appSecret, $redirectUri) {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->redirectUri = $redirectUri;
    }

    /**
     * 獲取授權網址，請求所有 10 個權限
     */
    public function getAuthUrl() {
        $scopes = [
            'threads_basic',
            'threads_content_publish',
            'threads_location_tagging',
            'threads_delete',
            'threads_keyword_search',
            'threads_read_replies',
            'threads_manage_replies',
            'threads_manage_insights',
            'threads_manage_mentions',
            'threads_profile_discovery'
        ];

        $state = bin2hex(random_bytes(16));
        $_SESSION['th_state'] = $state;

        $params = [
            'client_id' => $this->appId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(',', $scopes),
            'response_type' => 'code',
            'state' => $state
        ];

        return $this->authBase . '?' . http_build_query($params);
    }

    /**
     * 使用授權碼換取短期存取權杖
     */
    public function getShortLivedToken($code) {
        $params = [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code
        ];

        return $this->makeRequest($this->tokenBase, $params, 'POST');
    }

    /**
     * 將短期權杖換成長期權杖 (60天)
     */
    public function getLongLivedToken($shortToken) {
        $url = "https://graph.threads.net/access_token";
        $params = [
            'grant_type' => 'th_exchange_token',
            'client_secret' => $this->appSecret,
            'access_token' => $shortToken
        ];

        return $this->makeRequest($url, $params, 'GET');
    }

    /**
     * 延長尚未過期的長效權杖
     */
    public function refreshLongLivedToken($longToken) {
        $url = "https://graph.threads.net/refresh_access_token";
        $params = [
            'grant_type' => 'th_refresh_token',
            'access_token' => $longToken
        ];

        return $this->makeRequest($url, $params, 'GET');
    }

    /**
     * 通用的 API 請求發送器
     */
    public function makeApiRequest($endpoint, $accessToken, $params = [], $method = 'GET') {
        $url = $this->apiBase . ltrim($endpoint, '/');
        $params['access_token'] = $accessToken;
        
        return $this->makeRequest($url, $params, $method);
    }

    private function makeRequest($url, $params, $method = 'GET') {
        $ch = curl_init();
        
        if ($method === 'GET') {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        } elseif ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } elseif ($method === 'DELETE') {
            // 對於 DELETE，Threads API 通常期望 access_token 作為查詢參數
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
        }
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        if ($response === false) {
            throw new Exception("CURL Error: " . $curlError);
        }
        
        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            $message = 'Unknown Error';
            if (is_array($decoded)) {
                $message = $decoded['error']['message'] ?? $decoded['message'] ?? $decoded['error_msg'] ?? $message;
            } elseif (is_string($response) && trim($response) !== '') {
                $message = substr(trim($response), 0, 300);
            }

            return [
                'error' => true,
                'status' => $httpCode,
                'message' => $message,
                'raw' => $decoded
            ];
        }
        
        return $decoded;
    }

    /**
     * 建立媒體容器 (可以包含文字、圖片或影片，或作為回覆)
     */
    public function createThreadContainer($userId, $accessToken, $params) {
        $endpoint = "$userId/threads";
        return $this->makeApiRequest($endpoint, $accessToken, $params, 'POST');
    }

    /**
     * 發佈媒體容器
     */
    public function publishThread($userId, $accessToken, $creationId) {
        $endpoint = "$userId/threads_publish";
        $params = ['creation_id' => $creationId];
        return $this->makeApiRequest($endpoint, $accessToken, $params, 'POST');
    }

    /**
     * 刪除媒體 (Thread Post)
     */
    public function deleteThread($mediaId, $accessToken) {
        $endpoint = "$mediaId";
        return $this->makeApiRequest($endpoint, $accessToken, [], 'DELETE');
    }

    /**
     * 獲取媒體容器狀態
     * @return array [status => 'FINISHED'|'IN_PROGRESS'|'ERROR'|'EXPIRED', error_message => '...']
     */
    public function getContainerStatus($accessToken, $creationId) {
        $endpoint = "$creationId";
        $params = ['fields' => 'status,error_message'];
        return $this->makeApiRequest($endpoint, $accessToken, $params, 'GET');
    }

    /**
     * 等待媒體容器處理完成
     * @return bool 是否成功完成
     */
    public function waitForContainer($accessToken, $creationId, $maxRetries = 15, $sleepTime = 5) {
        for ($i = 0; $i < $maxRetries; $i++) {
            $statusRes = $this->getContainerStatus($accessToken, $creationId);
            
            $status = $statusRes['status'] ?? 'UNKNOWN';
            if ($status === 'FINISHED') {
                return true;
            }
            
            if ($status === 'ERROR') {
                throw new Exception("媒體處理失敗: " . ($statusRes['error_message'] ?? '未知原因'));
            }

            if ($status === 'EXPIRED') {
                throw new Exception("媒體容器已過期");
            }

            // 如果是 IN_PROGRESS 或是其他，繼續等待
            sleep($sleepTime);
        }
        
        throw new Exception("媒體處理超時 (已等待 " . ($maxRetries * $sleepTime) . " 秒)");
    }
}
