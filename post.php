<?php
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyascore.class.php";
require_once $usersrc."nyacore.class.php";
class addpost {
    function add() {
        global $nlcore;
        global $zecore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv($zecore->cfg->limittime["post"]);
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];
        // 检查用户是否登录
        $usertoken = $jsonarr["token"];
        $nlcore->safe->is_rhash64($usertoken);
        $userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpsecret);
        if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpsecret); //token无效
        // 检查标题
        $title = $jsonarr["title"] ?? null;
        $banwords = $nlcore->safe->wordfilter($title,true,$totpsecret);
        if ($banwords[0] == true) $zecore->msg->stopmsg(2020300,$totpsecret,"title");
        // 检查媒体类型
        $mtype = "TEXT";
        if (isset($jsonarr["mtype"])) {
            $mtype = strtoupper($jsonarr["mtype"]);
            if (!in_array($mtype,["TEXT","IMAGE","VIDEO"])) $zecore->msg->stopmsg(4010001,$totpsecret);
        }
        // 检查分享范围
        $share = "PUBLIC"; //暂时只支持 PUBLIC
        if (isset($jsonarr["share"])) {
            $share = strtoupper($jsonarr["share"]);
            if (!in_array($share,["PUBLIC"])) $zecore->msg->stopmsg(4010001,$totpsecret);
        }
        // 检查文件
        $files = (isset($jsonarr["files"]) && strlen($jsonarr["files"]) >= 32) ? $jsonarr["files"] : null;
        if ($files) {
            $filesarr = explode(",",$jsonarr["files"]);
            foreach ($filesarr as $nowfile) {
                if (!preg_match("/[\w\/]*[\d]{11}_[\w]{32}/",$nowfile)) {
                    $zecore->msg->stopmsg(4010200,$totpsecret,$nowfile);
                }
            }
        }
        // 引用其他贴文
        $cite = (isset($jsonarr["cite"]) && $nlcore->safe->is_rhash64($jsonarr["cite"])) ? $jsonarr["cite"] : null;
        if ($cite) {
            // 查询目标贴文是否允许转发
            $tableStr = $zecore->cfg->tables["posts"];
            $columnArr = ["id","user","noforward","forwardnum","forwardmax"];
            $whereDic = [
                "post" => $cite
            ];
            $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
            if ($dbreturn[0] != 1010000) {
                $zecore->msg->stopmsg(4010400,$totpsecret,$nowfile);
            }
            $target = $dbreturn[2][0];
            //TODO: 检查用户是否在对方黑名单中

            $targetid = $target["id"];
            $noforward = $target["noforward"];
            if (intval($noforward) > 0) {
                $zecore->msg->stopmsg(4010401,$totpsecret,$nowfile);
            }
            // 为对方转发数+1
            $forwardnum = intval($target["forwardnum"]) + 1;
            $forwardmax = intval($target["forwardmax"]) + 1;
            $updateDic = [
                "forwardnum" => $forwardnum,
                "forwardmax" => $forwardmax
            ];
            $whereDic = ["id" => $targetid];
            $result = $nlcore->db->update($updateDic,$tableStr,$whereDic);
            if ($dbreturn[0] >= 2000000) {
                $zecore->msg->stopmsg(4010402,$totpsecret);
            }
        }
        // 检查正文
        $content = $jsonarr["content"] ?? "";
        if (strlen($content) == 0) {
            if ($cite) $content = "转发贴文";
            else if ($mtype == "IMAGE") $content = "分享图片";
            else if ($mtype == "VIDEO") $content = "分享视频";
        }
        $contentlen = strlen($content);
        if ($contentlen == 0 && !$files && !$cite) $zecore->msg->stopmsg(4010000,$totpsecret);
        $banwords = $nlcore->safe->wordfilter($content,true,$totpsecret);
        if ($banwords[0] == true) $zecore->msg->stopmsg(2020300,$totpsecret,"content");
        // 检查提及是否在正文中,并转换成用户哈希字符串
        $mention = (isset($jsonarr["mention"]) && strlen($jsonarr["mention"]) > 5) ? $jsonarr["mention"] : null;
        if ($mention) {
            $mention = explode(",",$jsonarr["mention"]);
            for ($i=0; $i < count($mention); $i++) {
                $nowmention = $mention[$i];
                $namearr = explode("#",$nowmention);
                $name = $namearr[0];
                if (strstr($content, $name) == false) {
                    $zecore->msg->stopmsg(4010101,$totpsecret,$content);
                }
                $mention[$i] = $nlcore->func->fullnickname2userhash($namearr,$totpsecret)[2];
            }
            $mention = implode(",", $mention);
        }
        // 检查tag是否在正文中,并转换成tag
        $tag = (isset($jsonarr["tag"]) && strlen($jsonarr["tag"]) > 1) ? $jsonarr["tag"] : null;
        if ($tag) {
            $tag = explode(",",$jsonarr["tag"]);
            for ($i=0; $i < count($tag); $i++) {
                $nowtag = $tag[$i];
                if (strstr($content, $name) == false) {
                    $zecore->msg->stopmsg(4010300,$totpsecret,$content);
                }
                $tag[$i] = $this->gettagid($nowtag,$totpsecret);
            }
            $tag = implode(",", $tag);
        }
        // 检查关闭评论
        $nocomment = isset($jsonarr["nocomment"]) ? intval($jsonarr["nocomment"]) : 0;
        // 检查关闭转发
        $noforward = isset($jsonarr["noforward"]) ? intval($jsonarr["noforward"]) : 0;
        if (($nocomment != 0 && $nocomment != 1) || ($noforward != 0 && $noforward != 1)) $zecore->msg->stopmsg(4010001,$totpsecret);
        // 其他标识
        $posthash = $nlcore->safe->randhash();
        $userhash = $userpwdtimes["userhash"];
        // 封装正文
        $content = addslashes($content);
        // 数据库
        $tableStr = $zecore->cfg->tables["posts"];
        $insertDic = [
            "post" => $posthash,
            "user" => $userhash,
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
        if ($result[0] >= 2000000) $zecore->msg->stopmsg(2040108,$totpsecret);
        $returnarr = $zecore->msg->m(0,3000000);
        $returnarr["post"] = $posthash;
        echo $nlcore->safe->encryptargv($returnarr,$totpsecret);
    }

    //获得tagid，没有就新建一个
    function gettagid($tag,$totpsecret) {
        global $nlcore;
        global $zecore;
        $columnArr = ["id","stat","hot","hotmax"];
        $tableStr = $zecore->cfg->tables["tag"];
        $whereDic = [
            "tag" => $tag
        ];
        $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        $tagid = -1;
        if ($dbreturn[0] == 1010000) { //成功，写热度
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
                $zecore->msg->stopmsg(4010302,$totpsecret);
            }
        } else if ($dbreturn[0] == 1010001) { //需要新增
            $insertDic = ["tag" => $tag];
            $result = $nlcore->db->insert($tableStr,$insertDic);
            if ($dbreturn[0] >= 2000000) {
                $zecore->msg->stopmsg(4010303,$totpsecret);
            } else {
                $tagid = $result[1];
            }
        } else {
            $zecore->msg->stopmsg(2040108,$totpsecret);
        }
        return $tagid;
    }

    //转发贴文
    function forward() {
        global $nlcore;
        global $zecore;

    }
}
$addpost = new addpost();
$addpost->add();