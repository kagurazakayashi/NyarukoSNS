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
$followTable = $nscore->cfg->tables["follow"];
$fileNone = ["path"=>""];
$selectCmd = "";
$columnArrs = [];
// 準備需要查詢的內容
$columnArr = ["name","image"];
$columnArrs = array_merge($columnArrs,$columnArr);
foreach ($columnArr as $column) {
    $f = (strlen($selectCmd) == 0) ? "" : ",";
    $selectCmd .= $f."`".$infoTable."`.`".$column."`";
}
$columnArr = ["race"]; //需要的擴展用戶資料
$columnArrs = array_merge($columnArrs,$columnArr);
foreach ($columnArr as $column) {
    $selectCmd .= ",`".$zinfoTable."`.`".$column."`";
}
$columnArr = ["post","userhash","date","modified","title","type","content","tag","files","share","mention","nocomment","noforward","cite","forwardnum","commentnum","likenum"];
$columnArrs = array_merge($columnArrs,$columnArr);
foreach ($columnArr as $column) {
    $selectCmd .= ",`".$postsTable."`.`".$column."`";
}
$sqlcmd = "";
// 查詢貼文列表
$private = "";
// 限制獲取貼文範圍
if (isset($argReceived["private"])) {
    $argPrivate = $argReceived["private"];
    if (strlen($argPrivate) == 64 && $nlcore->safe->is_rhash64($argPrivate)) {
        // SQL 只顯示指定使用者發的帖（個人空間模式）
        $private = "`".$postsTable."`.`userhash` == '".$argPrivate."' ";
    } else {
        // SQL 只顯示所關注人發的帖（朋友圈模式）
        $private = ($userHash) ? " AND `".$postsTable."`.`userhash` != '".$userHash."'AND `".$postsTable."`.`userhash` IN (SELECT `".$followTable."`.`tuser` FROM `".$followTable."` WHERE `".$followTable."`.`fuser` = '".$userHash."') " : "";
    }
} else {
    // SQL 過濾遮蔽的使用者（如果使用者已經登入）
    $sqlban = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
}
// SQL 查詢時間線
$sqlcmd = "SELECT ".$selectCmd." FROM `".$postsTable."` JOIN `".$infoTable."` ON `".$postsTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON ".$infoTable.".`userhash` = ".$zinfoTable.".`userhash` WHERE `".$postsTable."`.`userhash` ".$sqlban.$private."ORDER BY date DESC LIMIT ".$limst.",".$offset.";";
$nlcore->db->initReadDbs();
$dbReturnPost = $nlcore->db->sqlc($sqlcmd);
$returnArr = $nscore->msg->m(0,3000200);
$citeHashs = [];
if ($dbReturnPost[0] == 1010000) {
    $postList = $dbReturnPost[2];
    $posthashs = [];
    for ($postListi=0; $postListi < count($postList); $postListi++) {
        $postitem = $postList[$postListi];
        $post = $postitem["post"];
        array_push($posthashs,$post);
        $cite = $postitem["cite"];
        if ($cite) array_push($citeHashs,$cite);
        // 補充檔案訊息
        $postitem["files"] = strlen($postitem["files"]) > 1 ? $nlcore->func->imagesurl($postitem["files"],$fileNone) : [$fileNone];
        $postitem["image"] = strlen($postitem["image"]) > 1 ? $nlcore->func->imagesurl($postitem["image"],$fileNone) : [$fileNone];
        $postList[$postListi] = $postitem;
        // 校驗資料庫取出資訊完整性
        foreach ($columnArrs as $column) {
            if (!in_array($column,array_keys($postitem))) {
                $nscore->msg->stopmsg(4010700,$totpSecret,$column);
            }
        }
    }
    // 批量取得轉發貼文詳情
    $citehashcmd = implode("','", $citeHashs);
    $citearr = [];
    $sqlban = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
    $sqlcmd = "SELECT ".$selectCmd." FROM `".$postsTable."` JOIN `".$infoTable."` ON `".$postsTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON ".$infoTable.".`userhash` = ".$zinfoTable.".`userhash` WHERE `".$postsTable."`.`post` IN ('".$citehashcmd."') AND`".$postsTable."`.`userhash` ".$sqlban."ORDER BY date DESC LIMIT ".$limst.",".$offset.";";
    $nlcore->db->initReadDbs();
    $dbReturCite = $nlcore->db->sqlc($sqlcmd);
    if ($dbReturCite[0] >= 2000000) $nscore->msg->stopmsg(4010404,$totpSecret);
    if ($dbReturCite[2] != null || strlen($dbReturCite[2]) > 0) $citearr = $dbReturCite[2];
    // 批量取得評論
    $timelinecommnum = $nscore->cfg->timelinecommnum;
    if ($timelinecommnum > 0) {
        $columnArrs = [];
        $selectCmd = "";
        $columnArr = ["name"];
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $f = (strlen($selectCmd) == 0) ? "" : ",";
            $selectCmd .= $f."`".$infoTable."`.`".$column."`";
        }
        $columnArr = ["race"]; //需要的擴展用戶資料
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $selectCmd .= ",`".$zinfoTable."`.`".$column."`";
        }
        $columnArr = ["post","comment","userhash","date","modified","content","type","files","likenum","storey","commentnum"];
        $columnArrs = array_merge($columnArrs,$columnArr);
        foreach ($columnArr as $column) {
            $selectCmd .= ",`".$commentTable."`.`".$column."`";
        }
        // 準備集中查詢，合併語句
        for ($posthashsi=0; $posthashsi < count($posthashs); $posthashsi++) {
            $posthashs[$posthashsi] = "`".$commentTable."`.`id` IN (SELECT cid.`id` FROM (SELECT * FROM `".$commentTable."` WHERE `".$commentTable."`.`post` = '".$posthashs[$posthashsi]."' ORDER BY date DESC LIMIT ".$timelinecommnum.") AS cid)";
        }
        $posthashcmd = implode(" OR ", $posthashs);
        // 集中獲取按日期排序的每條貼文的前三條評論
        $sqlban = $userHash ? "NOT IN (SELECT `".$banTable."`.`tuser` FROM `".$banTable."` WHERE `".$banTable."`.`fuser` = '".$userHash."') " : "";
        $sqlcmd = "SELECT ".$selectCmd." FROM `".$commentTable."` JOIN `".$infoTable."` ON `".$commentTable."`.`userhash` = `".$infoTable."`.`userhash` JOIN `".$zinfoTable."` ON `".$infoTable."`.`userhash` = `".$zinfoTable."`.`userhash` WHERE (".$posthashcmd.") AND `".$infoTable."`.`userhash` ".$sqlban."ORDER BY date DESC;";
        $nlcore->db->initReadDbs();
        $dbReturnComm = $nlcore->db->sqlc($sqlcmd);
        if ($dbReturnComm[0] >= 2000000) $nscore->msg->stopmsg(4020300,$totpSecret);
    }
    // 批量取得已關注狀態
    $postuserhashs = [];
    $postuserwhere = [];
    foreach ($postList as $post) {
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
    $dbReturnFollow = $nlcore->db->select($columnArr,$tableStr,[],$customWhere);
    if ($dbReturnFollow[0] >= 2000000) $nscore->msg->stopmsg(4040015,$totpSecret);
    // 批次獲取贊
    $tableStr = $nscore->cfg->tables["like"];
    $postuserwhere = [];
    foreach ($postList as $post) {
        $nowline = "(`user`='".$userHash."' AND `post`='".$post["post"]."' AND `citetype`='POST')";
        array_push($postuserwhere,$nowline);
    }
    $customWhere = implode(" OR ", $postuserwhere);
    $dbReturnLike = $nlcore->db->select(["post"],$tableStr,[],$customWhere);
    if ($dbReturnLike[0] >= 2000000) $nscore->msg->stopmsg(4030104,$totpSecret);
    // 合併關注狀態到貼文陣列
    if ($dbReturnFollow[0] == 1010000 && $dbReturCite[0] == 1010000) {
        $citearr = $dbReturCite[2];
        $citearrcount = count($citearr);
        for ($citearri=0; $citearri < $citearrcount; $citearri++) {
            $citeitem = $citearr[$citearri];
            $citeuserhash = $citeitem["userhash"];
            $isover = true;
            foreach ($dbReturnFollow[2] as $userFollowInfo) {
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
    for ($posti=0; $posti < count($postList); $posti++) {
        $post = $postList[$posti];
        // 合併評論資料到貼文陣列
        if ($timelinecommnum > 0) {
            if ($dbReturnComm[0] == 1010000 || $dbReturCite[0] == 1010000) {
                $commarr = $dbReturnComm[2];
                if ($dbReturnComm[0] == 1010000) {
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
            }
        }
        // 合併轉發資料到貼文陣列
        if ($dbReturCite[0] == 1010000) {
            $cite = $post["cite"];
            if ($cite != null) {
                $citearrcount = count($citearr);
                for ($citearri=0; $citearri < $citearrcount; $citearri++) {
                    $citeitem = $citearr[$citearri];
                    $topost = $citeitem["post"];
                    if (strcmp($cite,$topost) == 0) {
                        $citeitem["files"] = strlen($citeitem["files"]) > 1 ? $nlcore->func->imagesurl($citeitem["files"],$fileNone) : [$fileNone];
                        $citeitem["image"] = strlen($citeitem["image"]) > 1 ? $nlcore->func->imagesurl($citeitem["image"],$fileNone) : [$fileNone];
                        $postList[$postListi] = $postitem;
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
        if ($dbReturnFollow[0] == 1010000) {
            $postuserhash = $post["userhash"];
            foreach ($dbReturnFollow[2] as $userFollowInfo) {
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
        }
        // 合併贊到貼文陣列
        if ($dbReturnLike[0] == 1010000) {
            foreach ($dbReturnLike[2] as $nowPostitem) {
                $nowPost = $nowPostitem["post"];
                if (strcmp($post["post"],$nowPost) == 0) {
                    $post["ilike"] = "1";
                    break;
                }
            }
        }
        // 儲存全部修改後的貼文到貼文陣列
        $postList[$posti] = $post;
    }
    $returnArr["tl"] = $postList;
} else if ($dbReturnPost[0] == 1010001) {
    $returnArr["tl"] = [];
} else {
    $nscore->msg->stopmsg(4020000,$totpSecret);
}
exit($nlcore->safe->encryptargv($returnArr,$totpSecret));