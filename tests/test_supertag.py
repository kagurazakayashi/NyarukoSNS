# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("将话题升级为超级话题")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"]+"supertag.php"
udataarr = {
    "token": jsonfiledata["token"],  # 使用者令牌
    "tagName": "你好 世界",  # 要修改的使用者雜湊
    "bgcolor": "FEDFE1",  # 主題色
    "describes": "向这个世界说早安"  # 描述文字
}
test_core.postarray(uurl, udataarr, True)
