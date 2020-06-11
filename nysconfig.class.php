<?php
class nyssetting {
    // 資料庫表設置
    var $tables = [
        "comment" => "s1_comment",
        "keyword" => "s1_keyword",
        "posts" => "s1_posts",
        "like" => "s1_like",
        "tag" => "s1_tag",
        "follow" => "s1_follow", //社交關註
        "ban" => "s1_ban", //社交屏蔽
        "info" => "s1_info" //擴展用戶資訊
    ];
    // 各功能時長設定（每個IP位址）：[多少秒內,最多允許訪問多少次]
    var $limittime = [
        "post" => [60,30], // 文章
        "comment" => [60,30], // 評論
        "timeline" => [60,30], // 時間線
        "commentlist" => [60,30] // 評論列表
    ];
    // 字數上限(按字元)
    var $wordlimit = [
        "post" => 1000,
        "comment" => 500
    ];
}
?>