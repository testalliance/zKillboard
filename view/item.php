<?php

if (!is_numeric($id))
{
    $id = Info::getItemId($id);
    if ($id > 0) header("Location: /item/$id/");
    else header("Location: /");
    die();
}

$info = Db::queryRow("select typeID, typeName, description from ccp_invTypes where typeID = :id", array(":id" => $id), 3600);
$info["description"] = str_replace("<br>", "\n", $info["description"]);
$info["description"] = strip_tags($info["description"]);
$hasKills = 1 == Db::queryField("select 1 as hasKills from zz_participants where shipTypeID = :id limit 1", "hasKills", array(":id" => $id), 3600);

$app->render("item.html", array("info" => $info, "hasKills" => $hasKills));
