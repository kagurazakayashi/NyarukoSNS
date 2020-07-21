<?php
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
require_once $phpFileUserSrcDir."nyastand.class.php";
// IP檢查和解密客戶端提交的資訊
$inputInformation = $nlcore->safe->decryptargv("signup");
$argReceived = $inputInformation[0];
$totpSecret = $inputInformation[1];
// 檢查用戶是否登入
$sessionInformation = $nlcore->safe->userLogged($inputInformation);
// 初始化類別
$stand = new stand();
// 獲取執行結果
$returnArray = $stand->addstand($nlcore, $inputInformation, $sessionInformation);
// 初始化 z1_info 表
$tableStr = $nscore->cfg->tables["info"];
$insertDic = [
    "userhash" => $returnArray["userhash"],
];
$dbreturn = $nlcore->db->insert($tableStr,$insertDic);
if ($dbreturn[0] >= 2000000) $nscore->msg->stopmsg(4050000,$totpSecret);
// 將執行結果 JSON 返回到客戶端
echo $nlcore->safe->encryptargv($returnArray, $totpSecret);
?>