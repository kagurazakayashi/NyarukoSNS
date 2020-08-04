<?php
/**
 * @description: 评论增刪改查
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
// IP檢查和解密客戶端提交的資訊
$frequencyLimitation = $nscore->cfg->limittime["comment"];
$inputInformation = $nlcore->safe->decryptargv("",$frequencyLimitation[0],$frequencyLimitation[1]);
$argReceived = $inputInformation[0];
$totpSecret = $inputInformation[1];
// 檢查用戶是否登入
$sessionInformation = $nlcore->safe->userLogged($inputInformation);
$userHash = $sessionInformation[2];
// 檢查使用哪個使用者操作
if (isset($argReceived["userhash"]) && strcmp($userHash,$argReceived["userhash"]) != 0) {
    $subuser = $argReceived["userhash"];
    if (!$nlcore->safe->is_rhash64($subuser)) $nlcore->msg->stopmsg(2070003,$totpSecret,"S-".$subuser);
    $issub = $nlcore->func->issubaccount($userHash,$subuser)[0];
    if ($issub == false) $nlcore->msg->stopmsg(2070004,$totpSecret,"S-".$subuser);
    $userHash = $subuser;
}
// 檢查請求模式
$content = $banwords = $mtype = $files = $post = null;
$delcomment = $argReceived["delcomment"] ?? null;
$citetype = strtoupper($argReceived["citetype"] ?? "POST"); //貼文類型
if (!$delcomment) {
    // 評論內容
    $content = $argReceived["content"] ?? $nscore->msg->stopmsg(4020005,$totpSecret);
    if (strlen($content) > $nscore->cfg->wordlimit["comment"]) { //字長檢查
        $nlcore->msg->stopmsg(4020002,$totpSecret);
    }
    $banwords = $nlcore->safe->wordfilter($content,true,$totpSecret); //敏感詞檢查
    if ($banwords[0] == true) $nlcore->msg->stopmsg(2020300,$totpSecret,"content");
    // 媒體類型
    $mtype = strtoupper($argReceived["mtype"] ?? "TEXT");
    if (!in_array($mtype,["TEXT","IMAGE"])) {
        $nscore->msg->stopmsg(4020003,$totpSecret);
    }
    // 檔案路徑列表
    $files = (isset($argReceived["files"]) && strlen($argReceived["files"]) >= 32) ? $argReceived["files"] : null;
    if ($files) {
        $filesarr = explode(",",$argReceived["files"]);
        foreach ($filesarr as $nowfile) {
            if (!$nlcore->safe->ismediafilename($nowfile)) {
                $nscore->msg->stopmsg(4010200,$totpSecret,$nowfile);
            }
        }
    }
}
// 修改評論
if (isset($argReceived["editcomment"])) {
    if (strlen($argReceived["editcomment"]) == 0 || !$nlcore->safe->is_rhash64($argReceived["editcomment"])) {
        $nscore->msg->stopmsg(4020007,$totpSecret,$nowfile);
    }
    // 數據庫操作：修改貼文
    $tableStr = $nscore->cfg->tables["comment"];
    $updateDic = [
        "modified" => date("Y-m-d H:i:s",time()),
        "content" => $content,
        "type" => $mtype,
        "files" => $files
    ];
    $whereDic = [
        "comment" => $argReceived["editcomment"],
        "userhash" => $userHash
    ];
    $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
    if ($result[0] >= 2000000) $nscore->msg->stopmsg(4010503,$totpSecret);
    $returnarr = $nscore->msg->m(0,3000101);
    $returnarr["comment"] = $argReceived["editcomment"];
    die($nlcore->safe->encryptargv($returnarr,$totpSecret));
}
// 目標 貼文/評論 唯一哈希值
if ($delcomment) {
    if (!$nlcore->safe->is_rhash64($delcomment)) $nscore->msg->stopmsg(4020003,$totpSecret);
    $tableStr = $nscore->cfg->tables["comment"];
    $columnArr = ["post"];
    $whereDic = ["comment" => $delcomment];
    $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
    if ($dbreturn[0] == 1010000) {
        $post = $dbreturn[2][0]["post"];
    } else {
        $nscore->msg->stopmsg(4020100,$totpSecret);
    }
} else {
    $post = (isset($argReceived["post"]) && $nlcore->safe->is_rhash64($argReceived["post"])) ? $argReceived["post"] : $nscore->msg->stopmsg(4020000,$totpSecret);
}
// 評論目標
$iscomment = false;
if (strcmp($citetype,"POST") == 0) {
    $iscomment = false;
} else if (strcmp($citetype,"COMM") == 0) {
    $iscomment = true;
} else {
    $nscore->msg->stopmsg(4010001,$totpSecret,$citetype);
}
// 查詢已有評論數量
$columnArr = ["commentnum","commentmax"];
$tableposts = $nscore->cfg->tables["posts"];
$whereDic = ["post" => $post];
if ($iscomment) {
    array_push($columnArr,"post");
    $tableposts = $nscore->cfg->tables["comment"];
    $whereDic = ["comment" => $post];
}
$dbreturn = $nlcore->db->select($columnArr,$tableposts,$whereDic);
if ($dbreturn[0] == 1010000) {
    $dbreturndata = $dbreturn[2][0]; //列數據
    if ($iscomment) {
        $post = $dbreturndata["post"]; //評論的評論視為評論貼文
    }
    // 刪除評論
    if ($delcomment) {
        $commentnum = intval($dbreturndata["commentnum"]) - 1;
        if ($commentnum < 0) $commentnum = 0;
        $tableStr = $nscore->cfg->tables["comment"];
        $whereDic = [
            "comment" => $delcomment,
            "post" => $post
        ];
        $dbreturn = $nlcore->db->delete($tableStr,$whereDic,"","OR");
        if ($dbreturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4020202,$totpSecret,$nowfile);
        }
    } else {
        $commentnum = intval($dbreturndata["commentnum"]) + 1;
        $commentmax = intval($dbreturndata["commentmax"]) + 1;
        $tablecomment = $nscore->cfg->tables["comment"];
        // 資料庫操作：發表評論
        $commenthash = $nlcore->safe->randhash();
        $insertDic = [
            "comment" => $commenthash,
            "userhash" => $userHash,
            "citetype" => $citetype,
            "post" => $post,
            "content" => $content,
            "type" => $mtype,
            "files" => $files,
            "storey" => $commentmax
        ];
        $result = $nlcore->db->insert($tablecomment,$insertDic);
        if ($result[0] >= 2000000) $nscore->msg->stopmsg(4020004,$totpSecret);
    }
    // 更新貼文評論數
    $updateDic = ["commentnum" => $commentnum];
    $okcode = 3000102;
    if ($delcomment == null) {
        $updateDic["commentmax"] = $commentmax;
        $okcode = 3000100;
    }
    $whereDic = ["post" => $post];
    $result = $nlcore->db->update($updateDic,$tableposts,$whereDic);
    if ($result[0] >= 2000000) $nscore->msg->stopmsg(4020006,$totpSecret);
    // 完成返回
    $returnarr = $nscore->msg->m(0,$okcode);
    $returnarr["comment"] = $commenthash ?? $delcomment;
    echo $nlcore->safe->encryptargv($returnarr,$totpSecret);
} else if ($dbreturn[0] == 1010001) {
    $nscore->msg->stopmsg(4020001,$totpSecret,$post);
} else {
    $nscore->msg->stopmsg(4020000,$totpSecret);
}
?>