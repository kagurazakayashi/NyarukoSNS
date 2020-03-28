<?php
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
require_once $usersrc."nyasignup.class.php";
// IP檢查和解密客戶端提交的資訊
$inputInformation = $nlcore->safe->decryptargv("signup");
$jsonarr = $inputInformation[0];
$totpSecret = $inputInformation[1];
// 不檢查用戶是否登入
// 初始化類別
$nyasignup = new nyasignup();
// 獲取執行結果
$returnArray = $nyasignup->adduser($nlcore, $inputInformation);
// 初始化 z1_info 表
$tableStr = $nscore->cfg->tables["info"];
$insertDic = [
    "userhash" => $returnArray["userhash"],
];
$dbreturn = $nlcore->db->insert($tableStr,$insertDic);
if ($dbreturn[0] >= 2000000) $nscore->msg->stopmsg(4050000,$totpsecret);
// 將執行結果 JSON 返回到客戶端
echo $nlcore->safe->encryptargv($returnArray, $totpSecret);
?>