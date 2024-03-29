<?php

/**
 * @description: 建立子賬戶
 * @package NyarukoLogin
 */
$phpFileDir = pathinfo(__FILE__)["dirname"] . DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir . ".." . DIRECTORY_SEPARATOR . "user" . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR;
require_once $phpFileDir . "nyscore.class.php";
require_once $phpFileUserSrcDir . "nyacore.class.php";
require_once $phpFileUserSrcDir . "nyastand.class.php";
require_once $phpFileUserSrcDir . "nyauserinfoedit.class.php";
// IP檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("signup");
// 檢查用戶是否登入
$nlcore->sess->userLogged();
// 實現功能
$stand = new stand();
$returnClientData = $stand->addstand($nlcore->sess->argReceived, $nlcore->sess->appToken, $nlcore->sess->ipId, $nlcore->sess->userHash);
// 初始化 z1_info 表
$tableStr = $nscore->cfg->tables["info"];
$exinfoDic = [
    "userhash" => $returnClientData["userhash"],
];
$exinfoDic = $nscore->func->chkNewExInfo($exinfoDic);
// 執行資料庫更新
$dbreturn = $nlcore->db->insert($tableStr, $exinfoDic);
if ($dbreturn[0] >= 2000000) $nscore->msg->stopmsg(4050000);
// 將資訊返回給客戶端
exit($nlcore->sess->encryptargv($returnClientData));
