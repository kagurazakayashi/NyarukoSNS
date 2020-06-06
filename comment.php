<?php
/**
 * @description: 评论增刪改查
 * @package NyarukoSNS
*/
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
class comment {
    function init():void {
        global $nlcore;
        global $nscore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv($nscore->cfg->limittime["comment"]);
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];
        // 檢查用戶是否登入
        $usertoken = $jsonarr["token"];
        if (!$nlcore->safe->is_rhash64($usertoken)) $nlcore->msg->stopmsg(2040402,$totpsecret,"COMM".$usertoken);
        $userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpsecret);
        $userhash = $userpwdtimes["userhash"];
        if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpsecret,"COMM".$usertoken); //token無效
        // 檢查請求模式
        $content = $banwords = $mtype = $files = $post = null;
        $delcomment = $jsonarr["delcomment"] ?? null;
        $citetype = strtoupper($jsonarr["citetype"] ?? "POST"); //貼文類型
        if (!$delcomment) {
            // 評論內容
            $content = $jsonarr["content"] ?? $nscore->msg->stopmsg(4020005,$totpsecret);
            if (strlen($content) > $nscore->cfg->wordlimit["comment"]) { //字長檢查
                $nlcore->msg->stopmsg(4020002,$totpsecret);
            }
            $banwords = $nlcore->safe->wordfilter($content,true,$totpsecret); //敏感詞檢查
            if ($banwords[0] == true) $nlcore->msg->stopmsg(2020300,$totpsecret,"content");
            // 媒體類型
            $mtype = strtoupper($jsonarr["mtype"] ?? "TEXT");
            if (!in_array($mtype,["TEXT","IMAGE"])) {
                $nscore->msg->stopmsg(4020003,$totpsecret);
            }
            // 檔案路徑列表
            $files = (isset($jsonarr["files"]) && strlen($jsonarr["files"]) >= 32) ? $jsonarr["files"] : null;
            if ($files) {
                $filesarr = explode(",",$jsonarr["files"]);
                foreach ($filesarr as $nowfile) {
                    if (!$nlcore->safe->ismediafilename($nowfile)) {
                        $nscore->msg->stopmsg(4010200,$totpsecret,$nowfile);
                    }
                }
            }
        }
        // 修改評論
        if (isset($jsonarr["editcomment"])) {
            if (strlen($jsonarr["editcomment"]) == 0 || !$nlcore->safe->is_rhash64($jsonarr["editcomment"])) {
                $nscore->msg->stopmsg(4020007,$totpsecret,$nowfile);
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
                "comment" => $jsonarr["editcomment"],
                "user" => $userhash
            ];
            $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
            if ($result[0] >= 2000000) $nscore->msg->stopmsg(4010503,$totpsecret);
            $returnarr = $nscore->msg->m(0,3000101);
            $returnarr["comment"] = $jsonarr["editcomment"];
            die($nlcore->safe->encryptargv($returnarr,$totpsecret));
        }
        // 目標 貼文/評論 唯一哈希值
        if ($delcomment) {
            if (!$nlcore->safe->is_rhash64($delcomment)) $nscore->msg->stopmsg(4020003,$totpsecret);
            $tableStr = $nscore->cfg->tables["comment"];
            $columnArr = ["post"];
            $whereDic = ["comment" => $delcomment];
            $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
            if ($dbreturn[0] == 1010000) {
                $post = $dbreturn[2][0]["post"];
            } else {
                $nscore->msg->stopmsg(4020100,$totpsecret);
            }
        } else {
            $post = (isset($jsonarr["post"]) && $nlcore->safe->is_rhash64($jsonarr["post"])) ? $jsonarr["post"] : $nscore->msg->stopmsg(4020000,$totpsecret);
        }
        // 評論目標
        $iscomment = false;
        if (strcmp($citetype,"POST") == 0) {
            $iscomment = false;
        } else if (strcmp($citetype,"COMM") == 0) {
            $iscomment = true;
        } else {
            $nscore->msg->stopmsg(4010001,$totpsecret,$citetype);
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
                    $nscore->msg->stopmsg(4020202,$totpsecret,$nowfile);
                }
            } else {
                $commentnum = intval($dbreturndata["commentnum"]) + 1;
                $commentmax = intval($dbreturndata["commentmax"]) + 1;
                $tablecomment = $nscore->cfg->tables["comment"];
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
                if ($result[0] >= 2000000) $nscore->msg->stopmsg(4020004,$totpsecret);
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
            if ($result[0] >= 2000000) $nscore->msg->stopmsg(4020006,$totpsecret);
            // 完成返回
            $returnarr = $nscore->msg->m(0,$okcode);
            $returnarr["comment"] = $commenthash ?? $delcomment;
            echo $nlcore->safe->encryptargv($returnarr,$totpsecret);
        } else if ($dbreturn[0] == 1010001) {
            $nscore->msg->stopmsg(4020001,$totpsecret,$post);
        } else {
            $nscore->msg->stopmsg(4020000,$totpsecret);
        }
    }
}
$comment = new comment();
$comment->init();
?>