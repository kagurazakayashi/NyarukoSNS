# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("删除贴文")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"]+"post.php"
salt = ''.join(random.sample(string.ascii_letters + string.digits, 16))
udataarr = {
    "token":jsonfiledata["token"],
    "delpost":"1FbA5A20d66fF37dCeb199ffcd5265057E4ba22D85676d585b83844D44084f84"
}
test_core.postarray(uurl,udataarr,True)