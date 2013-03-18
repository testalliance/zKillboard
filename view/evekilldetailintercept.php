<?php

$ek = Db::queryField("SELECT mKillID FROM zz_manual_mails WHERE eveKillID = :id", "mKillID", array(":id" => $id), 0);

if($ek > 0)
	$app->redirect("/detail/-$ek/", 301);
else
	$app->notFound();