<?php

require_once( "../init.php" );

$result = Info::findNames($_GET["q"]);

header("Content-Type: application/json");
echo json_encode($result);