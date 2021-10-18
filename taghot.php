<?php

/**
 * @description: 话题热度排行
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
// 檢查用戶是否登入，若沒有提供 token 則…算了
$userHash = null;
if (isset($argReceived["token"]) && strlen($argReceived["token"]) > 0) {
    $nlcore->sess->userLogged();
    $userHash = $nlcore->sess->userHash;
}
// 導入提交的參數
$limst = isset($argReceived["limst"]) ? intval($argReceived["limst"]) : 0;
$offset = isset($argReceived["offset"]) ? intval($argReceived["offset"]) : 10;
// 0當前 1日 2周 3月 4年
$listLevels = ["hot", "hotday", "hotweek", "hotmon"];
$listLevel = isset($argReceived["list"]) ? intval($argReceived["list"]) : 0;
if ($listLevel < 0 || $listLevel > count($listLevels)) {
    $listLevel = 0;
}
// 查询热度
$table = $nscore->cfg->tables["tag"];
$columnArr = ["taghash", "tag", "bgcolor", "describes"];
$whereDic = ["stat" => 0];
$orderby = $listLevels[$listLevel];
$result = $nlcore->db->select($columnArr, $table, $whereDic, "", "AND", false, [$orderby, true], [$limst, $offset]);
if ($result[0] >= 2000000) $nscore->msg->stopmsg(4010301);
$tagArr = $result[2];
$returnClientData = $nscore->msg->m(0, 3000202);
$returnClientData["tags"] = $tagArr;
exit($nlcore->sess->encryptargv($returnClientData));
