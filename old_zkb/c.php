<?php

require_once "init.php";

$result = Db::query("select character_id from zz_characters", array(), 0);
echo json_encode($result);
