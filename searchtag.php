<?php

/**
 * @description: 搜索标签
 * @package NyarukoSNS
 */
$phpFileDir = pathinfo(__FILE__)["dirname"] . DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir . ".." . DIRECTORY_SEPARATOR . "user" . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR;
require_once $phpFileDir . "nyscore.class.php";
require_once $phpFileUserSrcDir . "nyacore.class.php";
// IP檢查和解密客戶端提交的資訊
$frequencyLimitation = $nscore->cfg->limittime["timeline"];
$nlcore->sess->decryptargv("", $frequencyLimitation[0], $frequencyLimitation[1]);
$argReceived = $nlcore->sess->argReceived;
// 檢查用戶是否登入
$nlcore->sess->userLogged();
$userHash = $nlcore->sess->userHash;
// 檢查搜尋的字串是否合法
if (!isset($argReceived["tag"]) || strlen($argReceived["tag"]) == 0) {
    $nscore->msg->stopmsg(4010308);
}
$search = $nlcore->db->searchWordSafe([$argReceived["tag"]], false);
// 進行查詢
$tableStr = $nscore->cfg->tables["tag"];
$columnArr = ["tag"];
$searchColumn = ["tag"];
$mode = 1;
$order = [];
$limit = isset($argReceived["limit"]) ? intval($argReceived["limit"]) : 10;
$dbReturn = $nlcore->db->searchWord($tableStr, $columnArr, $searchColumn, $mode, $search, $order, [$limit]);
// 整理資料庫返回值
$findTags = [];
$tagItems=$dbReturn[2];
if (isset($tagItems) && count($tagItems) > 0) {
    foreach ($tagItems as $tagItem) {
        $nowTag = $tagItem["tag"];
        array_push($findTags, $nowTag);
    }
}
$returnClientData = $nscore->msg->m(0, 3000500);
$returnClientData["num"] = count($findTags);
$returnClientData["tags"] = $findTags;
exit($nlcore->sess->encryptargv($returnClientData));
