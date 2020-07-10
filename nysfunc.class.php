<?php
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyscore.class.php";
require_once $usersrc."nyacore.class.php";
class nysfunc {
    /**
    * @description: 我关注了哪些人
    * @param String me 我的用户哈希
    * @param String totpsecret 加密用secret
    * @return Array<String> 我的关注列表（用户哈希）
    */
    function i_follow_hs($me,$totpsecret=null) {
        // SELECT * FROM follow WHERE fuser = 'I'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $columnArr = ["tuser"];
        $whereDic = ["fuser" => $me];
        $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        $userhashs = [];
        if ($dbreturn[0] == 1010000) { //有
            foreach ($dbreturn[2] as $item) {
                array_push($userhashs,$item["tuser"]);
            }
        } else if ($dbreturn[0] == 1010001) { //无，不处理
        } else { //错
            $nscore->msg->stopmsg(4040000,$totpsecret);
        }
        return $userhashs;
        // ["Dl4oGEJoyqf00yPXEbohjWYZExy4n7dXbaebMmgCVMLbNkn0C9bZqtPi1mkGdjwo","lIvEST0CJPp3LaRQAHqm174iVKD28Eeu4AhwDLRpglRrHwFjZRgODFMprHxYt3Uc"]
    }

    /**
    * @description: 哪些人关注了我
    * @param String me 我的用户哈希
    * @param String totpsecret 加密用secret
    * @return Array<String> 关注我的用户哈希数组
    */
    function hs_follow_i($me,$totpsecret=null) {
        // SELECT * FROM follow WHERE tuser = 'I'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $columnArr = ["fuser"];
        $whereDic = ["tuser" => $me];
        $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        $userhashs = [];
        if ($dbreturn[0] == 1010000) { //有
            foreach ($dbreturn[2] as $item) {
                array_push($userhashs,$item["fuser"]);
            }
        } else if ($dbreturn[0] == 1010001) { //无，不处理
        } else { //错
            $nscore->msg->stopmsg(4040001,$totpsecret);
        }
        return $userhashs;
        // ["lIvEST0CJPp3LaRQAHqm174iVKD28Eeu4AhwDLRpglRrHwFjZRgODFMprHxYt3Uc","Dl4oGEJoyqf00yPXEbohjWYZExy4n7dXbaebMmgCVMLbNkn0C9bZqtPi1mkGdjwo"]
    }

    /**
    * @description: 我的好友（与那些人互相关注）
    * @param String me 我的用户哈希
    * @param String totpsecret 加密用secret
    * @return Array<String> 我的好友列表（用户哈希）
    */
    function i_friend_hs($me,$totpsecret=null) {
        // SELECT * FROM follow WHERE fuser='I' AND friend=1
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $columnArr = ["fuser","tuser"];
        $whereDic = [
            "fuser" => $me,
            "friend" => 1
        ];
        $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        $userhashs = [];
        if ($dbreturn[0] == 1010000) { //有
            foreach ($dbreturn[2] as $item) {
                foreach ($item as $key => $value) {
                    if ($value != $me) {
                        array_push($userhashs,$item["fuser"]);
                    }
                }
            }
        } else if ($dbreturn[0] == 1010001) { //无，不处理
        } else { //错
            $nscore->msg->stopmsg(4040002,$totpsecret);
        }
        // if (count($userhashs) % 2 != 0) { //错:是奇数,数据错误
        //     $nscore->msg->stopmsg(4040003,$totpsecret);
        // }
        $userhashs = array_unique($userhashs);
        return $userhashs;
    }

    /**
    * @description: 对方是不是我的好友（互相关注）
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    * @return Bool 对方是不是我的好友
    */
    function i_friend_h($me,$who,$totpsecret=null) {
        // SELECT * FROM z1_follow WHERE fuser='I' AND tuser='W' AND friend=1
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $whereDic = [
            "fuser" => $me,
            "tuser" => $who,
            "friend" => 1
        ];
        $dbreturn = $nlcore->db->scount($tableStr,$whereDic);
        $isok = false;
        if ($dbreturn[0] == 1010000) { //有
            $datacount = intval($dbreturn[2][0]["count(*)"]);
            if ($datacount == 0) { //否
                $isok = false;
            } else if ($datacount == 1) { //是
                $isok = true;
            } else { //错
                $nscore->msg->stopmsg(4040004,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4040005,$totpsecret);
        }
        return $isok; //bool
    }

    /* // 我和某人是否互相关注（是不是我的好友）（弃用）
    function i_friend_hs($me,$who,$totpsecret=null) {
        // SELECT * FROM follow WHERE (fuser = 'I' or tuser = 'I') AND (fuser = 'W' or tuser = 'W')
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $customWhere = "(fuser = '".$me."' or tuser = '".$me."') AND (fuser = '".$who."' or tuser = '".$who."')";
        $dbreturn = $nlcore->db->scount($tableStr,[],$customWhere);
        $isok = false;
        if ($dbreturn[0] == 1010000) { //有
            $datacount = intval($dbreturn[2][0]["count(*)"]);
            if ($datacount == 0 || $datacount == 1) { //否
                $isok = false;
            } else if ($datacount == 2) { //是
                $isok = true;
            } else { //错

            }
        } else { //无=错

        }
        print_r($isok);
    } */

    /**
    * @description: 对方是否为我的粉丝（被对方关注）
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    * @return Bool 对方是否为我的粉丝
    */
    function h_follow_i($me,$who,$totpsecret=null) {
        // SELECT * FROM follow WHERE fuser = 'W' AND tuser = 'I'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $whereDic = [
            "fuser" => $who,
            "tuser" => $me
        ];
        $dbreturn = $nlcore->db->scount($tableStr,$whereDic);
        $isok = false;
        if ($dbreturn[0] == 1010000) { //有
            $datacount = intval($dbreturn[2][0]["count(*)"]);
            if ($datacount == 0) { //否
                $isok = false;
            } else if ($datacount == 1) { //是
                $isok = true;
            } else { //错
                $nscore->msg->stopmsg(4040006,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4040007,$totpsecret);
        }
        return $isok; //bool
    }

    /**
    * @description: 我是否关注了对方
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    * @return Bool 我是否关注了对方
    */
    function i_follow_h($me,$who,$totpsecret=null) {
        // SELECT * FROM follow WHERE fuser = 'I' AND tuser = 'W'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $whereDic = [
            "fuser" => $me,
            "tuser" => $who
        ];
        $dbreturn = $nlcore->db->scount($tableStr,$whereDic);
        $isok = false;
        if ($dbreturn[0] == 1010000) { //有
            $datacount = intval($dbreturn[2][0]["count(*)"]);
            if ($datacount == 0) { //否
                $isok = false;
            } else if ($datacount == 1) { //是
                $isok = true;
            } else { //错
                $nscore->msg->stopmsg(4040008,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4040009,$totpsecret);
        }
        return $isok; //bool
    }

    /**
    * @description: 对方是否将我屏蔽
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    * @return Bool 对方是否将我拉黑
    */
    function h_ban_i($me,$who,$totpsecret=null) {
        // SELECT * FROM ban WHERE fuser='W' AND tuser='I'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["ban"];
        $whereDic = [
            "fuser" => $who,
            "tuser" => $me
        ];
        $dbreturn = $nlcore->db->scount($tableStr,$whereDic);
        $isok = false;
        if ($dbreturn[0] == 1010000) { //有
            $datacount = intval($dbreturn[2][0]["count(*)"]);
            if ($datacount == 0) { //否
                $isok = false;
            } else if ($datacount == 1) { //是
                $isok = true;
            } else { //错
                $nscore->msg->stopmsg(4040010,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4040011,$totpsecret);
        }
        return $isok; //bool
    }

    /**
    * @description: 我是否屏蔽了对方
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    * @return Bool 我是否屏蔽了对方
    */
    function i_ban_h($me,$who,$totpsecret=null) {
        // SELECT * FROM ban WHERE fuser='I' AND tuser='W'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["ban"];
        $whereDic = [
            "fuser" => $me,
            "tuser" => $who
        ];
        $dbreturn = $nlcore->db->scount($tableStr,$whereDic);
        $isok = false;
        if ($dbreturn[0] == 1010000) { //有
            $datacount = intval($dbreturn[2][0]["count(*)"]);
            if ($datacount == 0) { //否
                $isok = false;
            } else if ($datacount == 1) { //是
                $isok = true;
            } else { //错
                $nscore->msg->stopmsg(4040012,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4040013,$totpsecret);
        }
        return $isok; //bool
    }

    /**
    * @description: 查看我的黑名单
    * @param String me 我的用户哈希
    * @param String totpsecret 加密用secret
    * @return Array<String> 我的黑名单（用户哈希）
    */
    function i_ban_hs($me,$totpsecret=null) {
        // SELECT * FROM ban WHERE fuser='I'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["ban"];
        $columnArr = ["tuser"];
        $whereDic = ["fuser" => $me];
        $dbreturn = $nlcore->db->select($columnArr,$tableStr,$whereDic);
        $userhashs = [];
        if ($dbreturn[0] == 1010000) { //有
            foreach ($dbreturn[2] as $item) {
                array_push($userhashs,$item["tuser"]);
            }
        } else if ($dbreturn[0] == 1010001) { //无，不操作
        } else { //错
            $nscore->msg->stopmsg(4040014,$totpsecret);
        }
        return $userhashs;
        // ["HqrJHc4bLwe444ja73RQop4HsqsUEiyCaQpxAszYc9Lsj13CYjIU0Dzx3KpBujsi","vHqw1uq4XaXyz4IbdC4gfB441886l25Zt4TDB6YNHhDlu27uHZGg3IW8Zb1BegQm"]
    }

    //修改社交关系

    /**
    * @description: 關注對方
    * @param String me 我的使用者雜湊
    * @param String who 對方的使用者雜湊
    * @param String totpsecret 加密用secret
    * @return Bool 是否已經關注了
    */
    function i_follow_f($me,$who,$totpsecret=null):bool {
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        if ($this->h_ban_i($me,$who)) { //被對方拉黑
            $nscore->msg->stopmsg(4040108,$totpsecret);
        }
        if ($this->i_ban_h($me,$who)) { //將對方拉黑
            $nscore->msg->stopmsg(4040109,$totpsecret);
        }
        if ($this->i_follow_h($me,$who,$totpsecret)) { //是否已經關注了
            return true;
        }
        // UPDATE z1_follow SET friend=1 WHERE fuser='W' AND `tuser`='I'
        $updateDic = [
            "friend" => 1
        ];
        $whereDic = [
            "fuser" => $who,
            "tuser" => $me
        ];
        $dbreturn = $nlcore->db->update($updateDic,$tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000 || $dbreturn[3] > 1) {
            $nscore->msg->stopmsg(4040101,$totpsecret);
        }
        // if ($dbreturn[3] == 1) {
            // UPDATE z1_follow SET friend=1 WHERE fuser='I' AND `tuser`='W'
            $insertDic = [
                "fuser" => $me,
                "tuser" => $who,
                "friend" => $dbreturn[3]
            ];
            $dbreturn = $nlcore->db->insert($tableStr,$insertDic);
            if ($dbreturn[0] >= 2000000 || $dbreturn[3] > 1) {
                $nscore->msg->stopmsg(4040102,$totpsecret);
            }
        // } else if ($dbreturn[3] == 0) {
        //     // INSERT INTO z1_follow(fuser, tuser) VALUES ('I','W')
        //     $insertDic = [
        //         "fuser" => $me,
        //         "tuser" => $who,
        //         "friend" => 0
        //     ];
        //     $dbreturn = $nlcore->db->insert($tableStr,$insertDic);
        //     if ($dbreturn[0] >= 2000000) {
        //         $nscore->msg->stopmsg(4040100,$totpsecret);
        //     } else if ($dbreturn[3] == 0) {
        //     }
        // }
        return false;
    }

    /**
    * @description: 取關對方
    * @param String me 我的使用者雜湊
    * @param String who 對方的使用者雜湊
    * @param String totpsecret 加密用secret
    * @return Bool 是否已經取關對方
    */
    function i_unfollow_f($me,$who,$totpsecret=null):bool {
        // DELETE FROM z1_follow WHERE fuser='I' AND tuser='W'
        global $nlcore; global $nscore;
        $notarget = false;
        $tableStr = $nscore->cfg->tables["follow"];
        $whereDic = [
            "fuser" => $me,
            "tuser" => $who
        ];
        $dbreturn = $nlcore->db->delete($tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4040103,$totpsecret);
        } else if ($dbreturn[3] != 1) {
            $notarget = true;
        }
        // UPDATE z1_follow SET friend=0 WHERE fuser='W' AND `tuser`='I'
        $updateDic = [
            "friend" => 0
        ];
        $whereDic = [
            "fuser" => $who,
            "tuser" => $me
        ];
        $dbreturn = $nlcore->db->update($updateDic,$tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4040104,$totpsecret);
        } else if ($dbreturn[3] == 0) {
        }
        return $notarget;
    }

    /**
    * @description: 拉黑对方
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    */
    function i_ban_f($me,$who,$totpsecret=null) {
        global $nlcore; global $nscore;
        if ($this->i_ban_h($me,$who)) {
            $nscore->msg->stopmsg(4040110,$totpsecret);
        }
        // INSERT INTO z1_ban(fuser, tuser) VALUES ('I','W')
        $tableStr = $nscore->cfg->tables["ban"];
        $insertDic = [
            "fuser" => $me,
            "tuser" => $who,
        ];
        $dbreturn = $nlcore->db->insert($tableStr,$insertDic);
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4040105,$totpsecret);
        } else if ($dbreturn[3] == 0) { //重复操作，不报错
        }
        $tableStr = $nscore->cfg->tables["follow"];
        $customWhere = "(fuser = '".$me."' AND tuser = '".$who."') OR (fuser = '".$who."' AND tuser = '".$me."')";
        $dbreturn = $nlcore->db->delete($tableStr,[],$customWhere);
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4040106,$totpsecret);
        } else if ($dbreturn[3] == 0) { //重复操作，不报错
        }
    }

    /**
    * @description: 取消拉黑对方
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    */
    function i_unban_f($me,$who,$totpsecret=null) {
        // DELETE FROM z1_ban WHERE fuser='I' AND tuser='W'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["ban"];
        $whereDic = [
            "fuser" => $me,
            "tuser" => $who
        ];
        $dbreturn = $nlcore->db->delete($tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4040107,$totpsecret);
        } else if ($dbreturn[3] != 1) { //重复操作，不报错
        }
    }

    /**
    * @description: 檢查提及（昵称#编号）是否在正文中,並轉換成用戶哈希字符串
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    */
    function mention() { //TODO:!!!
        $mention = explode(",",$jsonarr["mention"]);
        for ($i=0; $i < count($mention); $i++) {
            $nowmention = $mention[$i];
            $namearr = explode($nscore->cfg->separator["namelink"],$nowmention);
            $name = $namearr[0];
            if (strstr($content, $name) == false) {
                $nscore->msg->stopmsg(4010101,$totpsecret,$content);
            }
            $mention[$i] = $nlcore->func->fullnickname2userhash($namearr,$totpsecret)[2];
        }
        $mention = implode(",", $mention);
    }
}
?>