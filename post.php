<?php
/**
 * @description: 貼文增刪改查
 * @package NyarukoSNS
*/
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
class post {
    function init():void {
        global $nlcore;
        global $nscore;
        $jsonarrTotpsecret = $nlcore->safe->decryptargv($nscore->cfg->limittime["post"]);
        $jsonarr = $jsonarrTotpsecret[0];
        $totpsecret = $jsonarrTotpsecret[1];
        $totptoken = $jsonarrTotpsecret[2];
        $ipid = $jsonarrTotpsecret[3];
        $appid = $jsonarrTotpsecret[4];
        // 檢查用戶是否登入
        $usertoken = $jsonarr["token"];
        if (!$nlcore->safe->is_rhash64($usertoken)) $nlcore->msg->stopmsg(2040402,$totpsecret,"T-".$usertoken);
        $userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpsecret);
        $userhash = $userpwdtimes["userhash"];
        if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpsecret,"T-".$usertoken); //token無效
        // 檢查使用哪個使用者操作
        if (isset($jsonarr["userhash"])) {
            $subuser = $jsonarr["userhash"];
            if (strcmp($userhash,$subuser) != 0) {
                if (!$nlcore->safe->is_rhash64($subuser)) $nlcore->msg->stopmsg(2070003,$totpsecret,"S-".$subuser);
                $issub = $nlcore->func->issubaccount($userhash,$subuser)[0];
                if ($issub == false) $nlcore->msg->stopmsg(2070004,$totpsecret,"S-".$subuser);
                $userhash = $subuser;
            }
        }
        // 檢查請求模式
        $postmode = 0; //0发布 1修改 2刪除
        $editpost = null;
        if (isset($jsonarr["editpost"])) { // 編輯模式
            if (!$nlcore->safe->is_rhash64($jsonarr["editpost"])) $nscore->msg->stopmsg(4010500,$totpsecret);
            $editpost = $jsonarr["editpost"];
            $postmode = 1;
            // 取得要編輯的貼文
            $tableStr = $nscore->cfg->tables["posts"];
            $columnArr = ["id","userhash","title","type","content","tag","files","share","mention","nocomment","noforward","cite"];
            $whereDic = [
                "post" => $editpost
            ];
            $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
            if ($dbreturn[0] != 1010000) {
                $nscore->msg->stopmsg(4010501,$totpsecret,$nowfile);
            }
            $target = $dbreturn[2][0]; // 列數據
            $target["post"] = $editpost;
            $editpost = $target;
        } else if (isset($jsonarr["delpost"])) { // 刪除模式
            if (!$nlcore->safe->is_rhash64($jsonarr["delpost"])) $nscore->msg->stopmsg(4010600,$totpsecret);
            $editpost = $jsonarr["delpost"];
            $postmode = 2;
            $this->deletepost($editpost,$totpsecret);
            $returnarr = $nscore->msg->m(0,3000002);
            $returnarr["post"] = $editpost;
            echo $nlcore->safe->encryptargv($returnarr,$totpsecret);
            return;
        }
        // 檢查標題
        $title = $jsonarr["title"] ?? null;
        $banwords = $nlcore->safe->wordfilter($title,true,$totpsecret);
        if ($banwords[0] == true) $nscore->msg->stopmsg(2020300,$totpsecret);
        // 檢查媒體類型
        $mtype = "TEXT";
        if (isset($jsonarr["mtype"])) {
            $mtype = strtoupper($jsonarr["mtype"]);
            if (!in_array($mtype,["TEXT","IMAGE","VIDEO"])) $nscore->msg->stopmsg(4010001,$totpsecret);
        }
        // 檢查分享範圍
        $share = "PUBLIC"; //暫時隻支援 PUBLIC
        if (isset($jsonarr["share"])) {
            $share = strtoupper($jsonarr["share"]);
            if (!in_array($share,["PUBLIC"])) $nscore->msg->stopmsg(4010001,$totpsecret);
        }
        // 如果是修改模式，禁止對引用進行修改
        if ($postmode == 1 && isset($editpost["cite"]) && isset($jsonarr["cite"])) {
            if (strcmp($editpost["cite"],$jsonarr["cite"]) != 0) {
                $nscore->msg->stopmsg(4010502,$totpsecret);
            }
        }
        // 引用其他貼文
        $files = null;
        $cite = (isset($jsonarr["cite"]) && $nlcore->safe->is_rhash64($jsonarr["cite"])) ? $jsonarr["cite"] : null;
        if ($cite) {
            // 查詢目標貼文是否允許轉發
            $tableStr = $nscore->cfg->tables["posts"];
            $columnArr = ["id","userhash","noforward","forwardnum","forwardmax"];
            $whereDic = [
                "post" => $cite
            ];
            $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
            if ($dbreturn[0] != 1010000) {
                $nscore->msg->stopmsg(4010400,$totpsecret,$nowfile);
            }
            $target = $dbreturn[2][0]; //列數據
            // 檢查用戶是否在對方黑名單中
            if ($nscore->func->h_ban_i($userhash,$target["userhash"],$totpsecret)) {
                $nscore->msg->stopmsg(4010403,$totpsecret);
            }
            $targetid = $target["id"];
            $noforward = $target["noforward"];
            if (intval($noforward) > 0) {
                $nscore->msg->stopmsg(4010401,$totpsecret,$nowfile);
            }
        } else {
            // 如果是轉發貼，丟棄文件附件；如果不是轉發貼，檢查文件附件
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
        // 檢查正文
        $content = $jsonarr["content"] ?? "";
        if (strlen($content) == 0) {
            if ($cite) $content = "转发贴文";
            else if ($mtype == "IMAGE") $content = "分享图片";
            else if ($mtype == "VIDEO") $content = "分享视频";
        }
        $contentlen = strlen($content);
        if ($contentlen == 0 && !$files && !$cite) $nscore->msg->stopmsg(4010000,$totpsecret);
        $banwords = $nlcore->safe->wordfilter($content,true,$totpsecret);
        if ($banwords[0] == true) $nlcore->msg->stopmsg(2020300,$totpsecret,"content");
        // 檢查提及是否在正文中,並轉換成用戶哈希字符串
        $mention = (isset($jsonarr["mention"]) && strlen($jsonarr["mention"]) > 5) ? $jsonarr["mention"] : null;
        if ($mention) {
            $mention = explode(",",$jsonarr["mention"]);
            for ($i=0; $i < count($mention); $i++) {
                $nowmention = $mention[$i];
                $namearr = explode($zecore->cfg->separator["namelink"],$nowmention);
                $name = $namearr[0];
                if (strstr($content, $name) == false) {
                    $nscore->msg->stopmsg(4010101,$totpsecret,$content);
                }
                $mention[$i] = $nlcore->func->fullnickname2userhash($namearr,$totpsecret)[2];
            }
            $mention = implode(",", $mention);
        }
        // 檢查tag是否在正文中,並轉換成tag
        $tag = (isset($jsonarr["tag"]) && strlen($jsonarr["tag"]) > 1) ? $jsonarr["tag"] : null;
        if ($tag) {
            $tag = explode(",",$jsonarr["tag"]);
            for ($i=0; $i < count($tag); $i++) {
                $nowtag = $tag[$i];
                if (strstr($content, $name) == false) {
                    $nscore->msg->stopmsg(4010300,$totpsecret,$content);
                }
                $tag[$i] = $this->gettagid($nowtag,$totpsecret);
            }
            $tag = implode(",", $tag);
        }
        // 檢查關閉評論
        $nocomment = isset($jsonarr["nocomment"]) ? intval($jsonarr["nocomment"]) : 0;
        // 檢查關閉轉發
        $noforward = isset($jsonarr["noforward"]) ? intval($jsonarr["noforward"]) : 0;
        if (($nocomment != 0 && $nocomment != 1) || ($noforward != 0 && $noforward != 1)) $nscore->msg->stopmsg(4010001,$totpsecret);
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
                $nscore->msg->stopmsg(4010402,$totpsecret);
            }
        }
        $tableStr = $nscore->cfg->tables["posts"];
        $returncode = 4000000;
        if ($postmode == 0) {
            // 數據庫操作：發帖
            $insertDic = [
                "post" => $posthash,
                "userhash" => $userhash,
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
            if ($result[0] >= 2000000) $nscore->msg->stopmsg(4010003,$totpsecret);
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
            if ($result[0] >= 2000000) $nscore->msg->stopmsg(4010503,$totpsecret);
            $returncode = 3000001;
        }
        $returnarr = $nscore->msg->m(0,$returncode);
        $returnarr["post"] = $posthash;
        echo $nlcore->safe->encryptargv($returnarr,$totpsecret);
    }

    /**
    * @description: 獲得tagid，冇有就新建一個
    * @param String tag 標簽哈希
    * @param String totpsecret 加密用secret
    * @param Int tag ID
    */
    function gettagid(string $tag, string $totpsecret):int {
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
                $nscore->msg->stopmsg(4010302,$totpsecret);
            }
        } else if ($dbreturn[0] == 1010001) { //需要新增
            $insertDic = ["tag" => $tag];
            $result = $nlcore->db->insert($tableStr,$insertDic);
            if ($dbreturn[0] >= 2000000) {
                $nscore->msg->stopmsg(4010303,$totpsecret);
            } else {
                $tagid = $result[1];
            }
        } else {
            $nscore->msg->stopmsg(2040108,$totpsecret);
        }
        return intval($tagid);
    }

    /**
    * @description: 刪除貼文
    * @param String post 貼文哈希
    * @param String totpsecret 加密用secret
    */
    function deletepost(string $post, string $totpsecret):void {
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
                        $nscore->msg->stopmsg(4030100,$totpsecret,$nowfile);
                    }
                    // 刪除所有貼文評論
                    $allcomment = array_merge($dellikes,$delcomments);
                    $tableStr = $nscore->cfg->tables["comment"];
                    $dbreturn = $nlcore->db->delete($tableStr,$allcomment,"","OR");
                    if ($dbreturn[0] >= 2000000) {
                        $nscore->msg->stopmsg(4020202,$totpsecret,$nowfile);
                    }
                } else if ($dbreturn[0] == 1010001) { // 無評論的評論
                } else { // 異常
                    $nscore->msg->stopmsg(4020201,$totpsecret);
                }
            }
        } else if ($dbreturn[0] == 1010001) { // 無評論
        } else { // 異常
            $nscore->msg->stopmsg(4020200,$totpsecret);
        }

        // 移除貼文點贊
        $tableStr = $nscore->cfg->tables["like"];
        $whereDic = [
            "post" => $post
        ];
        $dbreturn = $nlcore->db->delete($tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4030101,$totpsecret,$nowfile);
        }
        // 移除貼文
        $tableStr = $nscore->cfg->tables["posts"];
        $whereDic = [
            "post" => $post
        ];
        $dbreturn = $nlcore->db->delete($tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4010601,$totpsecret,$nowfile);
        }
    }
}
$post = new post();
$post->init();