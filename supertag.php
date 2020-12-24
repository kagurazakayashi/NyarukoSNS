<?php
/**
 * @description: 超級話題設定
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
// 檢查用戶是否登入
$nlcore->sess->userLogged();
$userHash = $nlcore->sess->userHash;
// 檢查使用哪個使用者操作
if (isset($argReceived["userhash"]) && strcmp($userHash, $argReceived["userhash"]) != 0) {
    $subuser = $argReceived["userhash"];
    if (!$nlcore->safe->is_rhash64($subuser)) $nlcore->msg->stopmsg(2070003, "S-" . $subuser);
    $issub = $nlcore->func->issubaccount($userHash, $subuser)[0];
    if ($issub == false) $nlcore->msg->stopmsg(2070004, "S-" . $subuser);
    $userHash = $subuser;
}
// 獲取標籤內容
$tagName = (isset($argReceived["tagName"]) && strlen($argReceived["tagName"]) >= 0) ? $argReceived["tagName"] : $nscore->msg->stopmsg(4010300, $nowfile);
// 檢查上傳的背景圖
$bgimg = (isset($argReceived["bgimg"]) && strlen($argReceived["bgimg"]) >= 32) ? $argReceived["bgimg"] : null;
if ($bgimg) {
    $filesarr = explode(",", $argReceived["bgimg"]);
    foreach ($filesarr as $nowfile) {
        if (!$nlcore->safe->ismediafilename($nowfile)) {
            $nscore->msg->stopmsg(4010200, $nowfile);
        }
    }
}
// 檢查顏色
$bgcolor = (isset($argReceived["bgcolor"]) && strlen($argReceived["bgcolor"]) >= 3) ? $argReceived["bgcolor"] : null;
if ($bgcolor) {
    $bgcolor = $nlcore->safe->eColor($bgcolor);
}
// 檢查描述
$describes = (isset($argReceived["describes"]) && strlen($argReceived["describes"]) > 0) ? $argReceived["describes"] : null;
if ($describes) {
    // TODO: 字数限制
    $nlcore->safe->wordfilter($describes);
}
// 更新還是刪除
$isAdd = ($bgimg || $bgcolor || $describes);
// 檢查資訊並更新標籤
$tagMgr = new nystag();
$returncode = $tagMgr->supertag($tagName, $userHash, $isAdd, $bgimg ?? '', $bgcolor ?? '0', $describes ?? '');
$returnClientData = $nscore->msg->m(0, $returncode);
exit($nlcore->sess->encryptargv($returnClientData));
