# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("赞")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"]+"like.php"
salt = ''.join(random.sample(string.ascii_letters + string.digits, 16))
udataarr = {
    "token":jsonfiledata["token"],
    "post":"3b8629Ea43DF976Ed60bfFC06FAB21820e151160A6aC26867a6A6d694Fb46e29",
    "like":2,
    "citetype":"POST"
}
test_core.postarray(uurl,udataarr,True)