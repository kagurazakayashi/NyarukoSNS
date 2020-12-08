<?php
class nystag {
    /**
     * @description: 獲取某個貼文或評論原有的標籤
     * @param String pohash 貼文或評論的雜湊
     * @param Int potype 指定是 0貼文 1評論
     * @param Int gettype 取出標籤的 0雜湊 1內容 2[雜湊:內容] 3[雜湊:[详细內容]]
     * @return Array [雜湊或內容]/[[雜湊:內容]]
     */
    function postTagsGet(string $pohash, int $potype = 0, int $gettype = 1): array {
        global $nlcore;
        global $nscore;
        $tagHashs = [];
        // 檢查原來都有哪些 taghash
        $tableStr = $nscore->cfg->tables["posttag"];
        $columnArr = ["taghash"];
        $whereDic = [
            "type" => $potype,
            "post" => $pohash
        ];
        $dbReturn = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($dbReturn[0] == 1010000) {
            // 原來有 taghash
            $returndata = $dbReturn[2];
            foreach ($returndata as $nowdata) {
                array_push($tagHashs, $nowdata["taghash"]);
            }
        } else if ($dbReturn[0] == 1010001) {
            // 原來沒有 taghash
            return [];
        } else {
            $nscore->msg->stopmsg(4010301, strval($potype) . ':' . $pohash);
        }
        if ($gettype == 0) return $tagHashs;
        // 用標籤雜湊去查詢標籤內容
        $tableStr = $nscore->cfg->tables["tag"];
        $columnArr = $gettype == 3 ? ["*"] : ["taghash", "tag"];
        $whereDic = [];
        for ($i = 0; $i < count($tagHashs); $i++) {
            $taghash = $tagHashs[$i];
            $whereDic["taghash*" . strval($i)] = $taghash;
        }
        $dbReturn = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($dbReturn[0] == 1010000) {
            $returndata = $dbReturn[2];
            $tagInfo = [];
            foreach ($returndata as $nowdata) {
                $nowTagHash = $nowdata["taghash"];
                if ($gettype == 3) {
                    $nowTagName = $nowdata;
                    unset($nowTagName["taghash"]);
                } else {
                    $nowTagName = $nowdata["tag"];
                }
                $tagInfo[$nowTagHash] = $nowTagName;
            }
            if ($gettype == 1) return array_values($tagInfo);
            if ($gettype == 2 || $gettype == 3) return $tagInfo;
        }
        $nscore->msg->stopmsg(4010304, strval($potype) . ':' . $pohash);
        return [];
    }

    /**
     * @description: 移除某個貼文或評論與的tag的關聯
     * @param String pohash 貼文或評論的雜湊
     * @param Int type 指定是貼文還是評論
     */
    function postTagsRemoveAll(string $pohash, int $potype = 0): void {
        global $nlcore;
        global $nscore;
        $tagInfosArr = $this->postTagsGet($pohash, $potype, 3);
        foreach ($tagInfosArr as $tagHash => $tagInfos) {
            $this->postTagAddHot($tagInfos, false);
        }
        $tableStr = $nscore->cfg->tables["posttag"];
        $whereDic = [
            "type" => strval($potype),
            "post" => $pohash
        ];
        $dbReturn = $nlcore->db->delete($tableStr, $whereDic);
        if ($dbReturn[0] >= 2000000) {
            $nscore->msg->stopmsg(4010305, strval($potype) . ':' . $pohash);
        }
    }

    /**
     * @description: 將貼文和標籤相關聯
     * @param String pohash 貼文或評論的雜湊
     * @param Int potype 指定是 0貼文 1評論
     * @param String taghash 標籤雜湊
     */
    function postTagsAddLink(string $pohash, int $potype = 0, string $taghash):void {
        global $nlcore;
        global $nscore;
        $tableStr = $nscore->cfg->tables["posttag"];
        $insertDic = [
            "type" => strval($potype),
            "post" => $pohash,
            "taghash" => $taghash
        ];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) {
            $nscore->msg->stopmsg(4010306);
        }
    }

    /**
     * @description: 檢查當前標籤是否存在
     * @param String tagName 標簽內容
     * @return Array 如果這個標籤已經存在，則返回此標籤的詳細資訊以供重新計數。
     *               如果這個標籤不存在，返回的陣列中將只有標籤內容(tag)。
     */
    function postTagExists(string $tagName): array {
        global $nlcore;
        global $nscore;
        $tableStr = $nscore->cfg->tables["tag"];
        $columnArr = ["id", "tag", "taghash", "stat", "hot", "hotday", "hotweek", "hotmon", "hotyear", "hotmax"];
        $whereDic = [
            "tag" => $tagName
        ];
        $dbReturn = $nlcore->db->select($columnArr, $tableStr, $whereDic);
        if ($dbReturn[0] == 1010000) { //成功，可以去寫熱度
            $returndata = $dbReturn[2][0];
            return $returndata;
        } else if ($dbReturn[0] == 1010001) { //需要新增
            return $whereDic;
        } else {
            $nscore->msg->stopmsg(2040108);
        }
    }

    /**
     * @description: 為當前標籤增加或減少熱度（先透過 postTagExists 函式檢查是否需要）
     * @param Array postTagExistsData postTagExists函式返回的陣列
     * @param Bool isAdd 增加(true)還是減少(false)熱度
     * @return Int 被修改的 tag ID
     */
    function postTagAddHot(array $postTagExistsData, bool $isAdd = true): int {
        global $nlcore;
        global $nscore;
        $tableStr = $nscore->cfg->tables["tag"];
        $addNum = $isAdd ? 1 : -1;
        $nlcore->safe->intStringAdd(
            $addNum,
            $postTagExistsData["hot"],
            $postTagExistsData["hotday"],
            $postTagExistsData["hotweek"],
            $postTagExistsData["hotmon"],
            $postTagExistsData["hotyear"]
        );
        if ($isAdd) $nlcore->safe->intStringAdd($addNum, $postTagExistsData["hotmax"]);
        $nlcore->safe->stringGreaterThanNum(
            0,
            $postTagExistsData["hot"],
            $postTagExistsData["hotday"],
            $postTagExistsData["hotweek"],
            $postTagExistsData["hotmon"],
            $postTagExistsData["hotyear"]
        );
        $updateDic = $nlcore->safe->dicExtract($postTagExistsData, "hot", "hotday", "hotweek", "hotmon", "hotyear");
        $updateDic["ntime"] = $nlcore->safe->getnowtimestr();
        if ($isAdd) $updateDic["hotmax"] = $postTagExistsData["hotmax"];
        $whereDic = ["id" => $postTagExistsData["id"]];
        $result = $nlcore->db->update($updateDic, $tableStr, $whereDic);
        if ($result[0] >= 2000000) {
            $nscore->msg->stopmsg(4010302);
        }
        return intval($postTagExistsData["id"]);
    }

    /**
     * @description: 根據函式 postTagExists 的結果，自動選擇是新增標籤還是增長標籤熱度
     * @param Array postTagExistsData postTagExists函式返回的陣列
     * @return Int 新建或被修改的 tag ID。
     */
    function postTagAutoAdd(array $postTagExistsData): int {
        if (count($postTagExistsData) == 1) {
            return $this->postTagNew($postTagExistsData["tag"]);
        } else if (count($postTagExistsData) > 1) {
            return $this->postTagAddHot($postTagExistsData);
        }
        return -1;
    }

    /**
     * @description: 將貼文和多個標籤批次進行關聯
     * @param String pohash 貼文或評論的雜湊
     * @param Int potype 指定是 0貼文 1評論
     * @param Array postTagExistsData postTagExists函式返回的陣列
     */
    function postTagsAutoAddLink(string $pohash, int $potype, array $postTagExistsData): void {
        foreach ($postTagExistsData as $postdata) {
            $taghash = $postdata["taghash"];
            $this->postTagsAddLink($pohash, $potype, $taghash);
        }
    }

    /**
     * @description: 輸入引數陣列批次執行上述函式
     * @param Array postTagExistsData postTagExists函式返回的陣列
     * @return Array 新建或被修改的 tag ID 陣列。
     */
    function postTagAutoAdds(array $postTagExistsDataArr): array {
        $returnArr = [];
        for ($i = 0; $i < count($postTagExistsDataArr); $i++) {
            $postTagExistsData = $postTagExistsDataArr[$i];
            $returnInt = $this->postTagAutoAdd($postTagExistsData);
            array_push($returnArr, $returnInt);
        }
        return $returnArr;
    }

    /**
     * @description: 登記一個新的標籤雜湊（先透過 postTagExists 函式檢查是否需要）
     * @param String tagName 標簽內容
     * @return Int 新建的 tag ID
     */
    function postTagNew(string $tagName): int {
        global $nlcore;
        global $nscore;
        $tableStr = $nscore->cfg->tables["tag"];
        $tagHash = $nlcore->safe->md6($tagName);
        $insertDic = [
            "taghash" => $tagHash,
            "tag" => $tagName
        ];
        $result = $nlcore->db->insert($tableStr, $insertDic);
        if ($result[0] >= 2000000) {
            $nscore->msg->stopmsg(4010303);
        }
        return intval($result[1]);
    }
}
