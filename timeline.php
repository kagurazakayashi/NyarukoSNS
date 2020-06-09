<?php
/**
 * @description: 時間線
 * @package NyarukoSNS
*/
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
class timeline {
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
        // 導入提交的參數
        $limst = isset($jsonarr["limst"]) ? intval($jsonarr["limst"]) : 0;
        $offset = isset($jsonarr["offset"]) ? intval($jsonarr["offset"]) : 10;
        // 讀取貼文
        $postsTable = $nscore->cfg->tables["posts"];
        $banTable = $nscore->cfg->tables["ban"];
        $infoTable = $nlcore->cfg->db->tables["info"];
        $columnArr = ["post","userhash","date","modified","title","type","content","tag","files","share","mention","nocomment","noforward","cite","forwardnum","commentnum","likenum"];
        $poststbcmd = "";
        foreach ($columnArr as $column) {
            $poststbcmd .= ",`".$postsTable."`.`".$column."`";
        }
        $sqlcmd = "SELECT `".$infoTable."`.`name`,`".$infoTable."`.`image`".$poststbcmd." FROM `".$postsTable."` JOIN `".$infoTable."` ON `".$postsTable."`.`userhash` = `".$infoTable."`.`userhash` WHERE `".$postsTable."`.`userhash` NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userhash."') ORDER BY date DESC LIMIT ".$limst.",".$offset.";";
        $dbreturn = $nlcore->db->sqlc($sqlcmd);
        $returnarr = $nscore->msg->m(0,3000200);
        if ($dbreturn[0] == 1010000) {
            $postlist = $dbreturn[2];
            $posthashs = [];
            for ($i=0; $i < count($postlist); $i++) {
                $postitem = $postlist[$i];
                $post = $postitem["post"];
                array_push($posthashs,$post);
                // 補充檔案訊息
                $postitem["files"] = strlen($postitem["files"]) > 1 ? $nlcore->func->imagesurl($postitem["files"]) : ["path"=>""];
                $postitem["image"] = strlen($postitem["image"]) > 1 ? $nlcore->func->imagesurl($postitem["image"]) : ["path"=>""];
                $postlist[$i] = $postitem;
            }
            // 批量取得評論
            $columnArr = ["post","comment","userhash","date","modified","content","type","files","likenum","storey","commentnum"];
            $tableStr = $nscore->cfg->tables["comment"];
            $whereDic = ["post" => $posthashs];
            $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic,$customWhere="",$whereMode="IN");
            if ($dbreturn[0] == 1010000) {
                $commarr = $dbreturn[2];
                for ($j=0; $j < count($commarr); $j++) {
                    $commitem = $commarr[$j];
                    $topost = $commitem["post"];
                    unset($commitem["post"]);
                    $commitem["files"] = strlen($commitem["files"]) > 1 ? $nlcore->func->imagesurl($commitem["files"]) : [];
                    for ($k=0; $k < count($postlist); $k++) {
                        $post = $postlist[$k]["post"];
                        if (strcmp($post,$topost) == 0) {
                            $npost = $postlist[$k];
                            $commentarr = $npost["comment"] ?? [];
                            array_push($commentarr,$commitem);
                            $npost["comment"] = $commentarr;
                            $postlist[$k] = $npost;
                        }
                    }
                    $commarr[$j] = $commitem;
                }
            } else if ($dbreturn[0] == 1010001) {
            } else {
                $nscore->msg->stopmsg(4020300,$totpsecret);
            }
            $returnarr["tl"] = $postlist;
        } else if ($dbreturn[0] == 1010001) {
            $returnarr["tl"] = [];
        } else {
            $nscore->msg->stopmsg(4020000,$totpsecret);
        }
        echo $nlcore->safe->encryptargv($returnarr,$totpsecret);
    }
}
$timeline = new timeline();
$timeline->init();
?>