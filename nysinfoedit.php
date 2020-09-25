<?php

/**
 * @description: 使用者資訊編輯
 * @package NyarukoSNS
 */
$phpFileDir = pathinfo(__FILE__)["dirname"] . DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir . ".." . DIRECTORY_SEPARATOR . "user" . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR;
require_once $phpFileDir . "nyscore.class.php";
require_once $phpFileUserSrcDir . "nyacore.class.php";
require_once $phpFileUserSrcDir . "nyauserinfoedit.class.php";
// IP檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("signup");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 初始化類別
$userinfoedit = new userInfoEdit($nlcore->sess->argReceived, $nlcore->sess->userHash);
// 批量檢查並加入更新計劃
$userinfoedit->batchUpdate();
$nlcore->db->initWriteDbs();
$exinfoDic = $zecore->func->chkNewExInfo();
// 執行資料庫更新
if (count($exinfoDic) > 0) {
    $tableStr = $zecore->cfg->tables["info"];
    $whereDic = ["userhash" => $nlcore->sess->userHash];
    $result = $this->nlcore->db->update($exinfoDic, $tableStr, $whereDic);
    if ($result[0] >= 2000000) $this->nlcore->msg->stopmsg(2040604);
}
// 將執行結果 JSON 返回到客戶端
$returnClientData = $nlcore->msg->m(0, 1000000);
$returnClientData["updated"] = implode(",", $updated);
exit($nlcore->sess->encryptargv($returnClientData));
