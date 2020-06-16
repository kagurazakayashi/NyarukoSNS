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
    // 功能性符號定義
    var $separator = [
        "namelink" => "+", //用户昵称和昵称ID的连接符，常用符號爲「#」，例如「神楽坂雅詩#5534」。
        "mention" => "@", //提及某人，常用符號爲「@」，例如「@神楽坂雅詩」。
        "hashtag" => "#" //話題起始符，常用符號爲「#」，例如「#猫猫」。
    ];
    // 主頁每個貼文顯示多少條評論
    var $timelinecommnum = 3;
}
?>