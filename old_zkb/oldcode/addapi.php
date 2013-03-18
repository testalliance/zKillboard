<?php
require_once "init.php";

$keyid = $_GET['keyID'];
$vcode = $_GET['vCode'];

Db::execute("insert delayed into zz_api (user_id, api_key) values (:keyid, :vcode) on duplicate key update api_key = :vcode", array(":keyid" => $keyid, ":vcode" => $vcode));
