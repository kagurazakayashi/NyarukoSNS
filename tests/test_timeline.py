# -*- coding:utf-8 -*-
import test_core
import demjson
import random
import string
test_core.title("浏览信息流和标签贴文列表")
f = open("testconfig.json", 'r')
lines = f.read()
f.close()
jsonfiledata = demjson.decode(lines)
if jsonfiledata["nys"] == "" or jsonfiledata["token"] == "":
    test_core.terr("错误： 'testconfig.json' 配置不完全。")
    exit()
uurl = jsonfiledata["nys"]+"timeline.php"
salt = ''.join(random.sample(string.ascii_letters + string.digits, 16))
udataarr = {
    "token":jsonfiledata["token"], #用戶令牌
    # "tag":"你好 世界",
    "limst":0, #查詢起始位置
    "limit":10 #查詢數據量
}
test_core.postarray(uurl,udataarr,True)