# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("获取用户信息")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"]+"userinfo.php"
salt = ''.join(random.sample(string.ascii_letters + string.digits, 16))
udataarr = {
    "token":jsonfiledata["token"],
    "cuser":"Dl4oGEJoyqf00yPXEbohjWYZExy4n7dXbaebMmgCVMLbNkn0C9bZqtPi1mkGdjwo",
    "subinfo":3
}
test_core.postarray(uurl,udataarr,True)