<?php
/**
 * @description: 使用者註冊
 * @package NyarukoLogin
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
require_once $phpFileUserSrcDir."nyasignup.class.php";
require_once $phpFileUserSrcDir."nyauserinfoedit.class.php";
// IP檢查和解密客戶端提交的資訊
$nlcore->sess->decryptargv("signup");
// 不檢查用戶是否登入
// 實現功能
$nyasignup = new nyasignup();
$returnClientData = $nyasignup->adduser($nlcore->sess->argReceived,$nlcore->sess->appToken,$nlcore->sess->ipId);
// 初始化 z1_info 表
$tableStr = $nscore->cfg->tables["info"];
$exinfoDic = [
    "userhash" => $returnClientData["userhash"],
];
$exinfoDic = $zecore->func->chkNewExInfo($exinfoDic);
$dbreturn = $nlcore->db->insert($tableStr,$exinfoDic);
if ($dbreturn[0] >= 2000000) $zecore->msg->stopmsg(4050000);
// 將資訊返回給客戶端
exit($nlcore->sess->encryptargv($returnClientData));