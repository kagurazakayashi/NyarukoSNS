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
        $jsonarrTotpsecret = $nlcore->safe->decryptargv($nscore->cfg->limittime["timeline"]);
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
        $zinfoTable = $nscore->cfg->tables["info"];
        $commentTable = $nscore->cfg->tables["comment"];
        $filenone = ["path"=>""];
        $selectcmd = "";
        $columnArrs = [];
        $columnArr = ["name","image"];
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $f = (strlen($selectcmd) == 0) ? "" : ",";
            $selectcmd .= $f."`".$infoTable."`.`".$column."`";
        }
        $columnArr = ["ext*"]; //需要的擴展用戶資料
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $selectcmd .= ",`".$zinfoTable."`.`".$column."`";
        }
        $columnArr = ["post","userhash","date","modified","title","type","content","tag","files","share","mention","nocomment","noforward","cite","forwardnum","commentnum","likenum"];
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $selectcmd .= ",`".$postsTable."`.`".$column."`";
        }
        $sqlcmd = "SELECT ".$selectcmd." FROM `".$postsTable."` JOIN `".$infoTable."` ON `".$postsTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON ".$infoTable.".`userhash` = ".$zinfoTable.".`userhash` WHERE `".$postsTable."`.`userhash` NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userhash."') ORDER BY date DESC LIMIT ".$limst.",".$offset.";";
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
                $postitem["files"] = strlen($postitem["files"]) > 1 ? $nlcore->func->imagesurl($postitem["files"],$filenone) : [$filenone];
                $postitem["image"] = strlen($postitem["image"]) > 1 ? $nlcore->func->imagesurl($postitem["image"],$filenone) : [$filenone];
                $postlist[$i] = $postitem;
                // 校驗資料庫取出資訊完整性
                foreach ($columnArrs as $column) {
                    if (!in_array($column,array_keys($postitem))) {
                        $nscore->msg->stopmsg(4010700,$totpsecret,$column);
                    }
                }
            }
            // 批量取得評論
            $columnArrs = [];
            $selectcmd = "";
            $columnArr = ["name"];
            $columnArrs = array_merge($columnArrs,$columnArr);
            foreach ($columnArr as $column) {
                $f = (strlen($selectcmd) == 0) ? "" : ",";
                $selectcmd .= $f."`".$infoTable."`.`".$column."`";
            }
            $columnArr = ["ext*"]; //需要的擴展用戶資料
            $columnArrs = array_merge($columnArrs,$columnArr);
            foreach ($columnArr as $column) {
                $selectcmd .= ",`".$zinfoTable."`.`".$column."`";
            }
            $columnArr = ["post","comment","userhash","date","modified","content","type","files","likenum","storey","commentnum"];
            $columnArrs = array_merge($columnArrs,$columnArr);
            foreach ($columnArr as $column) {
                $selectcmd .= ",`".$commentTable."`.`".$column."`";
            }
            $posthashcmd = implode("','", $posthashs);
            // 取按日期排列的前三條資料
            $sqlcmd = "SELECT ".$selectcmd." FROM `".$commentTable."` JOIN `".$infoTable."` ON `".$commentTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON `".$infoTable."`.`userhash` = `".$zinfoTable."`.`userhash` WHERE `".$commentTable."`.`post` IN ('".$posthashcmd."') AND `".$infoTable."`.`userhash` NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userhash."') ORDER BY date DESC;";
            $dbreturn = $nlcore->db->sqlc($sqlcmd);
            if ($dbreturn[0] == 1010000) {
                $commarr = $dbreturn[2];
                for ($j=0; $j < count($commarr); $j++) {
                    $commitem = $commarr[$j];
                    $topost = $commitem["post"];
                    unset($commitem["post"]);
                    $commitem["files"] = strlen($commitem["files"]) > 1 ? $nlcore->func->imagesurl($commitem["files"],$filenone) : $filenone;
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