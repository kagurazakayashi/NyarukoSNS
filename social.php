<?php

/**
 * @description: 修改使用者關係
 * @package NyarukoSNS
 */
$phpFileDir = pathinfo(__FILE__)["dirname"] . DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir . ".." . DIRECTORY_SEPARATOR . "user" . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR;
require_once $phpFileDir . "nyscore.class.php";
require_once $phpFileUserSrcDir . "nyacore.class.php";
// IP檢查和解密客戶端提交的資訊
$frequencyLimitation = $nscore->cfg->limittime["social"];
$nlcore->sess->decryptargv("", $frequencyLimitation[0], $frequencyLimitation[1]);
$argReceived = $nlcore->sess->argReceived;
// 檢查用戶是否登入
$nlcore->sess->userLogged();
$usertoken = $nlcore->sess->userToken;
$usersessioninfo = $nlcore->sess->userSessionInfo;
$userHash = $nlcore->sess->userHash;

$rcode = 4040100;
$returnClientData = null;
if (isset($argReceived["follow"])) { // 關注對方
    $tuser = $argReceived["follow"];
    if (!$nlcore->safe->is_rhash64($tuser)) $nlcore->msg->stopmsg(4020301, "U-" . $tuser);
    $rcode = 3010000;
    if ($nscore->func->i_follow_f($userHash, $tuser)) {
        $rcode = 3010001;
    }
} else if (isset($argReceived["unfollow"])) { // 取關對方
    $tuser = $argReceived["unfollow"];
    if (!$nlcore->safe->is_rhash64($tuser)) $nlcore->msg->stopmsg(4020301, "U-" . $tuser);
    $rcode = 3010002;
    if ($nscore->func->i_unfollow_f($userHash, $tuser)) {
        $rcode = 3010003;
    }
} else {
    $nscore->msg->stopmsg(4020301);
}
$returnClientData = $nscore->msg->m(0, $rcode);
exit($nlcore->sess->encryptargv($returnClientData));
