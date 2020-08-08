<?php
/**
 * @description: 評論列表
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
class commentlist {
    function init():void {
        global $nlcore;
        global $nscore;
        $limittime = $zecore->cfg->limittime["commentlist"];
        $clientInformation = $nlcore->safe->decryptargv("",$limittime[0],$limittime[1]);
        $argReceived = $clientInformation[0];
        $totpSecret = $clientInformation[1];
        $totptoken = $clientInformation[2];
        $ipid = $clientInformation[3];
        $appid = $clientInformation[4];
        // 檢查用戶是否登入，若沒有提供 token 則…算了
        $userHash = null;
        if (isset($argReceived["token"]) && strlen($argReceived["token"]) > 0) {
            $usertoken = $argReceived["token"];
            if (!$nlcore->safe->is_rhash64($usertoken)) $nlcore->msg->stopmsg(2040402,$totpSecret,"COMM".$usertoken);
            $userpwdtimes = $nlcore->sess->sessionstatuscon($usertoken,true,$totpSecret);
            $userHash = $userpwdtimes["userhash"];
            if (!$userpwdtimes) $nlcore->msg->stopmsg(2040400,$totpSecret,"COMM".$usertoken);
        }
        // 導入提交的參數
        $limst = isset($argReceived["limst"]) ? intval($argReceived["limst"]) : 0;
        $offset = isset($argReceived["offset"]) ? intval($argReceived["offset"]) : 10;
        if (!isset($argReceived["post"])) $nscore->msg->stopmsg(4020301,$totpSecret);
        $post = $argReceived["post"];
        // 讀取評論列表
        $banTable = $nscore->cfg->tables["ban"];
        $infoTable = $nlcore->cfg->db->tables["info"];
        $commentTable = $nscore->cfg->tables["comment"];
        $zinfoTable = $nscore->cfg->tables["info"];
        $filenone = ["path"=>""];
        $selectcmd = "";
        $columnArrs = [];
        $columnArr = ["name","belong","image"];
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $f = (strlen($selectcmd) == 0) ? "" : ",";
            $selectcmd .= $f."`".$infoTable."`.`".$column."`";
        }
        $columnArr = [""]; //需要的擴展用戶資料
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $selectcmd .= ",`".$zinfoTable."`.`".$column."`";
        }
        $columnArr = ["post","comment","userhash","date","modified","content","type","files","likenum","storey","commentnum"];
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $selectcmd .= ",`".$commentTable."`.`".$column."`";
        }
        $sqlban = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
        $sqlcmd = "SELECT ".$selectcmd." FROM `".$commentTable."` JOIN `".$infoTable."` ON `".$commentTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON ".$infoTable.".`userhash` = ".$zinfoTable.".`userhash` WHERE `".$commentTable."`.`userhash` ".$sqlban."AND `".$commentTable."`.`post`='".$post."' ORDER BY date DESC LIMIT ".$limst.",". $offset.";";
        $nlcore->db->initReadDbs();
        $dbreturn = $nlcore->db->sqlc($sqlcmd);
        $returnarr = $nscore->msg->m(0,3000201);
        if ($dbreturn[0] == 1010000) {
            $commlist = $dbreturn[2];
            // 補充檔案訊息
            for ($i=0; $i < count($commlist); $i++) {
                $commitem = $commlist[$i];
                $commitem["files"] = strlen($commitem["files"]) > 1 ? $nlcore->func->imagesurl($commitem["files"],$filenone) : [$filenone];
                $commitem["image"] = strlen($commitem["image"]) > 1 ? $nlcore->func->imagesurl($commitem["image"],$filenone) : [$filenone];
                $commlist[$i] = $commitem;
                // 校驗資料庫取出資訊完整性
                foreach ($columnArrs as $column) {
                    if (!in_array($column,array_keys($commitem))) {
                        $nscore->msg->stopmsg(4020302,$totpSecret,$column);
                    }
                }
            }
            $returnarr["comm"] = $commlist;
        } else if ($dbreturn[0] == 1010001) {
            $returnarr["comm"] = [];
        } else {
            $nscore->msg->stopmsg(4020300,$totpSecret);
        }
        echo $nlcore->safe->encryptargv($returnarr,$totpSecret);
    }
}
$commentlist = new commentlist();
$commentlist->init();
?>