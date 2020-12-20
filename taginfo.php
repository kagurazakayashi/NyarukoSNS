<?php

/**
 * @description: 話題資訊讀取
 * @package NyarukoSNS
 */
$phpFileDir = pathinfo(__FILE__)["dirname"] . DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir . ".." . DIRECTORY_SEPARATOR . "user" . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR;
require_once $phpFileUserSrcDir . "nyacore.class.php";
require_once $phpFileDir . "nyscore.class.php";
require_once $phpFileDir . "nystag.class.php";
// IP檢查和解密客戶端提交的資訊
$frequencyLimitation = $nscore->cfg->limittime["timeline"];
$nlcore->sess->decryptargv("", $frequencyLimitation[0], $frequencyLimitation[1]);
$argReceived = $nlcore->sess->argReceived;
// 獲取標籤內容
$tagName = (isset($argReceived["tag"]) && strlen($argReceived["tag"]) >= 0) ? $argReceived["tag"] : $nscore->msg->stopmsg(4010300, $nowfile);
// 檢查標籤是否存在
$tagMgr = new nystag();
$tag = $tagMgr->postTagExists($tagName);
if (count($tag) < 2) {
    $nscore->msg->stopmsg(4010308, "-1");
}
// 檢查話題是否已經被封禁
$stat = intval($tag["stat"]);
if ($stat > 0) {
    $nscore->msg->stopmsg(4010308, $tag["stat"]);
}
// 準備要返回的資訊
$returnClientData = $nscore->msg->m(0, 3000405);
$returnClientData["ctime"] = $tag["ctime"];
$returnClientData["ntime"] = $tag["ntime"];
// 熱度重整
$returnClientData["hot"] = [
    "now" => intval($tag["hot"]),
    "day" => intval($tag["hotday"]),
    "week" => intval($tag["hotweek"]),
    "mon" => intval($tag["hotmon"])
];
// 檢查話題是否為超話
$type = intval($tag["type"]);
$fileNone = ["path" => ""];
$returnClientData["type"] = $type;
$returnClientData["user"] = [];
$returnClientData["img"] = $fileNone;
$returnClientData["color"] = "";
$returnClientData["describes"] = "";
if ($type == 1) {
    // 如果是超話，返回超話相關資訊
    if ($tag["userhash"] != null) {
        $returnClientData["user"] = $nlcore->func->getuserinfo($tag["userhash"], [], false, ["userhash", "infotype", "name", "nameid", "gender"]);
    }
    // 獲取圖片詳細路徑
    if ($tag["bgimg"] != null) {
        $returnClientData["img"] = strlen($tag["bgimg"]) > 1 ? $nlcore->func->imagesurl($tag["bgimg"], $fileNone) : $fileNone;
    }
    // 還原圖片為16進位制
    if ($tag["bgcolor"] != null) {
        $returnClientData["color"] = "FF".$nlcore->safe->dColor(intval($tag["bgcolor"]));
    }
    if ($tag["describes"] != null) {
        $returnClientData["describes"] = $tag["describes"];
    }
}
exit($nlcore->sess->encryptargv($returnClientData));