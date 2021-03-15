<?php
/**
 * @description: 評論列表
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
$limittime = $nscore->cfg->limittime["commentlist"];
$nlcore->sess->decryptargv("",$limittime[0],$limittime[1]);
$argReceived = $nlcore->sess->argReceived;
// 檢查用戶是否登入，若沒有提供 token 則…算了
$userHash = null;
if (isset($argReceived["token"]) && strlen($argReceived["token"]) > 0) {
    $nlcore->sess->userLogged();
    $userHash = $nlcore->sess->userHash;
}
// 導入提交的參數
$limst = isset($argReceived["limst"]) ? intval($argReceived["limst"]) : 0;
$offset = isset($argReceived["offset"]) ? intval($argReceived["offset"]) : 10;
if (!isset($argReceived["post"])) $nscore->msg->stopmsg(4020301);
$post = $argReceived["post"];
// 讀取評論列表
$banTable = $nscore->cfg->tables["ban"];
$infoTable = $nlcore->cfg->db->tables["info"];
$commentTable = $nscore->cfg->tables["comment"];
$zinfoTable = $nscore->cfg->tables["info"];
$fileNone = ["path"=>""];
$selectCmd = "";
$columnArrs = [];
$columnArr = ["name","belong","image"];
$columnArrs = array_merge($columnArrs,$columnArr);
foreach ($columnArr as $column) {
    $f = (strlen($selectCmd) == 0) ? "" : ",";
    $selectCmd .= $f."`".$infoTable."`.`".$column."`";
}
$columnArr = [""]; //需要的擴展用戶資料
$columnArrs = array_merge($columnArrs,$columnArr);
foreach ($columnArr as $column) {
    $selectCmd .= ",`".$zinfoTable."`.`".$column."`";
}
$columnArr = ["post","comment","userhash","date","modified","content","type","files","likenum","storey","commentnum"];
$columnArrs = array_merge($columnArrs,$columnArr);
foreach ($columnArr as $column) {
    $selectCmd .= ",`".$commentTable."`.`".$column."`";
}
$sqlBan = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
$statusDisplay = " AND `" . $commentTable . "`.`status` NOT IN ('DELETED', 'BANNED')";
$sqlcmd = "SELECT ".$selectCmd." FROM `".$commentTable."` JOIN `".$infoTable."` ON `".$commentTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON ".$infoTable.".`userhash` = ".$zinfoTable.".`userhash` WHERE `".$commentTable."`.`userhash` ".$sqlBan.$statusDisplay." AND `".$commentTable."`.`post`='".$post."' ORDER BY date DESC LIMIT ".$limst.",". $offset.";";
$nlcore->db->initReadDbs();
$dbReturn = $nlcore->db->sqlc($sqlcmd);
$returnClientData = $nscore->msg->m(0,3000201);
if ($dbReturn[0] == 1010000) {
    $commList = $dbReturn[2];
    // 批次獲取贊
    $tableStr = $nscore->cfg->tables["like"];
    $postUserWhere = [];
    foreach ($commList as $comm) {
        $nowline = "(`user`='".$userHash."' AND `post`='".$comm["comment"]."' AND `citetype`='COMM')";
        array_push($postUserWhere,$nowline);
    }
    $customWhere = implode(" OR ", $postUserWhere);
    $dbReturnLike = $nlcore->db->select(["post"],$tableStr,[],$customWhere);
    if ($dbReturnLike[0] >= 2000000) $nscore->msg->stopmsg(4030104);
    // 補充訊息
    for ($i=0; $i < count($commList); $i++) {
        // 合併檔案訊息到貼文陣列
        $commItem = $commList[$i];
        $commItem["files"] = strlen($commItem["files"]) > 1 ? $nlcore->func->imagesurl($commItem["files"],$fileNone) : [$fileNone];
        $commItem["image"] = strlen($commItem["image"]) > 1 ? $nlcore->func->imagesurl($commItem["image"],$fileNone) : [$fileNone];
        // 合併贊到貼文陣列
        if ($dbReturnLike[0] == 1010000) {
            foreach ($dbReturnLike[2] as $nowPostitem) {
                $nowPost = $nowPostitem["post"];
                if (strcmp($commItem["comment"],$nowPost) == 0) {
                    $commItem["ilike"] = "1";
                    break;
                }
            }
        }
        $commList[$i] = $commItem;
        // 校驗資料庫取出資訊完整性
        foreach ($columnArrs as $column) {
            if (!in_array($column,array_keys($commItem))) {
                $nscore->msg->stopmsg(4020302,$column);
            }
        }
    }
    $returnClientData["comm"] = $commList;
} else if ($dbReturn[0] == 1010001) {
    $returnClientData["comm"] = [];
} else {
    $nscore->msg->stopmsg(4020300);
}
exit($nlcore->sess->encryptargv($returnClientData));
