# -*- coding:utf-8 -*-
import test_core
import demjson
import string
test_core.title("查询话题热度")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"] + "taghot.php"
udataarr = {
    "list": 0,
    "limst": 0,
    "offset": 5
}
test_core.postarray(uurl, udataarr, True)
