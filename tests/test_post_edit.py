# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("编辑贴文")
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
    "title":"测试贴文",
    "content":("测试贴文 @神楽坂雅詩#5534 #你好 世界#"+salt),
    "type":"image",
    "files":"2019/07/23/15848977730_5ce4d3381e821e82e6899a51ac149554,2019/07/23/15848977730_5ce4d3381e821e82e6899a51ac149553",
    "mention":"神楽坂雅詩#5534",
    "tag":"你好 世界",
    "share":"public",
    "nocomment":0,
    "noforward":0,
    "editpost":"1FbA5A20d66fF37dCeb199ffcd5265057E4ba22D85676d585b83844D44084f84"
}
test_core.postarray(uurl,udataarr,True)