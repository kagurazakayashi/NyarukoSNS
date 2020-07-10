# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("跟随对方")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["zeze"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["zeze"]+"zezefollow.php"
salt = ''.join(random.sample(string.ascii_letters + string.digits, 16))
udataarr = {
    "token":jsonfiledata["token"], #用戶令牌
    "tuser":"M4C16P5V61mJCyZ7QCtUvDnbHcSjBu20BZvntm1apoDM5kwwYe24cSfBqZj2PoDW" #目标用户哈希
}
test_core.postarray(uurl,udataarr,True)