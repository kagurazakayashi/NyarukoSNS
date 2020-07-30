<?php
/**
 * @description: 時間線
 * @package NyarukoSNS
*/
$phpFileDir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$phpFileUserSrcDir = $phpFileDir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpFileDir."nyscore.class.php";
require_once $phpFileUserSrcDir."nyacore.class.php";
// IP檢查和解密客戶端提交的資訊
$frequencyLimitation = $nscore->cfg->limittime["timeline"];
$inputInformation = $nlcore->safe->decryptargv("",$frequencyLimitation[0],$frequencyLimitation[1]);
$argReceived = $inputInformation[0];
$totpSecret = $inputInformation[1];
// 檢查用戶是否登入，若沒有提供 token 則…算了
$userHash = null;
if (isset($argReceived["token"]) && strlen($argReceived["token"]) > 0) {
    $sessionInformation = $nlcore->safe->userLogged($inputInformation);
    $userHash = $sessionInformation[2];
}
// 導入提交的參數
$limst = isset($argReceived["limst"]) ? intval($argReceived["limst"]) : 0;
$offset = isset($argReceived["offset"]) ? intval($argReceived["offset"]) : 10;
// 讀取貼文
$postsTable = $nscore->cfg->tables["posts"];
$banTable = $nscore->cfg->tables["ban"];
$infoTable = $nlcore->cfg->db->tables["info"];
$zinfoTable = $nscore->cfg->tables["info"];
$commentTable = $nscore->cfg->tables["comment"];
$followTable = $zecore->cfg->tables["follow"];
$filenone = ["path"=>""];
$selectcmd = "";
$columnArrs = [];
$columnArr = ["name","image"];
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
$columnArr = ["post","userhash","date","modified","title","type","content","tag","files","share","mention","nocomment","noforward","cite","forwardnum","commentnum","likenum"];
$columnArrs = array_merge($columnArrs,$columnArr);
foreach ($columnArr as $column) {
    $selectcmd .= ",`".$postsTable."`.`".$column."`";
}
$sqlcmd = "";
// 查詢貼文列表
// SQL 朋友圈+
$private = "";
if (isset($argReceived["private"])) {
    $privateLen = strlen($argReceived["private"]);
    if ($privateLen == 0) { // 只顯示所關注人發的帖（朋友圈模式）
        $private = (isset($argReceived["private"]) && $userHash) ? " AND `".$postsTable."`.`userhash` != '".$userHash."'AND `".$postsTable."`.`userhash` IN (SELECT `".$followTable."`.`tuser` FROM `".$followTable."` WHERE `".$followTable."`.`fuser` = '".$userHash."') " : "";
    } else if ($privateLen == 4) { // TODO: 只顯示自己發的帖
    } else if ($privateLen == 64) { // TODO: 只顯示指定使用者發的帖
    }
}
// SQL 過濾遮蔽的使用者+
$sqlban = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
// SQL 查詢時間線
$sqlcmd = "SELECT ".$selectcmd." FROM `".$postsTable."` JOIN `".$infoTable."` ON `".$postsTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON ".$infoTable.".`userhash` = ".$zinfoTable.".`userhash` WHERE `".$postsTable."`.`userhash` ".$sqlban.$private."ORDER BY date DESC LIMIT ".$limst.",".$offset.";";
$dbreturn = $nlcore->db->sqlc($sqlcmd);
$returnarr = $nscore->msg->m(0,3000200);
$citehashs = [];
if ($dbreturn[0] == 1010000) {
    $postlist = $dbreturn[2];
    $posthashs = [];
    for ($postlisti=0; $postlisti < count($postlist); $postlisti++) {
        $postitem = $postlist[$postlisti];
        $post = $postitem["post"];
        array_push($posthashs,$post);
        $cite = $postitem["cite"];
        if ($cite) array_push($citehashs,$cite);
        // 補充檔案訊息
        $postitem["files"] = strlen($postitem["files"]) > 1 ? $nlcore->func->imagesurl($postitem["files"],$filenone) : [$filenone];
        $postitem["image"] = strlen($postitem["image"]) > 1 ? $nlcore->func->imagesurl($postitem["image"],$filenone) : [$filenone];
        $postlist[$postlisti] = $postitem;
        // 校驗資料庫取出資訊完整性
        foreach ($columnArrs as $column) {
            if (!in_array($column,array_keys($postitem))) {
                $nscore->msg->stopmsg(4010700,$totpSecret,$column);
            }
        }
    }
    // 批量取得轉發貼文詳情
    $citehashcmd = implode("','", $citehashs);
    $citearr = [];
    $sqlban = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
    $sqlcmd = "SELECT ".$selectcmd." FROM `".$postsTable."` JOIN `".$infoTable."` ON `".$postsTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON ".$infoTable.".`userhash` = ".$zinfoTable.".`userhash` WHERE `".$postsTable."`.`post` IN ('".$citehashcmd."') AND`".$postsTable."`.`userhash` ".$sqlban."ORDER BY date DESC LIMIT ".$limst.",".$offset.";";
    $dbreturcite = $nlcore->db->sqlc($sqlcmd);
    if ($dbreturcite[0] >= 2000000) $nscore->msg->stopmsg(4010404,$totpSecret);
    // 批量取得評論
    $timelinecommnum = $nscore->cfg->timelinecommnum;
    if ($timelinecommnum > 0) {
        $columnArrs = [];
        $selectcmd = "";
        $columnArr = ["name"];
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
        // 準備集中查詢，合併語句
        for ($posthashsi=0; $posthashsi < count($posthashs); $posthashsi++) {
            $posthashs[$posthashsi] = "`".$commentTable."`.`id` IN (SELECT cid.`id` FROM (SELECT * FROM `".$commentTable."` WHERE `".$commentTable."`.`post` = '".$posthashs[$posthashsi]."' ORDER BY date DESC LIMIT ".$timelinecommnum.") AS cid)";
        }
        $posthashcmd = implode(" OR ", $posthashs);
        // 集中獲取按日期排序的每條貼文的前三條評論
        $sqlban = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
        $sqlcmd = "SELECT ".$selectcmd." FROM `".$commentTable."` JOIN `".$infoTable."` ON `".$commentTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON `".$infoTable."`.`userhash` = `".$zinfoTable."`.`userhash` WHERE (".$posthashcmd.") AND `".$infoTable."`.`userhash` ".$sqlban."ORDER BY date DESC;";
        $dbreturn = $nlcore->db->sqlc($sqlcmd);
    }
    // 批量取得已關注狀態
    $postuserhashs = [];
    $postuserwhere = [];
    foreach ($postlist as $post) {
        $nowuserhash = $post["userhash"];
        if (!in_array($nowuserhash,$postuserhashs)) {
            array_push($postuserhashs,$nowuserhash);
            $nowline = "(`fuser`='".$userHash."' AND `tuser`='".$nowuserhash."')";
            array_push($postuserwhere,$nowline);
        }
    }
    // 同時處理轉發內容的已關注狀態
    foreach ($citearr as $post) {
        $nowuserhash = $post["userhash"];
        if (!in_array($nowuserhash,$postuserhashs)) {
            array_push($postuserhashs,$nowuserhash);
            $nowline = "(`fuser`='".$userHash."' AND `tuser`='".$nowuserhash."')";
            array_push($postuserwhere,$nowline);
        }
    }
    $customWhere = implode(" OR ", $postuserwhere);
    $tableStr = $nscore->cfg->tables["follow"];
    $columnArr = ["tuser","friend"];
    $dbreturnfollow = $nlcore->db->select($columnArr,$tableStr,[],$customWhere);
    // 合併關注狀態到貼文陣列
    if ($dbreturnfollow[0] == 1010000 && $dbreturcite[0] == 1010000) {
        $citearr = $dbreturcite[2];
        $citearrcount = count($citearr);
        for ($citearri=0; $citearri < $citearrcount; $citearri++) {
            $citeitem = $citearr[$citearri];
            $citeuserhash = $citeitem["userhash"];
            $isover = true;
            foreach ($dbreturnfollow[2] as $userFollowInfo) {
                $tuser = $userFollowInfo["tuser"];
                $friend = $userFollowInfo["friend"];
                if (strcmp($citeuserhash,$tuser) == 0) {
                    $citeitem["follow"] = intval($friend);
                    $citearr[$citearri] = $citeitem;
                    $isover = false;
                    break;
                }
            }
            if ($isover) {
                $citeitem["follow"] = -1;
                $citearr[$citearri] = $citeitem;
            }
        }
    }
    // 將批量獲取的資料合併到貼文陣列
    for ($posti=0; $posti < count($postlist); $posti++) {
        $post = $postlist[$posti];
        // 合併評論資料到貼文陣列
        if ($timelinecommnum > 0) {
            if ($dbreturn[0] == 1010000 || $dbreturcite[0] == 1010000) {
                $commarr = $dbreturn[2];
                if ($dbreturn[0] == 1010000) {
                    $tpost = $post["post"];
                    for ($commarri=0; $commarri < count($commarr); $commarri++) {
                        $commitem = $commarr[$commarri];
                        $topost = $commitem["post"];
                        if (strcmp($tpost,$topost) == 0) {
                            $commentarr = $post["comment"] ?? [];
                            array_push($commentarr,$commitem);
                            $post["comment"] = $commentarr;
                            if (count($commentarr)>=3) {
                                break;
                            }
                        }
                    }
                }
            }else if ($dbreturn[0] == 1010001) {
            } else {
                $nscore->msg->stopmsg(4020300,$totpSecret);
            }
        }
        // 合併轉發資料到貼文陣列
        if ($dbreturcite[0] == 1010000) {
            $cite = $post["cite"];
            if ($cite != null) {
                $citearrcount = count($citearr);
                for ($citearri=0; $citearri < $citearrcount; $citearri++) {
                    $citeitem = $citearr[$citearri];
                    $topost = $citeitem["post"];
                    if (strcmp($cite,$topost) == 0) {
                        $citeitem["files"] = strlen($citeitem["files"]) > 1 ? $nlcore->func->imagesurl($citeitem["files"],$filenone) : [$filenone];
                        $citeitem["image"] = strlen($citeitem["image"]) > 1 ? $nlcore->func->imagesurl($citeitem["image"],$filenone) : [$filenone];
                        $postlist[$postlisti] = $postitem;
                        $post["cite"] = $citeitem;
                        break;
                    }
                    if ($citearri == $citearrcount-1) {
                        $post["cite"] = ["nodata"=>""];
                    }
                }
            }
        }
        // 合併跟隨資料到貼文陣列
        if ($dbreturnfollow[0] == 1010000) {
            $postuserhash = $post["userhash"];
            foreach ($dbreturnfollow[2] as $userFollowInfo) {
                $tuser = $userFollowInfo["tuser"];
                $friend = $userFollowInfo["friend"];
                $isover = true;
                if (strcmp($postuserhash,$tuser) == 0) {
                    $post["follow"] = intval($friend);
                    $isover = false;
                    break;
                }
                if ($isover) {
                    $post["follow"] = -1;
                }
            }
        } else if ($dbreturnfollow[0] == 1010000) {
            // 沒有任何關注
        }
        // 儲存全部修改後的貼文到貼文陣列
        $postlist[$posti] = $post;
    }
    $returnarr["tl"] = $postlist;
} else if ($dbreturn[0] == 1010001) {
    $returnarr["tl"] = [];
} else {
    $nscore->msg->stopmsg(4020000,$totpSecret);
}
echo $nlcore->safe->encryptargv($returnarr,$totpSecret);