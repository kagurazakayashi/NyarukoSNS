<?
/**
 * @description: 贊
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
// IP檢查和解密客戶端提交的資訊
$inputInformation = $nlcore->safe->decryptargv("signup");
$argReceived = $inputInformation[0];
$totpSecret = $inputInformation[1];
// 檢查用戶是否登入
$sessionInformation = $nlcore->safe->userLogged($inputInformation);
$userHash = $sessionInformation[2];
// 获取输入的POST
$post = ($argReceived["post"] && $nlcore->safe->is_rhash64($argReceived["post"])) ? $argReceived["post"] : $nlcore->msg->stopmsg(2000101,$totpSecret);
$citetype = $argReceived["citetype"] ?? "POST";
// 檢查使用哪個使用者操作
if (isset($argReceived["userhash"]) && strcmp($userHash,$argReceived["userhash"]) != 0) {
    $subuser = $argReceived["userhash"];
    if (!$nlcore->safe->is_rhash64($subuser)) $nlcore->msg->stopmsg(2070003,$totpSecret,"S-".$subuser);
    $issub = $nlcore->func->issubaccount($userHash,$subuser)[0];
    if ($issub == false) $nlcore->msg->stopmsg(2070004,$totpSecret,"S-".$subuser);
    $userHash = $subuser;
}
// 目標
$postsCommentTable = "";
$postsComment = "";
$likeInsertDic = [
    "user" => $userHash,
    "citetype" => $citetype,
    "post" => $post
];
if (strcmp($citetype,"POST") == 0) {
    $postsCommentTable = $nscore->cfg->tables["posts"];
    $postsComment = "post";
} else if (strcmp($citetype,"COMM") == 0) {
    $postsCommentTable = $nscore->cfg->tables["comment"];
    $postsComment = "comment";
} else {
    $nscore->msg->stopmsg(4010001,$totpSecret,$citetype);
}
$okcode = 4030102;
$like = isset($argReceived["like"]) ? intval($argReceived["like"]) : 2;
$likeTable = $nscore->cfg->tables["like"];
if ($like == 1) { // 贊
    // 新增到 贊 表
    $result = $nlcore->db->insertInNull($likeTable,$likeInsertDic);
    if ($result[0] == 1010001) { // 成功
        // 變更目標貼文或評論的記錄數值
        $updateDic = [
            "likenum" => "\$likenum+1",
            "likemax" => "\$likemax+1"
        ];
        $whereDic = [$postsComment => $post];
        $result = $nlcore->db->update($updateDic,$postsCommentTable,$whereDic);
        if ($result[0] >= 2000000) $nscore->msg->stopmsg($okcode,$totpSecret);
        $okcode = 3000300;
    } else if ($result[0] == 1010002) { // 已存在
        $okcode = 3000301;
    } else { // 錯誤
        if ($result[0] >= 2000000) $nscore->msg->stopmsg($okcode,$totpSecret);
    }
    $returnarr = $nscore->msg->m(0,$okcode);
} else if ($like == 0) { // 取消贊
    // 從 贊 表移除
    $whereDic = ["post" => $post];
    $result = $nlcore->db->delete($likeTable,$whereDic);
    if ($result[0] >= 2000000) { // 錯誤
        if ($result[0] >= 2000000) $nscore->msg->stopmsg(4030103,$totpSecret);
    } else if ($result[3] > 0) { // 已刪除
        // 變更目標貼文或評論的記錄數值
        $updateDic = [
            "likenum" => "\$likenum-1",
        ];
        $whereDic = [$postsComment => $post];
        $result = $nlcore->db->update($updateDic,$postsCommentTable,$whereDic);
        if ($result[0] >= 2000000) $nscore->msg->stopmsg($okcode,$totpSecret);
        $okcode = 3000302;
    } else { // (==0)已經刪除
        $okcode = 3000303;
    }
    $returnarr = $nscore->msg->m(0,$okcode);
} else { // 查詢贊
    $limst = isset($argReceived["limst"]) ? intval($argReceived["limst"]) : 0;
    $offset = isset($argReceived["offset"]) ? intval($argReceived["offset"]) : 10;
    $columnArr = ["user"];
    $whereDic = ["post" => $post];
    $result = $nlcore->db->select($columnArr,$likeTable,$whereDic,"","AND",false,["date",true],[$limst,$offset]);
    if ($result[0] >= 2000000) $nscore->msg->stopmsg(4030104,$totpSecret);
    $likeusers = [];
    foreach ($result[2] as $item) {
        array_push($likeusers,$item["user"]);
    }
    $returnarr = $nscore->msg->m(0,3000304);
    $returnarr["users"] = $likeusers;
}
echo $nlcore->safe->encryptargv($returnarr,$totpSecret);
?>