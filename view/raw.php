<?php
$jsonRaw = Db::queryField("select kill_json from zz_killmails where killID = :id", "kill_json", array(":id" => $id));
header("Content-Type: application/json");
$json = json_decode($jsonRaw, true);

header("Content-Type: text/plain");
print_r($json);
die();
