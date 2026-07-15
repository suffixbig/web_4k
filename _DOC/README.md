正式網址
https://4k.1-0.tw/

遠端正式資料庫ip:192.168.0.249:3306
資料表名
帳號:suffixbig 密碼a0922508800

讓AI SSH登入
直接遠端登入
ssh -i "D:\key\1-0tw\id_rsa" -p 2222 root@192.168.0.249         這不行
ssh -i "$HOME\.ssh\codex_id_rsa" -p 2222 root@192.168.0.249     位置放這才可以

我把 key 複製到目前使用者的 C:\Users\suffi\.ssh\codex_id_rsa，這份檔案的 ACL 只有你自己、Administrators、SYSTEM 可用。
之後就能正常連上：

如果你要進行測試
正式機位置 
scp -P 2222 -i "$HOME\.ssh\codex_id_rsa" 'D:\wamp64\www\web_h4k\index.php' root@192.168.0.249:/home/wwwroot/web_4k/index.php

對映正式網址
https://4k.1-0.tw/index  正式環境為PHP 8.5 不用打副檔名.php

#API位置
https://4k.1-0.tw/api    這樣呼叫應該要有東西 



在本機建立 C:\Users\suffi\.ssh\config
-------------------------------------------------------------
Host 4k-prod
  HostName 192.168.0.249
  User root
  Port 2222
  IdentityFile C:/Users/suffi/.ssh/codex_id_rsa
  IdentitiesOnly yes
-------------------------------------------------------------
之後在 Codex 終端機直接使用：
ssh 4k-prod

檔案上傳傭有人需為www-data:www-data
但是 https://4k.1-0.tw/ 頁尾下方我要做這樣 D:\wamp64\www\web_ESIM 你可以去查 我事怎做的
相關檔案 ip_visitor_counter.php visit_analytics.php visit_stats.php 我已經搬移過來 若不夠你在拷貝