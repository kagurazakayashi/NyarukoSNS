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
$frequencyLimitation = $nscore->cfg->limittime["like"];
$nlcore->sess->decryptargv("",$frequencyLimitation[0],$frequencyLimitation[1]);
$argReceived = $nlcore->sess->argReceived;
// 檢查用戶是否登入
$nlcore->sess->userLogged();
$userHash = $nlcore->sess->userHash;
// 获取输入的POST
$post = ($argReceived["post"] && $nlcore->safe->is_rhash64($argReceived["post"])) ? $argReceived["post"] : $nlcore->msg->stopmsg(2000101);
$citeType = $argReceived["citetype"] ?? "POST";
// 檢查使用哪個使用者操作
if (isset($argReceived["userhash"]) && strcmp($userHash,$argReceived["userhash"]) != 0) {
    $subUser = $argReceived["userhash"];
    if (!$nlcore->safe->is_rhash64($subUser)) $nlcore->msg->stopmsg(2070003,"S-".$subUser);
    $isSub = $nlcore->func->issubaccount($userHash,$subUser)[0];
    if ($isSub == false) $nlcore->msg->stopmsg(2070004,"S-".$subUser);
    $userHash = $subUser;
}
// 目標
$postsCommentTable = "";
$postsComment = "";
$likeInsertDic = [
    "user" => $userHash,
    "citetype" => $citeType,
    "post" => $post
];
if (strcmp(strtoupper($citeType),"POST") == 0) {
    $postsCommentTable = $nscore->cfg->tables["posts"];
    $postsComment = "post";
} else if (strcmp(strtoupper($citeType),"COMM") == 0) {
    $postsCommentTable = $nscore->cfg->tables["comment"];
    $postsComment = "comment";
} else {
    $nscore->msg->stopmsg(4010001,$citeType);
}
$okCode = 4030102;
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
        if ($result[0] >= 2000000) $nscore->msg->stopmsg($okCode);
        $okCode = 3000300;
    } else if ($result[0] == 1010002) { // 已存在
        $okCode = 3000301;
    } else { // 錯誤
        if ($result[0] >= 2000000) $nscore->msg->stopmsg($okCode);
    }
    $returnClientData = $nscore->msg->m(0,$okCode);
} else if ($like == 0) { // 取消贊
    // 從 贊 表移除
    $whereDic = ["post" => $post];
    $result = $nlcore->db->delete($likeTable,$whereDic);
    if ($result[0] >= 2000000) { // 錯誤
        if ($result[0] >= 2000000) $nscore->msg->stopmsg(4030103);
    } else if ($result[3] > 0) { // 已刪除
        // 變更目標貼文或評論的記錄數值
        $updateDic = [
            "likenum" => "\$likenum-1",
        ];
        $whereDic = [$postsComment => $post];
        $result = $nlcore->db->update($updateDic,$postsCommentTable,$whereDic);
        if ($result[0] >= 2000000) $nscore->msg->stopmsg($okCode);
        $okCode = 3000302;
    } else { // (==0)已經刪除
        $okCode = 3000303;
    }
    $returnClientData = $nscore->msg->m(0,$okCode);
} else { // 查詢贊
    $limst = isset($argReceived["limst"]) ? intval($argReceived["limst"]) : 0;
    $offset = isset($argReceived["offset"]) ? intval($argReceived["offset"]) : 10;
    $columnArr = ["user"];
    $whereDic = ["post" => $post];
    $result = $nlcore->db->select($columnArr,$likeTable,$whereDic,"","AND",false,["date",true],[$limst,$offset]);
    if ($result[0] >= 2000000) $nscore->msg->stopmsg(4030104);
    $likeUsers = [];
    foreach ($result[2] as $item) {
        array_push($likeUsers,$item["user"]);
    }
    $returnClientData = $nscore->msg->m(0,3000304);
    $returnClientData["users"] = $likeUsers;
}
exit($nlcore->sess->encryptargv($returnClientData));
?>