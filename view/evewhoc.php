<?php

$page = $_GET["page"];
$pageSize = 100000;

$start = $page * $pageSize;

$result = Db::query("select characterID from zz_characters limit $start, $pageSize", array(), 0);
echo json_encode($result);
die();
