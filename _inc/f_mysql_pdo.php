<?php
/**
 * @file f_mysql_pdo.php
 * @brief MySQL PDO 資料庫操作工具函式庫。
 *
 * 提供連線建立、查詢、新增、更新、刪除等常用封裝函式，
 * 統一使用 PDO Prepared Statement 防止 SQL Injection。
 */


// ─────────────────────────────────────────────
// 連線管理
// ─────────────────────────────────────────────

/**
 * @brief 建立一個標準的 MySQL PDO 連線。
 *
 * 使用設定檔中定義的常數 (DB_HOST, DB_USER, DB_PASS, DB_CHARSET)
 * 建立 PDO 連線，並啟用例外模式 (ERRMODE_EXCEPTION)。
 * 連線失敗時會寫入錯誤日誌並終止程式，回傳 JSON 錯誤訊息。
 *
 * @param string $db_name 資料庫名稱。
 * @param string $host    資料庫主機，預設為 DB_HOST。
 * @param string $dbuser  資料庫使用者，預設為 DB_USER。
 * @param string $dbpass  資料庫密碼，預設為 DB_PASS。
 * @param string $charset 字元集，預設為 DB_CHARSET。
 * @return PDO PDO 連線物件。
 */
function omysql(
    string $db_name,
    string $host    = DB_HOST,
    string $dbuser  = DB_USER,
    string $dbpass  = DB_PASS,
    string $charset = DB_CHARSET
): PDO {
    $dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";
    try {
        $link = new \PDO($dsn, $dbuser, $dbpass);
        $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die(json_encode(["error" => "Database connection failed."]));
    }
    return $link;
}

/**
 * @brief 取得預設資料庫的 PDO 連線（相容性捷徑函式）。
 *
 * 等同於 omysql(DB_NAME)，方便快速取得預設連線。
 *
 * @return PDO PDO 連線物件。
 */
function getPDO(): PDO
{
    return omysql(DB_NAME);
}

/**
 * @brief 建立一個持久的 MySQL PDO 連線（長連線）。
 *
 * 與 omysql() 相同，但啟用 PDO::ATTR_PERSISTENT，
 * 連線在腳本結束後不會立即關閉，可被後續請求重複使用。
 * 適用於高頻查詢情境，但需留意連線狀態殘留問題。
 *
 * @param string $db_name 資料庫名稱。
 * @param string $host    資料庫主機，預設為 DB_HOST。
 * @param string $dbuser  資料庫使用者，預設為 DB_USER。
 * @param string $dbpass  資料庫密碼，預設為 DB_PASS。
 * @param string $charset 字元集，預設為 DB_CHARSET。
 * @return PDO PDO 連線物件。
 */
function omysql_l(
    string $db_name,
    string $host    = DB_HOST,
    string $dbuser  = DB_USER,
    string $dbpass  = DB_PASS,
    string $charset = DB_CHARSET
): PDO {
    $dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";
    try {
        $link = new \PDO($dsn, $dbuser, $dbpass, [PDO::ATTR_PERSISTENT => true]);
        $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log("Database connection failed (persistent): " . $e->getMessage());
        die(json_encode(["error" => "Database connection failed (persistent)."]));
    }
    return $link;
}

/**
 * @brief 關閉一個 PDO 連線。
 *
 * 透過傳參考將 PDO 物件設為 null，明確釋放資料庫連線資源。
 * 注意：必須傳入參考 (&$link)，否則外部變數不會被清除。
 *
 * @param PDO|null $link 要關閉的 PDO 連線物件（傳參考）。
 * @return void
 */
function cmysql(?PDO &$link): void
{
    $link = null;
}


// ─────────────────────────────────────────────
// 交易管理
// ─────────────────────────────────────────────

/**
 * @brief 以交易（Transaction）方式執行一組資料庫操作。
 *
 * 將 $callback 包裹在 BEGIN / COMMIT 之中，
 * 若 $callback 拋出任何例外則自動 ROLLBACK 並重新拋出。
 *
 * 使用範例：
 * ```php
 * db_transaction($db, function(PDO $db) {
 *     mysql_up($db, "UPDATE ...", [...]);
 *     mysql_insert_ok($db, "INSERT ...", [...]);
 * });
 * ```
 *
 * @param PDO      $db       PDO 連線物件。
 * @param callable $callback 接受 PDO 為參數的回呼函式。
 * @return mixed $callback 的回傳值。
 * @throws Throwable 若 $callback 執行失敗則重新拋出例外。
 */
function db_transaction(PDO $db, callable $callback): mixed
{
    $db->beginTransaction();
    try {
        $result = $callback($db);
        $db->commit();
        return $result;
    } catch (\Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}


// ─────────────────────────────────────────────
// 陣列工具
// ─────────────────────────────────────────────

/**
 * @brief 將陣列轉換為 SQL `IN` 子句字串（⚠️ 僅適用於已信任的整數值）。
 *
 * ⚠️  安全警告：此函式直接將陣列值拼接進 SQL 字串，
 *     若陣列內容來自使用者輸入，請改用 idgroup_placeholders()
 *     搭配 PDO 參數綁定，以避免 SQL Injection 風險。
 *
 * 將一維陣列轉換成 `IN ('a','b','c')` 格式的字串。
 * 若陣列為空，返回 `IN (0)` 以確保 SQL 語法不報錯。
 *
 * @param array $array 要轉換的一維陣列（建議僅傳入已驗證的整數 ID）。
 * @return string 格式化後的 SQL `IN` 子句字串。
 */
function idgroup(array $array = []): string
{
    if (empty($array)) {
        return "IN (0)";
    }

    $escaped = array_map(fn($v) => "'" . addslashes($v) . "'", $array);
    return "IN (" . implode(',', $escaped) . ")";
}

/**
 * @brief 產生安全的 SQL `IN` 子句佔位符與參數（推薦使用）。
 *
 * 回傳包含 `sql` 與 `params` 的陣列，需搭配 PDO 參數綁定使用，
 * 可完全避免 SQL Injection 風險。
 *
 * 使用範例：
 * ```php
 * $in = idgroup_placeholders($ids);
 * $sql = "SELECT * FROM users WHERE id {$in['sql']}";
 * $rows = assoc_sql_all($db, $sql, $in['params']);
 * ```
 *
 * @param array $array 要轉換的一維陣列。
 * @return array{sql: string, params: array} 包含 sql 片段與 params 陣列。
 */
function idgroup_placeholders(array $array): array
{
    if (empty($array)) {
        return ['sql' => 'IN (0)', 'params' => []];
    }
    $placeholders = implode(',', array_fill(0, count($array), '?'));
    return ['sql' => "IN ({$placeholders})", 'params' => array_values($array)];
}

/**
 * @brief 從二維陣列中提取指定欄位，轉換為一維陣列。
 *
 * 遍歷來源的二維陣列，將每筆子陣列中指定欄位的值提取出來，
 * 組成一個新的一維陣列。
 *
 * @param array  $array 來源的二維關聯陣列。
 * @param string $name  要提取的欄位名稱，預設為 'id'。
 * @return array 包含提取值的一維陣列；若來源不是陣列則返回空陣列。
 */
function array_2to1(array $array, string $name = 'id'): array
{
    $result = [];
    foreach ($array as $row) {
        if (is_array($row) && array_key_exists($name, $row)) {
            $result[] = $row[$name];
        }
    }
    return $result;
}


// ─────────────────────────────────────────────
// SELECT 查詢
// ─────────────────────────────────────────────

/**
 * @brief 執行帶有參數綁定的 SQL SELECT 查詢，返回所有結果。
 *
 * 使用 PDO Prepared Statement，支援命名參數（`:name`）與位置參數（`?`）。
 * 可透過 $types 指定各參數的 PDO 資料類型；未指定則預設為 PDO::PARAM_STR。
 * 查詢失敗時拋出例外。
 *
 * @param PDO    $db     PDO 連線物件。
 * @param string $sql    要執行的 SQL 查詢語句。
 * @param array  $params 參數陣列，鍵為命名參數名稱或位置索引（從 0 起）。
 * @param array  $types  （可選）對應 $params 的 PDO 資料類型陣列。
 * @return array 查詢結果的二維關聯陣列；無結果時返回空陣列。
 * @throws Exception 查詢失敗時拋出。
 */
function mysql_params(PDO $db, string $sql, array $params = [], array $types = []): array
{
    try {
        $query = $db->prepare($sql);
        $param_index = 1;
        foreach ($params as $key => $value) {
            $identifier = is_string($key) ? $key : $param_index;
            $type = $types[$key] ?? ($types[$param_index] ?? PDO::PARAM_STR);
            $query->bindValue($identifier, $value, $type);
            if (!is_string($key)) {
                $param_index++;
            }
        }
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        throw new \Exception("SQL 查詢失敗: " . $e->getMessage());
    }
}

/**
 * @brief 執行 SQL SELECT 查詢，返回所有結果（支援自動類型偵測）。
 *
 * 自動判斷每個參數的 PDO 類型（int / bool / null / string），
 * 避免 LIMIT、OFFSET 等整數子句因被加上引號而報錯。
 * 支援位置參數（`?`，key 從 0 起）與命名參數（`:name`）。
 * 查詢失敗時寫入錯誤日誌並返回空陣列。
 *
 * @param PDO    $linkID PDO 連線物件。
 * @param string $sql    要執行的 SQL 查詢語句。
 * @param array  $params 參數陣列，用於綁定到 SQL 語句中。
 * @return array 查詢結果的二維關聯陣列；失敗或無結果時返回空陣列。
 */
function assoc_sql_all(PDO $linkID, string $sql, array $params = []): array
{
    try {
        $stmt = $linkID->prepare($sql);
        foreach ($params as $key => $value) {
            $identifier = is_int($key) ? $key + 1 : $key;
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($identifier, $value, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        error_log("assoc_sql_all failed: " . $e->getMessage());
        return [];
    }
}

/**
 * @brief 執行 SQL SELECT 查詢，返回所有結果的二維關聯陣列。
 *
 * 使用 PDO Prepared Statement 搭配 fetchAll()，一次取回全部資料列。
 * 適合結果筆數可預期且不會過多的情境。
 *
 * @param PDO    $linkID PDO 連線物件。
 * @param string $sql    要執行的 SQL 查詢語句。
 * @param array  $params （可選）參數陣列，用於綁定到 SQL 語句中。
 * @return array 查詢結果的二維關聯陣列；無結果時返回空陣列。
 */
function assoc_sql(PDO $linkID, string $sql, array $params = []): array
{
    $stmt = $linkID->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @brief 執行 SQL SELECT 查詢，僅返回第一筆結果的關聯陣列。
 *
 * 函式名稱結尾 `1p` 表示使用 fetch() 只取第一列（一維關聯陣列）。
 * 若查詢無結果，返回 false。
 *
 * @param PDO    $linkID PDO 連線物件。
 * @param string $sql    要執行的 SQL 查詢語句。
 * @param array  $params （可選）參數陣列，用於綁定到 SQL 語句中。
 * @return array|false 第一筆結果的一維關聯陣列；無結果時返回 false。
 */
function assoc_sql1p(PDO $linkID, string $sql, array $params = []): array|false
{
    $stmt = $linkID->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * @brief 執行 SQL SELECT 查詢，返回第一筆結果中指定欄位的純量值。
 *
 * 以數字索引（從 0 起）指定要回傳的欄位位置，預設取第 0 欄。
 * 若查詢無結果，返回 null。
 *
 * @param PDO    $linkID PDO 連線物件。
 * @param string $sql    要執行的 SQL 查詢語句。
 * @param int    $s      要返回的欄位索引（從 0 起），預設為 0。
 * @param array  $params （可選）用於 Prepared Statement 的參數陣列。
 * @return mixed|null 指定欄位的值；無結果時返回 null。
 */
function row_sql1p(PDO $linkID, string $sql, int $s = 0, array $params = []): mixed
{
    $stmt = $linkID->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    return $row[$s] ?? null;
}

/**
 * @brief 執行 SQL SELECT 查詢，將每筆結果中指定欄位提取成一維陣列。
 *
 * 以數字索引（從 0 起）指定要提取的欄位位置，適合只需要單一欄位清單的情境，
 * 例如取出所有 ID 或所有名稱列表。
 *
 * @param PDO    $linkID PDO 連線物件。
 * @param string $sql    要執行的 SQL 查詢語句。
 * @param array  $params （可選）參數陣列，用於綁定到 SQL 語句中。
 * @param int    $s      要提取的欄位索引（從 0 起），預設為 0。
 * @return array 包含提取值的一維陣列；無結果時返回空陣列。
 */
function row_sql1s(PDO $linkID, string $sql, array $params = [], int $s = 0): array
{
    $stmt = $linkID->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
    return array_column($rows, $s);
}


// ─────────────────────────────────────────────
// 寫入操作（INSERT / UPDATE / DELETE）
// ─────────────────────────────────────────────

/**
 * @brief 執行 UPDATE、DELETE 或其他不返回結果集的 SQL 語句。
 *
 * @param PDO          $linkID PDO 連線物件。
 * @param string       $sql    要執行的 SQL 語句。
 * @param array|string $data   （可選）用於 Prepared Statement 的參數陣列。
 * @return int 受影響的資料列數。
 */
function mysql_up(PDO $linkID, string $sql, array|string $data = []): int
{
    $stmt = $linkID->prepare($sql);
    $stmt->execute(is_array($data) ? $data : []);
    return $stmt->rowCount();
}

/**
 * @brief 執行 INSERT 語句並返回操作結果。
 *
 * 成功時返回 `['ok' => 1, 'id' => lastInsertId]`；
 * 若 lastInsertId 為空（如資料表無自動遞增主鍵）則返回 `['ok' => 0, 'sms' => '新增失敗']`。
 * 執行過程中發生例外時拋出 Exception。
 *
 * @param PDO          $linkID PDO 連線物件。
 * @param string       $sql    要執行的 INSERT SQL 語句。
 * @param array|string $data   （可選）用於 Prepared Statement 的參數陣列。
 * @return array 操作結果：成功含 'ok' => 1 與 'id'，失敗含 'ok' => 0 與 'sms'。
 * @throws Exception 執行失敗時拋出。
 */
function mysql_insert_ok(PDO $linkID, string $sql, array|string $data = []): array
{
    $sth = $linkID->prepare($sql);
    try {
        $sth->execute(is_array($data) ? $data : []);
        $id = $linkID->lastInsertId();
        return $id
            ? ['ok' => 1, 'id' => $id]
            : ['ok' => 0, 'sms' => '新增失敗'];
    } catch (PDOException $e) {
        throw new \Exception('MYSQL資料新增失敗: ' . $e->getMessage());
    }
}

/**
 * @brief 根據指定欄位與值刪除資料表中的一筆紀錄（泛用版）。
 *
 * 可指定任意欄位名稱作為條件，取代個別撰寫 mysql_del / mysql_del_sno 等函式。
 *
 * 使用範例：
 * ```php
 * mysql_del_by($db, 'orders', 'id', 42);
 * mysql_del_by($db, 'orders', 'sno', 'ORD-001');
 * ```
 *
 * ⚠️ 資料表名稱 $table 與欄位名稱 $column 不經 PDO 綁定，
 *    請確保這兩個參數的來源受到程式控制，勿直接使用使用者輸入。
 *
 * @param PDO        $connection PDO 連線物件。
 * @param string     $table      資料表名稱。
 * @param string     $column     條件欄位名稱（如 'id'、'sno'）。
 * @param int|string $value      要比對的欄位值。
 * @return bool 刪除成功返回 true；失敗返回 false。
 */
function mysql_del_by(PDO $connection, string $table, string $column, int|string $value): bool
{
    try {
        $sql  = "DELETE FROM `{$table}` WHERE `{$column}` = :val";
        $stmt = $connection->prepare($sql);
        return $stmt->execute([':val' => $value]);
    } catch (PDOException $e) {
        error_log("mysql_del_by failed [{$table}.{$column}={$value}]: " . $e->getMessage());
        return false;
    }
}

/**
 * @brief 根據主鍵 `id` 刪除資料表中的一筆紀錄。
 *
 * 為 mysql_del_by($connection, $table, 'id', $id) 的捷徑函式。
 *
 * @param PDO        $connection PDO 連線物件。
 * @param string     $table      資料表名稱。
 * @param int|string $id         要刪除的紀錄 id 值。
 * @return bool 刪除成功返回 true；失敗返回 false。
 */
function mysql_del(PDO $connection, string $table, int|string $id): bool
{
    return mysql_del_by($connection, $table, 'id', $id);
}

/**
 * @brief 根據 `sno` 欄位刪除資料表中的一筆紀錄。
 *
 * 為 mysql_del_by($connection, $table, 'sno', $id) 的捷徑函式。
 *
 * @param PDO        $connection PDO 連線物件。
 * @param string     $table      資料表名稱。
 * @param int|string $id         要刪除的紀錄 sno 值。
 * @return bool 刪除成功返回 true；失敗返回 false。
 */
function mysql_del_sno(PDO $connection, string $table, int|string $id): bool
{
    return mysql_del_by($connection, $table, 'sno', $id);
}
