# -*- coding:utf-8 -*-
import test_core
import demjson
import string
test_core.title("发表动态")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"]+"searchtag.php"
udataarr = {
    "token": jsonfiledata["token"],
    "tag": "你"
}
test_core.postarray(uurl, udataarr, True)
