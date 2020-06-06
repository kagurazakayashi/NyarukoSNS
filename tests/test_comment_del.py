# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("修改评论")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"]+"comment.php"
salt = ''.join(random.sample(string.ascii_letters + string.digits, 16))
udataarr = {
    "token":jsonfiledata["token"],
    "delcomment":"466886F92048e094b28535347890dcBdf9Fc6a2c3d31973F6267D30687D9F610",
    "citetype":"post"
}
test_core.postarray(uurl,udataarr,True)