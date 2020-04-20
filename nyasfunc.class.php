<?php
$phpfiledir = pathinfo(__FILE__)["dirname"].DIRECTORY_SEPARATOR;
$usersrc = $phpfiledir."..".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."src".DIRECTORY_SEPARATOR;
require_once $phpfiledir."nyascore.class.php";
require_once $usersrc."nyacore.class.php";
class nyasfunc {
    public $totpsecret = null;

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
            $nscore->msg->stopmsg(4020000,$totpsecret);
        }
        return $userhashs;
        // ["Dl4oGEJoyqf00yPXEbohjWYnsxy4n7dXbaebMmgCVMLbNkn0C9bZqtPi1mkGdjwo","lIvEST0CJPp3LaRQAHqm174iVKD28Eeu4AhwDLRpglRrHwFjZRgODFMprHxYt3Uc"]
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
            $nscore->msg->stopmsg(4020001,$totpsecret);
        }
        return $userhashs;
        // ["lIvEST0CJPp3LaRQAHqm174iVKD28Eeu4AhwDLRpglRrHwFjZRgODFMprHxYt3Uc","Dl4oGEJoyqf00yPXEbohjWYnsxy4n7dXbaebMmgCVMLbNkn0C9bZqtPi1mkGdjwo"]
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
            $nscore->msg->stopmsg(4020002,$totpsecret);
        }
        // if (count($userhashs) % 2 != 0) { //错:是奇数,数据错误
        //     $nscore->msg->stopmsg(4020003,$totpsecret);
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
                $nscore->msg->stopmsg(4020004,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4020005,$totpsecret);
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
                $nscore->msg->stopmsg(4020006,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4020007,$totpsecret);
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
                $nscore->msg->stopmsg(4020008,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4020009,$totpsecret);
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
                $nscore->msg->stopmsg(4020010,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4020011,$totpsecret);
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
                $nscore->msg->stopmsg(4020012,$totpsecret);
            }
        } else { //无=错
            $nscore->msg->stopmsg(4020013,$totpsecret);
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
            $nscore->msg->stopmsg(4020014,$totpsecret);
        }
        return $userhashs;
    }

    //修改社交关系

    /**
    * @description: 关注对方
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    */
    function i_follow_f($me,$who,$totpsecret=null) {
        global $nlcore; global $nscore;
        if ($this->h_ban_i($me,$who)) { //被对方拉黑
            $nscore->msg->stopmsg(4020108,$totpsecret);
        }
        if ($this->i_ban_h($me,$who)) { //将对方拉黑
            $nscore->msg->stopmsg(4020109,$totpsecret);
        }
        // INSERT INTO z1_follow(fuser, tuser) VALUES ('I','W')
        $tableStr = $nscore->cfg->tables["follow"];
        $insertDic = [
            "fuser" => $me,
            "tuser" => $who,
        ];
        $dbreturn = $nlcore->db->insert($tableStr,$insertDic);
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4020100,$totpsecret);
        } else if ($dbreturn[3] == 0) { //重复操作，不操作
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
        if ($dbreturn[0] >= 2000000 || $dbreturn[3] > 1) { //错
            $nscore->msg->stopmsg(4020101,$totpsecret);
        }
        if ($dbreturn[3] == 1) {
            // UPDATE z1_follow SET friend=1 WHERE fuser='I' AND `tuser`='W'
            $updateDic = [
                "friend" => 1
            ];
            $whereDic = [
                "fuser" => $me,
                "tuser" => $who
            ];
            $dbreturn = $nlcore->db->update($updateDic,$tableStr,$whereDic);
            if ($dbreturn[0] >= 2000000 || $dbreturn[3] > 1) { //错
                $nscore->msg->stopmsg(4020102,$totpsecret);
            }
        }
    }

    /**
    * @description: 取关对方
    * @param String me 我的用户哈希
    * @param String who 对方的用户哈希
    * @param String totpsecret 加密用secret
    */
    function i_unfollow_f($me,$who,$totpsecret=null) {
        // DELETE FROM z1_follow WHERE fuser='I' AND tuser='W'
        global $nlcore; global $nscore;
        $tableStr = $nscore->cfg->tables["follow"];
        $whereDic = [
            "fuser" => $me,
            "tuser" => $who
        ];
        $dbreturn = $nlcore->db->delete($tableStr,$whereDic);
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4020103,$totpsecret);
        } else if ($dbreturn[3] != 1) { //重复操作，不报错
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
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4020104,$totpsecret);
        } else if ($dbreturn[3] == 0) { //重复操作，不报错
        }
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
            $nscore->msg->stopmsg(4020110,$totpsecret);
        }
        // INSERT INTO z1_ban(fuser, tuser) VALUES ('I','W')
        $tableStr = $nscore->cfg->tables["ban"];
        $insertDic = [
            "fuser" => $me,
            "tuser" => $who,
        ];
        $dbreturn = $nlcore->db->insert($tableStr,$insertDic);
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4020105,$totpsecret);
        } else if ($dbreturn[3] == 0) { //重复操作，不报错
        }
        // DELETE FROM z1_follow WHERE (fuser='I' or tuser='I') AND (fuser='W' or tuser='W')
        $tableStr = $nscore->cfg->tables["follow"];
        $customWhere = "(fuser = '".$me."' or tuser = '".$me."') AND (fuser = '".$who."' or tuser = '".$who."')";
        $dbreturn = $nlcore->db->delete($tableStr,[],$customWhere);
        if ($dbreturn[0] >= 2000000) { //错
            $nscore->msg->stopmsg(4020106,$totpsecret);
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
            $nscore->msg->stopmsg(4020107,$totpsecret);
        } else if ($dbreturn[3] != 1) { //重复操作，不报错
        }
    }
}
?>