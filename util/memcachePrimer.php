<?php

$base = dirname(__FILE__);
require_once "$base/../init.php";

Db::execute("set session wait_timeout = 120");

storeResult(Db::query("select * from zz_characters", array(), 0), "select name from zz_characters where characterID = :id", ":id", "characterID", "name");
storeResult(Db::query("select * from zz_corporations", array(), 0), "select name from zz_corporations where corporationID = :id", ":id", "corporationID", "name");
storeResult(Db::query("select * from zz_alliances", array(), 0), "select name from zz_alliances where allianceID = :id", ":id", "allianceID", "name");
storeResult(Db::query("select * from ccp_invTypes", array(), 0), "select typeName from invTypes where typeID = :typeID", ":typeID", "typeID", "typeName");

function storeResult($result, $query, $paramName, $keyColumn, $valueColumn) {
    foreach($result as $rowNum=>$row) {
        $keyValue = $row[$keyColumn];
        $valueValue = $row[$valueColumn];
        $params = array("$paramName" => $keyValue);
        $result = array(array("$valueColumn" => $valueValue));
        $key = Db::getKey($query, $params);
        Memcached::set($key, $result, 3 * 3600);
    }
}
