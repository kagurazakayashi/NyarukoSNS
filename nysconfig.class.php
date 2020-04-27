<?php
class nyssetting {
    // 資料庫表設置
    var $tables = [
        "comment" => "s1_comment",
        "keyword" => "s1_keyword",
        "posts" => "s1_posts",
        "like" => "s1_like",
        "tag" => "s1_tag",
        "follow" => "s1_follow", //社交關註錶
        "ban" => "s1_ban" //社交屏蔽錶
    ];
    // 各功能時長設定（每個IP位址）：[多少秒內,最多允許訪問多少次]
    var $limittime = [
        "post" => [60,30], // 發錶文章接口
        "comment" => [60,30], // 發錶評論接口
    ];
    // 字數上限(按字元)
    var $wordlimit = [
        "post" => 1000,
        "comment" => 500
    ];
}
?>