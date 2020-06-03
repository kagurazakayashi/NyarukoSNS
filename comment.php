<?php
/**
 * @description: 评论增刪改查
 * @package NyarukoSNS
*/
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."zezecore.class.php";
require_once $usersrc."nyacore.class.php";
class comment {
    function init():void {
        global $nlcore;
        global $zecore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv($zecore->cfg->limittime["comment"]);
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];
        // 檢查用戶是否登入
        $usertoken = $jsonarr["token"];
        $nlcore->safe->is_rhash64($usertoken);
        $userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpsecret);
        $userhash = $userpwdtimes["userhash"];
        if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpsecret); //token無效
        // 檢查請求模式
        $postmode = 0; //0发布 1修改 2刪除
        $editpost = null;
        $citetype = strtoupper($jsonarr["citetype"] ?? "POST"); //貼文類型
        // 評論內容
        $content = $jsonarr["content"] ?? $nlcore->msg->stopmsg(4020005,$totpsecret);
        if (strlen($content) > $zecore->cfg->wordlimit["comment"]) { //字長檢查
            $nlcore->msg->stopmsg(4020002,$totpsecret);
        }
        $banwords = $nlcore->safe->wordfilter($content,true,$totpsecret); //敏感詞檢查
        if ($banwords[0] == true) $nlcore->msg->stopmsg(2020300,$totpsecret,"content");
        // 媒體類型
        $mtype = strtoupper($jsonarr["mtype"] ?? "TEXT");
        if (!in_array($mtype,["TEXT","IMAGE"])) {
            $zecore->msg->stopmsg(4020003,$totpsecret);
        }
        // 檔案路徑列表
        $files = (isset($jsonarr["files"]) && strlen($jsonarr["files"]) >= 32) ? $jsonarr["files"] : null;
        if ($files) {
            $filesarr = explode(",",$jsonarr["files"]);
            foreach ($filesarr as $nowfile) {
                if (!$nlcore->safe->ismediafilename($nowfile)) {
                    $zecore->msg->stopmsg(4010200,$totpsecret,$nowfile);
                }
            }
        }

        // TODO: 修改評論
        // TODO: 刪除評論

        // 目標 貼文/評論 唯一哈希值
        $post = (isset($jsonarr["post"]) && $nlcore->safe->is_rhash64($jsonarr["post"])) ? $jsonarr["post"] : null;
        if (!$post) {
            $zecore->msg->stopmsg(4020000,$totpsecret);
        }
        // 評論目標
        $iscomment = -1;
        if (strcmp($citetype,"POST") == 0) {
            $iscomment = false;
        } else if (strcmp($citetype,"COMM") == 0) {
            $iscomment = true;
        } else {
            $zecore->msg->stopmsg(4010001,$totpsecret);
        }
        // 查詢已有評論數量
        $columnArr = ["commentnum","commentmax"];
        $tableposts = $zecore->cfg->tables["posts"];
        $whereDic = ["post" => $post];
        if ($iscomment) {
            array_push($columnArr,"post");
            $tableposts = $zecore->cfg->tables["comment"];
            $whereDic = ["comment" => $post];
        }
        $dbreturn = $nlcore->db->select($columnArr,$tableposts,$whereDic);
        if ($dbreturn[0] == 1010000) {
            $dbreturndata = $dbreturn[2][0]; //列數據
            $commentnum = intval($dbreturndata["commentnum"]) + 1;
            $commentmax = intval($dbreturndata["commentmax"]) + 1;
            if ($iscomment) {
                $post = $dbreturndata["post"]; //評論的評論視為評論貼文
            }
            $tablecomment = $zecore->cfg->tables["comment"];
            // 資料庫操作：發表評論
            $commenthash = $nlcore->safe->randhash();
            $insertDic = [
                "comment" => $commenthash,
                "user" => $userhash,
                "citetype" => $citetype,
                "post" => $post,
                "content" => $content,
                "type" => $mtype,
                "files" => $files,
                "storey" => $commentmax
            ];
            $result = $nlcore->db->insert($tablecomment,$insertDic);
            if ($result[0] >= 2000000) $zecore->msg->stopmsg(4020004,$totpsecret);
            // 更新貼文評論數
            $updateDic = [
                "commentnum" => $commentnum,
                "commentmax" => $commentmax
            ];
            $whereDic = [
                "post" => $post
            ];
            $result = $nlcore->db->update($updateDic,$tableposts,$whereDic);
            if ($result[0] >= 2000000) $zecore->msg->stopmsg(4020006,$totpsecret);
            // 完成返回
            $returnarr = $zecore->msg->m(0,3000100);
            $returnarr["comment"] = $commenthash;
            echo $nlcore->safe->encryptargv($returnarr,$totpsecret);
        } else if ($dbreturn[0] == 1010001) {
            $zecore->msg->stopmsg(4020001,$totpsecret);
        } else {
            $zecore->msg->stopmsg(4020000,$totpsecret);
        }
    }
}
$comment = new comment();
$comment->init();
?>