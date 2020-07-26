<?php
/**
 * @description: 貼文增刪改查
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";

/**
* @description: 獲得tagid，冇有就新建一個
* @param String tag 標簽哈希
* @param String totpsecret 加密用secret
* @param Int tag ID
*/
function gettagid(string $tag, string $totpSecret):int {
    global $nlcore;
    global $nscore;
    $columnArr = ["id","stat","hot","hotmax"];
    $tableStr = $nscore->cfg->tables["tag"];
    $whereDic = [
        "tag" => $tag
    ];
    $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
    $tagid = -1;
    if ($dbreturn[0] == 1010000) { //成功，寫熱度
        $returndata = $dbreturn[2][0];
        $tagid = intval($returndata["id"]);
        $hot = intval($returndata["hot"]) + 1;
        $hotmax = intval($returndata["hotmax"]) + 1;
        $updateDic = [
            "hot" => $hot,
            "hotmax" => $hotmax
        ];
        $whereDic = ["id" => $tagid];
        $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4010302,$totpSecret);
        }
    } else if ($dbreturn[0] == 1010001) { //需要新增
        $insertDic = ["tag" => $tag];
        $result = $nlcore->db->insert($tableStr,$insertDic);
        if ($dbreturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4010303,$totpSecret);
        } else {
            $tagid = $result[1];
        }
    } else {
        $nscore->msg->stopmsg(2040108,$totpSecret);
    }
    return intval($tagid);
}

/**
* @description: 刪除貼文
* @param String post 貼文哈希
* @param String totpsecret 加密用secret
*/
function deletepost(string $post, string $totpSecret):void {
    global $nlcore;
    global $nscore;
    // 遍曆此貼文的全部評論
    $tableStr = $nscore->cfg->tables["comment"];
    $columnArr = ["comment"];
    $whereDic = ["post" => $post];
    $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
    // 遍曆評論唯一哈希
    if ($dbreturn[0] == 1010000) { // 有評論
        $comments = $dbreturn[2];
        foreach ($comments as $comment) { // 遍曆評論
            $commenthash = $comment["comment"];
            // 獲取評論的評論
            $whereDic = [
                "comment" => $commenthash,
                "post" => $commenthash // 被評論的貼文和評論
            ];
            $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic,"","OR");
            if ($dbreturn[0] == 1010000) { // 有評論的評論
                $comments2 = $dbreturn[2];
                $dellikes = []; // 需要批量移除的點贊
                $delcomments = []; // 需要批量移除的評論
                for ($i=0; $i < count($comments2); $i++) { // 遍曆論的評論
                    $commenthash2 = $comments2[$i]["comment"];
                    $dellikeskey = "post*".strval($i);
                    $dellikes[$dellikeskey] = $commenthash2;
                    $delcommentskey = "comment*".strval($i);
                    $delcomments[$delcommentskey] = $commenthash2;
                }
                // 刪除所有貼文評論的點贊
                $tableStr = $nscore->cfg->tables["like"];
                $dbreturn = $nlcore->db->delete($tableStr,$dellikes,"","OR");
                if ($dbreturn[0] >= 2000000) {
                    $nscore->msg->stopmsg(4030100,$totpSecret);
                }
                // 刪除所有貼文評論
                $allcomment = array_merge($dellikes,$delcomments);
                $tableStr = $nscore->cfg->tables["comment"];
                $dbreturn = $nlcore->db->delete($tableStr,$allcomment,"","OR");
                if ($dbreturn[0] >= 2000000) {
                    $nscore->msg->stopmsg(4020202,$totpSecret);
                }
            } else if ($dbreturn[0] == 1010001) { // 無評論的評論
            } else { // 異常
                $nscore->msg->stopmsg(4020201,$totpSecret);
            }
        }
    } else if ($dbreturn[0] == 1010001) { // 無評論
    } else { // 異常
        $nscore->msg->stopmsg(4020200,$totpSecret);
    }

    // 移除貼文點贊
    $tableStr = $nscore->cfg->tables["like"];
    $whereDic = [
        "post" => $post
    ];
    $dbreturn = $nlcore->db->delete($tableStr,$whereDic);
    if ($dbreturn[0] >= 2000000) {
        $nscore->msg->stopmsg(4030101,$totpSecret);
    }
    // 移除貼文
    $tableStr = $nscore->cfg->tables["posts"];
    $whereDic = [
        "post" => $post
    ];
    $dbreturn = $nlcore->db->delete($tableStr,$whereDic);
    if ($dbreturn[0] >= 2000000) {
        $nscore->msg->stopmsg(4010601,$totpSecret);
    }
}

$frequencyLimitation = $nscore->cfg->limittime["timeline"];
$clientInformation = $nlcore->safe->decryptargv("",$frequencyLimitation[0],$frequencyLimitation[1]);
$argReceived = $clientInformation[0];
$totpSecret = $clientInformation[1];
// 檢查用戶是否登入
$usertoken = $argReceived["token"];
if (!$nlcore->safe->is_rhash64($usertoken)) $nlcore->msg->stopmsg(2040402,$totpSecret,"T-".$usertoken);
$userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpSecret);
$userHash = $userpwdtimes["userhash"];
if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpSecret,"T-".$usertoken); //token無效
// 檢查使用哪個使用者操作
if (isset($argReceived["userhash"])) {
    $subuser = $argReceived["userhash"];
    if (strcmp($userHash,$subuser) != 0) {
        if (!$nlcore->safe->is_rhash64($subuser)) $nlcore->msg->stopmsg(2070003,$totpSecret,"S-".$subuser);
        $issub = $nlcore->func->issubaccount($userHash,$subuser)[0];
        if ($issub == false) $nlcore->msg->stopmsg(2070004,$totpSecret,"S-".$subuser);
        $userHash = $subuser;
    }
}
// 檢查請求模式
$postmode = 0; //0发布 1修改 2刪除
$editpost = null;
if (isset($argReceived["editpost"])) { // 編輯模式
    if (!$nlcore->safe->is_rhash64($argReceived["editpost"])) $nscore->msg->stopmsg(4010500,$totpSecret);
    $editpost = $argReceived["editpost"];
    $postmode = 1;
    // 取得要編輯的貼文
    $tableStr = $nscore->cfg->tables["posts"];
    $columnArr = ["id","userhash","title","type","content","tag","files","share","mention","nocomment","noforward","cite"];
    $whereDic = [
        "post" => $editpost
    ];
    $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
    if ($dbreturn[0] != 1010000) {
        $nscore->msg->stopmsg(4010501,$totpSecret,$nowfile);
    }
    $target = $dbreturn[2][0]; // 列數據
    $target["post"] = $editpost;
    $editpost = $target;
} else if (isset($argReceived["delpost"])) { // 刪除模式
    if (!$nlcore->safe->is_rhash64($argReceived["delpost"])) $nscore->msg->stopmsg(4010600,$totpSecret);
    $editpost = $argReceived["delpost"];
    $postmode = 2;
    deletepost($editpost,$totpSecret);
    $returnarr = $nscore->msg->m(0,3000002);
    $returnarr["post"] = $editpost;
    echo $nlcore->safe->encryptargv($returnarr,$totpSecret);
    return;
}
// 檢查標題
$title = $argReceived["title"] ?? null;
$banwords = $nlcore->safe->wordfilter($title,true,$totpSecret);
if ($banwords[0] == true) $nscore->msg->stopmsg(2020300,$totpSecret);
// 檢查媒體類型
$mtype = "TEXT";
if (isset($argReceived["mtype"])) {
    $mtype = strtoupper($argReceived["mtype"]);
    if (!in_array($mtype,["TEXT","IMAGE","VIDEO"])) $nscore->msg->stopmsg(4010001,$totpSecret);
}
// 檢查分享範圍
$share = "PUBLIC"; //暫時隻支援 PUBLIC
if (isset($argReceived["share"])) {
    $share = strtoupper($argReceived["share"]);
    if (!in_array($share,["PUBLIC"])) $nscore->msg->stopmsg(4010001,$totpSecret);
}
// 如果是修改模式，禁止對引用進行修改
if ($postmode == 1 && isset($editpost["cite"]) && isset($argReceived["cite"])) {
    if (strcmp($editpost["cite"],$argReceived["cite"]) != 0) {
        $nscore->msg->stopmsg(4010502,$totpSecret);
    }
}
// 引用其他貼文
$files = null;
$cite = (isset($argReceived["cite"]) && $nlcore->safe->is_rhash64($argReceived["cite"])) ? $argReceived["cite"] : null;
if ($cite) {
    // 查詢目標貼文是否允許轉發
    $tableStr = $nscore->cfg->tables["posts"];
    $columnArr = ["id","userhash","noforward","forwardnum","forwardmax"];
    $whereDic = [
        "post" => $cite
    ];
    $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
    if ($dbreturn[0] != 1010000) {
        $nscore->msg->stopmsg(4010400,$totpSecret,$cite);
    }
    $target = $dbreturn[2][0]; //列數據
    // 檢查用戶是否在對方黑名單中
    if ($nscore->func->h_ban_i($userHash,$target["userhash"],$totpSecret)) {
        $nscore->msg->stopmsg(4010403,$totpSecret);
    }
    $targetid = $target["id"];
    $noforward = $target["noforward"];
    if (intval($noforward) > 0) {
        $nscore->msg->stopmsg(4010401,$totpSecret,$nowfile);
    }
} else {
    // 如果是轉發貼，丟棄文件附件；如果不是轉發貼，檢查文件附件
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
// 檢查正文
$content = $argReceived["content"] ?? "";
if (strlen($content) == 0) {
    if ($cite) $content = "转发贴文";
    else if ($mtype == "IMAGE") $content = "分享图片";
    else if ($mtype == "VIDEO") $content = "分享视频";
}
$contentlen = strlen($content);
if ($contentlen == 0 && !$files && !$cite) $nscore->msg->stopmsg(4010000,$totpSecret);
$banwords = $nlcore->safe->wordfilter($content,true,$totpSecret);
if ($banwords[0] == true) $nlcore->msg->stopmsg(2020300,$totpSecret,"content");
// 檢查提及是否在正文中,並轉換成用戶哈希字符串
$mention = (isset($argReceived["mention"]) && strlen($argReceived["mention"]) > 5) ? $argReceived["mention"] : null;
if ($mention) {
    $mention = explode(",",$argReceived["mention"]);
    for ($i=0; $i < count($mention); $i++) {
        $nowmention = $mention[$i];
        $namearr = explode($nscore->cfg->separator["namelink"],$nowmention);
        $name = $namearr[0];
        if (strstr($content, $name) == false) {
            $nscore->msg->stopmsg(4010101,$totpSecret,$content);
        }
        $mention[$i] = $nlcore->func->fullnickname2userhash($namearr,$totpSecret)[2];
    }
    $mention = implode(",", $mention);
}
// 檢查tag是否在正文中,並轉換成tag
$tag = (isset($argReceived["tag"]) && strlen($argReceived["tag"]) > 1) ? $argReceived["tag"] : null;
if ($tag) {
    $tag = explode(",",$argReceived["tag"]);
    for ($i=0; $i < count($tag); $i++) {
        $nowtag = $tag[$i];
        if (strstr($content, $name) == false) {
            $nscore->msg->stopmsg(4010300,$totpSecret,$content);
        }
        $tag[$i] = gettagid($nowtag,$totpSecret);
    }
    $tag = implode(",", $tag);
}
// 檢查關閉評論
$nocomment = isset($argReceived["nocomment"]) ? intval($argReceived["nocomment"]) : 0;
// 檢查關閉轉發
$noforward = isset($argReceived["noforward"]) ? intval($argReceived["noforward"]) : 0;
if (($nocomment != 0 && $nocomment != 1) || ($noforward != 0 && $noforward != 1)) $nscore->msg->stopmsg(4010001,$totpSecret);
// 創建隨機哈希
$posthash = $nlcore->safe->randhash();
// 过滤正文
// $content = addslashes($content);
if ($cite && $postmode == 0) {
    // 為對方轉發數+1
    $forwardnum = intval($target["forwardnum"]) + 1;
    $forwardmax = intval($target["forwardmax"]) + 1;
    $updateDic = [
        "forwardnum" => $forwardnum,
        "forwardmax" => $forwardmax
    ];
    $whereDic = ["id" => $targetid];
    $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
    if ($dbreturn[0] >= 2000000) {
        $nscore->msg->stopmsg(4010402,$totpSecret);
    }
}
$tableStr = $nscore->cfg->tables["posts"];
$returncode = 4000000;
if ($postmode == 0) {
    // 數據庫操作：發帖
    $insertDic = [
        "post" => $posthash,
        "userhash" => $userHash,
        "title" => $title,
        "type" => $mtype,
        "content" => $content,
        "files" => $files,
        "share" => $share,
        "mention" => $mention,
        "nocomment" => $nocomment,
        "noforward" => $noforward,
        "cite" => $cite
    ];
    $result = $nlcore->db->insert($tableStr,$insertDic);
    if ($result[0] >= 2000000) $nscore->msg->stopmsg(4010003,$totpSecret);
    $returncode = 3000000;
} else if ($postmode == 1) {
    // 數據庫操作：修改貼文
    $updateDic = [
        "title" => $title,
        "type" => $mtype,
        "content" => $content,
        "files" => $files,
        "share" => $share,
        "mention" => $mention,
        "nocomment" => $nocomment,
        "noforward" => $noforward,
        "modified" => date("Y-m-d H:i:s",time())
    ];
    $whereDic = ["id" => $editpost["id"]];
    $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
    if ($result[0] >= 2000000) $nscore->msg->stopmsg(4010503,$totpSecret);
    $returncode = 3000001;
}
$returnarr = $nscore->msg->m(0,$returncode);
$returnarr["post"] = $posthash;
echo $nlcore->safe->encryptargv($returnarr,$totpSecret);