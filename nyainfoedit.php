<?php
/**
 * @description: 資訊編輯
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
require_once $phpFileUserSrcDir."nyauserinfoedit.class.php";
// IP檢查和解密客戶端提交的資訊
$inputInformation = $nlcore->safe->decryptargv("signup");
$argReceived = $inputInformation[0];
$totpSecret = $inputInformation[1];
// 檢查用戶是否登入
$sessionInformation = $nlcore->safe->userLogged($inputInformation);$userHash = $sessionInformation[2];
// 初始化類別
$userinfoedit = new userInfoEdit($nlcore,$inputInformation,$sessionInformation);
// 批量檢查並加入更新計劃
$userinfoedit->batchUpdate();
// 執行資料庫更新
$nlcore->db->initWriteDbs();
$updated = $userinfoedit->sqlc();
// 將執行結果 JSON 返回到客戶端
$returnArray = $nlcore->msg->m(0,1000000);
$returnArray["updated"] = implode(",", $updated);
echo $nlcore->safe->encryptargv($returnArray, $totpSecret);