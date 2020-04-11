<?php
class nyassetting {
    //数据库表设置
    var $tables = [
        "comment" => "s1_comment",
        "keyword" => "s1_keyword",
        "posts" => "s1_posts",
        "like" => "s1_like",
        "tag" => "s1_tag",
        "follow" => "s1_follow", //社交关注表
        "ban" => "s1_ban" //社交屏蔽表
    ];
    //各功能时长设定（每个IP地址）：[多少秒内,最多允许访问多少次]
    var $limittime = [
        "post" => [60,30], //发表文章接口
    ];
}
?>