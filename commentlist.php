<?php
/**
 * @description: 評論列表
 * @package NyarukoSNS
*/
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
class commentlist {
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
        if (!isset($jsonarr["post"]) || !$nlcore->safe->is_rhash64($usertoken)) $nlcore->msg->stopmsg(4020301,$totpsecret,"COMM".$usertoken);
        $post = $jsonarr["post"];
        // 讀取評論列表
        $postsTable = $nscore->cfg->tables["posts"];
        $banTable = $nscore->cfg->tables["ban"];
        $infoTable = $nlcore->cfg->db->tables["info"];
        $commentTable = $nscore->cfg->tables["comment"];
        $columnArr = ["post","comment","userhash","date","modified","content","type","files","likenum","storey","commentnum"];
        $commstbcmd = "";
        foreach ($columnArr as $column) {
            $commstbcmd .= ",`".$commentTable."`.`".$column."`";
        }
        $sqlcmd = "SELECT `".$infoTable."`.`name`,`".$infoTable."`.`belong`,`".$infoTable."`.`image`".$commstbcmd." FROM `".$commentTable."` JOIN `".$infoTable."` ON `".$commentTable."`.`userhash` = `".$infoTable."`.`userhash` WHERE `".$commentTable."`.`userhash` NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userhash."') AND `".$commentTable."`.`post`='".$post."' ORDER BY date DESC LIMIT ".$limst.",". $offset.";";
        $dbreturn = $nlcore->db->sqlc($sqlcmd);
        $returnarr = $nscore->msg->m(0,3000201);
        if ($dbreturn[0] == 1010000) {
            $commlist = $dbreturn[2];
            // 補充檔案訊息
            for ($i=0; $i < count($commlist); $i++) {
                $commitem = $commlist[$i];
                $commitem["files"] = strlen($commitem["files"]) > 1 ? $nlcore->func->imagesurl($commitem["files"]) : ["path"=>""];
                $commitem["image"] = strlen($commitem["image"]) > 1 ? $nlcore->func->imagesurl($commitem["image"]) : ["path"=>""];
                $commlist[$i] = $commitem;
            }
            $returnarr["comm"] = $commlist;
        } else if ($dbreturn[0] == 1010001) {
            $returnarr["comm"] = [];
        } else {
            $nscore->msg->stopmsg(4020300,$totpsecret);
        }
        echo $nlcore->safe->encryptargv($returnarr,$totpsecret);
    }
}
$commentlist = new commentlist();
$commentlist->init();
?>