<?php
/**
 * @description: 修改使用者關係
 * @package NyarukoSNS
*/
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
// IP檢查和解密客戶端提交的資訊
$frequencylimitation = $nscore->cfg->limittime["social"];
$inputInformation = $nlcore->safe->decryptargv("",$frequencylimitation[0],$frequencylimitation[1]);
$jsonarr = $inputInformation[0];
$totpSecret = $inputInformation[1];
// 檢查用戶是否登入
$sessionInformation = $nlcore->safe->userLogged($inputInformation);
$usertoken = $sessionInformation[0];
$usersessioninfo = $sessionInformation[1];
$userhash = $sessionInformation[2];

$rcode = 4040100;
$returnarr = null;
if (isset($jsonarr["follow"])) { // 關注對方
    $tuser = $jsonarr["follow"];
    if (!$nlcore->safe->is_rhash64($tuser)) $nlcore->msg->stopmsg(4020301,$totpSecret,"U-".$tuser);
    $rcode = 3010000;
    if ($nscore->func->i_follow_f($userhash,$tuser,$totpSecret)) {
        $rcode = 3010001;
    }
} else if (isset($jsonarr["unfollow"])) { // 取關對方
    $tuser = $jsonarr["unfollow"];
    if (!$nlcore->safe->is_rhash64($tuser)) $nlcore->msg->stopmsg(4020301,$totpSecret,"U-".$tuser);
    $rcode = 3010002;
    if ($nscore->func->i_unfollow_f($userhash,$tuser,$totpSecret)) {
        $rcode = 3010003;
    }
} else {
    $nscore->msg->stopmsg(4020301,$totpSecret);
}
$returnarr = $nscore->msg->m(0,$rcode);
echo $nlcore->safe->encryptargv($returnarr,$totpSecret);