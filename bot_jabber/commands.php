<?php
require_once( "/storage/www/zkb/init.php" );
$message = "";
$totalCount = Db::queryField("select count(*) count from zz_killmail", "count", array(), 000);
$message .= "Total kills:|n| " . number_format($totalCount);
$killCount = Db::queryField("select count(*) count from zz_killmail where processed = 1", "count", array(), 000);
$message .= " / Actual Kills:|n| " . number_format($killCount);
$userCount = Db::queryField("select count(*) count from zz_users", "count", array(), 000);
$message .= " / Users:|n| " . number_format($userCount);
$apiCount = Db::queryField("select count(*) count from zz_api where errorCode = 0", "count", array(), 000);
$message .= " / Valid APIs:|n| " . number_format($apiCount);