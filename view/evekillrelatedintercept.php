<?php

$ek = Db::queryField("SELECT mKillID FROM zz_manual_mails WHERE eveKillID = :id", "mKillID", array(":id" => $id), 0);
$time = Db::query("SELECT unix_timestamp, solarSystemID FROM zz_participants WHERE killID = :id", array(":id" => "-".$ek), 0);

if(isset($time[0]["unix_timestamp"]))
	$date = date("YmdH00", $time[0]["unix_timestamp"]);
else
	$app->notFound();

$app->redirect("/related/" . $time[0]["solarSystemID"] . "/$date/", 301);