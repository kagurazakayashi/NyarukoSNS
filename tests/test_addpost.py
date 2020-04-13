# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("发表动态")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["zeze"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["zeze"]+"post.php"
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
    "cite":"41bab493640883282192004f96C3919B04409a49F48C370baa679D7c2987693F"
}
test_core.postarray(uurl,udataarr,True)