<?php
class nysmsg {
    public $imsg = array(
        /*
        ABBCCDD
        A: 3成功 4失败
        BB: 模块，例如「安全类」
        CC: 错误类型
        DD: 详细错误
        */
        1000000 => "操作成功完成",
        // A=3 : NYS正常
        /// A=3\BB=00 : 发表内容
        //// A=3\BB=00\CC=00 : 发帖
        //// A=3\BB=00\CC=00\DD=00 :
        3000000 => "贴文发布成功",
        //// A=3\BB=00\CC=00\DD=01 :
        3000001 => "贴文修改成功",
        //// A=3\BB=00\CC=00\DD=02 :
        3000002 => "贴文删除成功",
        //// A=3\BB=00\CC=01 : 发评论
        //// A=3\BB=00\CC=01\DD=00 :
        3000100 => "评论发布成功",
        //// A=3\BB=00\CC=01\DD=01 :
        3000101 => "评论修改成功",
        //// A=3\BB=00\CC=01\DD=02 :
        3000102 => "评论删除成功",
        //// A=3\BB=00\CC=02 : 浏览
        //// A=3\BB=00\CC=02\DD=00 :
        3000200 => "时间线",
        //// A=3\BB=00\CC=02\DD=01 :
        3000201 => "评论列表",
        // A=4 : NYS错误
        /// A=4\BB=00 : 通用
        //// A=4\BB=00\CC=00 : 未知问题
        ///// A=4\BB=00\CC=00\DD=00 :
        4000000 => "内部错误：未知系统错误。",
        /// A=4\BB=01 : 通用发表内容
        //// A=4\BB=01\CC=00 : 发表内容失败
        ///// A=4\BB=01\CC=00\DD=00 :
        4010000 => "错误：正文内容不能为空。",
        ///// A=4\BB=01\CC=00\DD=01 :
        4010001 => "内部错误：参数不正确。",
        ///// A=4\BB=01\CC=00\DD=02 :
        4010002 => "预留",
        ///// A=4\BB=01\CC=00\DD=03 :
        4010003 => "内部错误：未能将贴文存储到数据库。",
        //// A=4\BB=01\CC=01 : 提及
        ///// A=4\BB=01\CC=01\DD=00 :
        4010100 => "内部错误：昵称需要附加唯一编号。",
        ///// A=4\BB=01\CC=01\DD=01 :
        4010101 => "内部错误：提及不在正文中。",
        //// A=4\BB=01\CC=02 : 文件
        ///// A=4\BB=01\CC=02\DD=00 :
        4010200 => "内部错误：文件路径不正确。",
        //// A=4\BB=01\CC=03 : 标签
        ///// A=4\BB=01\CC=03\DD=00 :
        4010300 => "内部错误：标签不在正文中。",
        ///// A=4\BB=01\CC=03\DD=01 :
        4010301 => "内部错误：标签检索失败。",
        ///// A=4\BB=01\CC=03\DD=02 :
        4010302 => "内部错误：标签热度更新失败。",
        ///// A=4\BB=01\CC=03\DD=03 :
        4010303 => "内部错误：标签更新失败。",
        //// A=4\BB=01\CC=04 : 转发
        ///// A=4\BB=01\CC=04\DD=00 :
        4010400 => "错误：找不到要转发的贴文。",
        ///// A=4\BB=01\CC=04\DD=01 :
        4010401 => "错误：对方设置了禁止转发。",
        ///// A=4\BB=01\CC=04\DD=02 :
        4010402 => "错误：转发数量变更失败。",
        ///// A=4\BB=01\CC=04\DD=03 :
        4010403 => "错误：你不能转发已经拉黑你用户的贴文。",
        //// A=4\BB=01\CC=05 : 编辑
        ///// A=4\BB=01\CC=05\DD=00 :
        4010500 => "内部错误：找不到要编辑的贴文。",
        ///// A=4\BB=01\CC=05\DD=01 :
        4010501 => "错误：贴文已被删除。",
        ///// A=4\BB=01\CC=05\DD=02 :
        4010502 => "内部错误：不允许修改转发的内容。",
        ///// A=4\BB=01\CC=05\DD=03 :
        4010503 => "内部错误：修改贴文失败。",
        //// A=4\BB=01\CC=06 : 删除
        ///// A=4\BB=01\CC=06\DD=00 :
        4010600 => "内部错误：找不到要删除的贴文。",
        ///// A=4\BB=01\CC=06\DD=01 :
        4010601 => "内部错误：贴文删除失败。",
        //// A=4\BB=01\CC=07 : 浏览
        ///// A=4\BB=01\CC=07\DD=00 :
        4010700 => "内部错误：贴文数据完整性校验失败。",
        /// A=4\BB=02 : 回复
        //// A=4\BB=02\CC=00 : 发表回复
        ///// A=4\BB=02\CC=00\DD=00 :
        4020000 => "内部错误：要评论的目标数据不正确。",
        ///// A=4\BB=02\CC=00\DD=01 :
        4020001 => "错误：没有找到要评论的目标，可能已经删除。",
        ///// A=4\BB=02\CC=00\DD=02 :
        4020002 => "错误：回复内容太长了。",
        ///// A=4\BB=02\CC=00\DD=03 :
        4020003 => "内部错误：不受支持的回复格式。",
        ///// A=4\BB=02\CC=00\DD=04 :
        4020004 => "内部错误：回复失败。",
        ///// A=4\BB=02\CC=00\DD=05 :
        4020005 => "错误：请输入要回复的内容。",
        ///// A=4\BB=02\CC=00\DD=06 :
        4020006 => "内部错误：贴文评论数更新失败。",
        ///// A=4\BB=02\CC=00\DD=06 :
        4020007 => "内部错误：修改的评论不正确。",
        //// A=4\BB=02\CC=01 : 修改回复
        ///// A=4\BB=02\CC=01\DD=00 :
        4020100 => "内部错误：找不到该回复对应的目标。",
        //// A=4\BB=02\CC=02 : 删除回复
        ///// A=4\BB=02\CC=02\DD=00 :
        4020200 => "内部错误：查找要删除的回复失败。",
        ///// A=4\BB=02\CC=02\DD=01 :
        4020201 => "内部错误：查找要删除回复的回复失败。",
        ///// A=4\BB=02\CC=02\DD=02 :
        4020202 => "内部错误：删除回复失败。",
        ///// A=4\BB=02\CC=00\DD=00 :
        4020003 => "内部错误：要删除的目标数据不正确。",
        //// A=4\BB=02\CC=03 : 读取回复
        ///// A=4\BB=02\CC=03\DD=00 :
        4020300 => "内部错误：读取评论列表失败。",
        ///// A=4\BB=02\CC=03\DD=01 :
        4020301 => "内部错误：参数不正确。",
        ///// A=4\BB=02\CC=03\DD=02 :
        4020302 => "内部错误：回复数据完整性校验失败。",
        /// A=4\BB=03 : 点赞
        //// A=4\BB=03\CC=00 : 点赞
        ///// A=4\BB=03\CC=00\DD=00 :
        //// A=4\BB=03\CC=01 : 取消点赞
        ///// A=4\BB=03\CC=01\DD=00 :
        4030100 => "内部错误：移除对评论的点赞失败。",
        ///// A=4\BB=03\CC=01\DD=01 :
        4030101 => "内部错误：移除对贴文的点赞失败。",
        /// A=4\BB=04 : 社交关系
        //// A=4\BB=04\CC=00 : 社交关系查询失败
        ///// A=4\BB=04\CC=00\DD=00 :
        4040000 => "内部错误：关注列表读取失败。",
        ///// A=4\BB=04\CC=00\DD=01 :
        4040001 => "内部错误：粉丝列表读取失败。",
        ///// A=4\BB=04\CC=00\DD=02 :
        4040002 => "内部错误：好友列表读取失败。",
        ///// A=4\BB=04\CC=00\DD=03 :
        4040003 => "内部错误：好友列表数据异常。",
        ///// A=4\BB=04\CC=00\DD=04 :
        4040004 => "内部错误：好友数据异常。",
        ///// A=4\BB=04\CC=00\DD=05 :
        4040005 => "内部错误：好友数据读取失败。",
        ///// A=4\BB=04\CC=00\DD=06 :
        4040006 => "内部错误：粉丝关注数据异常。",
        ///// A=4\BB=04\CC=00\DD=07 :
        4040007 => "内部错误：粉丝关注列表读取失败。",
        ///// A=4\BB=04\CC=00\DD=08 :
        4040008 => "内部错误：关注数据异常。",
        ///// A=4\BB=04\CC=00\DD=09 :
        4040009 => "内部错误：关注列表读取失败。",
        ///// A=4\BB=04\CC=00\DD=10 :
        4040010 => "内部错误：屏蔽数据异常。",
        ///// A=4\BB=04\CC=00\DD=11 :
        4040011 => "内部错误：屏蔽数据读取失败。",
        ///// A=4\BB=04\CC=00\DD=12 :
        4040012 => "内部错误：屏蔽数据异常。",
        ///// A=4\BB=04\CC=00\DD=13 :
        4040013 => "内部错误：屏蔽数据读取失败。",
        ///// A=4\BB=04\CC=00\DD=14 :
        4040014 => "内部错误：黑名单读取失败。",
        //// A=4\BB=04\CC=01 : 社交关系修改失败
        ///// A=4\BB=04\CC=01\DD=00 :
        4040100 => "内部错误：关注失败。",
        ///// A=4\BB=04\CC=01\DD=01 :
        4040101 => "内部错误：添加好友失败。",
        ///// A=4\BB=04\CC=01\DD=02 :
        4040102 => "内部错误：添加好友失败。",
        ///// A=4\BB=04\CC=01\DD=03 :
        4040103 => "内部错误：取消关注失败。",
        ///// A=4\BB=04\CC=01\DD=04 :
        4040104 => "内部错误：解除好友关系失败。",
        ///// A=4\BB=04\CC=01\DD=05 :
        4040105 => "内部错误：屏蔽失败。",
        ///// A=4\BB=04\CC=01\DD=06 :
        4040106 => "内部错误：取消关注关系失败。",
        ///// A=4\BB=04\CC=01\DD=07 :
        4040107 => "内部错误：解除屏蔽失败。",
        ///// A=4\BB=04\CC=01\DD=08 :
        4040108 => "错误：你没有权限关注对方。",
        ///// A=4\BB=04\CC=01\DD=09 :
        4040109 => "内部错误：对方在你的黑名单中。",
        ///// A=4\BB=04\CC=01\DD=10 :
        4040110 => "内部错误：对方已在你的黑名单中。",
        /// A=4\BB=05 : 用户系统扩展
        //// A=4\BB=05\CC=00 : 用户信息相关操作
        ///// A=4\BB=05\CC=00\DD=00 :
        4050000 => "内部错误：扩展用户信息创建失败。"
    );
    /**
     * @description: 创建异常信息提示JSON
     * @param Int msgmode 错误信息输出方式：0返回数组，1返回JSON
     * @param String msgmode 输入 totp secret 而不是数字的话，会用此 secret 加密并返回。
     * @param Int code 错误代码
     * @param String/Array info 附加错误信息
     * @param String totpsecret 加密用secret（不加则自动）
     * @return String 返回由 msgmode 设置的 null / json / 加密 json
     */
    function m($msgmode = 0,$code = 4000000,$info = null) {
        $returnarr = array(
            "code" => $code,
            "msg" => $this->imsg[$code]
        );
        if (is_numeric($returnarr["msg"])) $returnarr["msg"] = $this->imsg[$returnarr["msg"]];
        if ($info) $returnarr["info"] = $info;
        if (is_numeric($msgmode) && $msgmode === 0) {
            return $returnarr;
        } else if (is_numeric($msgmode) && $msgmode === 1) {
            return json_encode($returnarr);
        } else {
            global $nlcore;
            return $nlcore->safe->encryptargv($returnarr,$msgmode);
        }
    }
    /**
     * @description: 返回信息，或抛出403错误，结束程序
     * @param Int code 错误代码
     * @param String totpsecret 加密用secret（可选，不加则明文返回）
     * @param String str 附加错误信息
     * @param Bool showmsg 是否显示错误信息（否则直接403）
     */
    function stopmsg($code=null,$totpsecret=null,$str="",$showmsg=true) {
        if ($code && $showmsg > 0) {
            global $nlcore;
            $msgmode = $totpsecret ? $totpsecret : 1;
            $json = $this->m($msgmode,$code,$str,$totpsecret);
            header('Content-Type:application/json;charset=utf-8');
            echo $json;
        } else {
            header('HTTP/1.1 403 Forbidden');
        }
        die();
    }
    function __destruct() {
        $this->imsg = null;
        unset($this->imsg);
    }
}
?>