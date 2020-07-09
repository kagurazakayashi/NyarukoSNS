<?php
/**
 * @description: 獲取使用者資訊
 * @package NyarukoSNS
*/
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
function userinfo($totpSecret,$userhash) {
    global $nlcore;
    global $nscore;
    $infoTable = $nlcore->cfg->db->tables["info"];
    $zinfoTable = $nscore->cfg->tables["info"];
    $columnArr = [
        [$infoTable,"userhash"],
        [$infoTable,"belong"],
        [$infoTable,"infotype"],
        [$infoTable,"name"],
        [$infoTable,"nameid"],
        [$infoTable,"gender"],
        [$infoTable,"pronoun"],
        [$infoTable,"address"],
        [$infoTable,"profile"],
        [$infoTable,"description"],
        [$infoTable,"image"],
        [$infoTable,"background"],
        [$zinfoTable,"following"],
        [$zinfoTable,"followers"],
        [$zinfoTable,"postnum"],
        [$zinfoTable,"race"]
    ];
    $tableStr = "`".$infoTable."` JOIN `".$zinfoTable."` ON `".$infoTable."`.`userhash` = `".$zinfoTable."`.`userhash`";
    $whereDic = ["u1_info.userhash" => $userhash];
    $result = $nlcore->db->select($columnArr,$tableStr,$whereDic);
    $userinfo = $nlcore->func->getuserinfo($userhash,$totpSecret,$result);
    if (count($userinfo) == 0) $nlcore->msg->stopmsg(2070001,$totpSecret);
    return $userinfo;
}
// IP檢查和解密客戶端提交的資訊
$frequencylimitation = $nscore->cfg->limittime["timeline"];
$inputInformation = $nlcore->safe->decryptargv("",$frequencylimitation[0],$frequencylimitation[1]);
$jsonarr = $inputInformation[0];
$totpSecret = $inputInformation[1];
$totptoken = $inputInformation[2];
$ipid = $inputInformation[3];
$appid = $inputInformation[4];
// 檢查用戶是否登入，若沒有提供 token 則…算了
$userhash = null;
if (isset($jsonarr["token"]) && strlen($jsonarr["token"]) > 0) {
    $sessionInformation = $nlcore->safe->userLogged($inputInformation);
    // $usertoken = $sessionInformation[0];
    // $usersessioninfo = $sessionInformation[1];
    $userhash = $sessionInformation[2];
}
// 取得使用者個性化資訊
$cuser = $jsonarr["cuser"] ?? $userhash;
if (!$nlcore->safe->is_rhash64($cuser)) $nlcore->msg->stopmsg(2070000,$totpSecret);
$userinfo = userinfo($totpSecret,$cuser);
// 獲取子賬戶資訊
if (isset($jsonarr["subinfo"])) { // 0 不查詢子賬戶（無 `subaccount` 欄位）
    $subinfomode = intval($jsonarr["subinfo"]);
    if ($subinfomode == 1) { // 1 只查獲取賬戶雜湊（不查詢具體資訊）
        $subaccount = $nlcore->func->subaccount($cuser,$totpSecret,false);
        $userinfo["subaccount"] = $subaccount;
    } else if ($subinfomode == 2) { // 2 獲取子賬戶的基本資料（僅限使用者系統中的資訊）
        $subaccount = $nlcore->func->subaccount($cuser,$totpSecret,true);
        $userinfo["subaccount"] = $subaccount;
    } else if ($subinfomode == 3) { // 3 獲取子賬戶的全部資料（全部資訊）
        $subaccount = $nlcore->func->subaccount($cuser,$totpSecret,false);
        for ($i=0; $i < count($subaccount); $i++) {
            $sa = $subaccount[$i];
            $si = userinfo($totpSecret,$sa["userhash"]);
            $subaccount[$i] = $si;
        }
        $userinfo["subaccount"] = $subaccount;
    }
}
$returnjson = [
    "code" => 1000000,
    "uinfo" => $userinfo
];
echo $nlcore->safe->encryptargv($returnjson,$totpSecret);
?>