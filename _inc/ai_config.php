<?php
/**
 * AI 用量統計與點數換算設定
 */

// 點數換算率 (預設：1000 Tokens = 1 點)
$tokenPointRate = 1000;

// 將換算率放入全域或常數
if (!defined('AI_TOKEN_RATE')) {
    define('AI_TOKEN_RATE', $tokenPointRate);
}
