<?php
/**
 * @description: 獲取使用者資訊
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
function userinfo($userHash) {
    global $nlcore;
    global $nscore;
    $uinfoTable = $nlcore->cfg->db->tables["info"];
    $zinfoTable = $nscore->cfg->tables["info"];
    $columnArr = [
        [$uinfoTable,"userhash"],
        [$uinfoTable,"belong"],
        [$uinfoTable,"infotype"],
        [$uinfoTable,"name"],
        [$uinfoTable,"nameid"],
        [$uinfoTable,"gender"],
        [$uinfoTable,"pronoun"],
        [$uinfoTable,"address"],
        [$uinfoTable,"profile"],
        [$uinfoTable,"description"],
        [$uinfoTable,"image"],
        [$uinfoTable,"background"],
        [$zinfoTable,"following"],
        [$zinfoTable,"followers"],
        [$zinfoTable,"postnum"]
    ];
    $tableStr = "`".$uinfoTable."` JOIN `".$zinfoTable."` ON `".$uinfoTable."`.`userhash` = `".$zinfoTable."`.`userhash`";
    $whereDic = ["u1_info.userhash" => $userHash];
    $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
    $userinfo = $nlcore->func->getuserinfo($userHash,$result);
    if (count($userinfo) == 0) $nlcore->msg->stopmsg(2070001);
    return $userinfo;
}
// IP檢查和解密客戶端提交的資訊
$frequencyLimitation = $nscore->cfg->limittime["timeline"];
$inputInformation = $nlcore->sess->decryptargv("", $frequencyLimitation[0], $frequencyLimitation[1]);
$argReceived = $nlcore->sess->argReceived;
// 檢查用戶是否登入，若沒有提供 token 則…算了
$userHash = null;
if (isset($argReceived["token"]) && strlen($argReceived["token"]) > 0) {
    $nlcore->sess->userLogged();
    $userHash = $nlcore->sess->userHash;
}
// 取得使用者個性化資訊
$cuser = $argReceived["cuser"] ?? $userHash;
if (!$nlcore->safe->is_rhash64($cuser)) $nlcore->msg->stopmsg(2070000);
$userinfo = userinfo($cuser);
// 獲取子賬戶資訊
if (isset($argReceived["subinfo"])) { // 0 不查詢子賬戶（無 `subaccount` 欄位）
    $subinfomode = intval($argReceived["subinfo"]);
    if ($subinfomode == 1) { // 1 只查獲取賬戶雜湊（不查詢具體資訊）
        $subaccount = $nlcore->func->subaccount($cuser,false);
        $userinfo["subaccount"] = $subaccount;
    } else if ($subinfomode == 2) { // 2 獲取子賬戶的基本資料（僅限使用者系統中的資訊）
        $subaccount = $nlcore->func->subaccount($cuser,true);
        $userinfo["subaccount"] = $subaccount;
    } else if ($subinfomode == 3) { // 3 獲取子賬戶的全部資料（全部資訊）
        $subaccount = $nlcore->func->subaccount($cuser,false);
        for ($i=0; $i < count($subaccount); $i++) {
            $sa = $subaccount[$i];
            $si = userinfo($sa["userhash"]);
            $subaccount[$i] = $si;
        }
        $userinfo["subaccount"] = $subaccount;
    }
}
$returnClientData = [
    "code" => 1000000,
    "uinfo" => $userinfo
];
exit($nlcore->sess->encryptargv($returnClientData));
