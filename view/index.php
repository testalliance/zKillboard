<?php
global $subDomainKey, $subDomainRow;
if ($subDomainRow) {
	include( "view/overview.php" );
	return;
}

$topIsk = Stats::getTopIsk(array("pastSeconds" => (3*86400), "limit" => 5));
$topPods = Stats::getTopIsk(array("shipTypeID" => 670, "pastSeconds" => (3*86400), "limit" => 5));
$topPointList = Stats::getTopPoints("killID", array("losses" => true, "pastSeconds" => (3*86400), "limit" => 5));
$topPoints = Kills::getKillsDetails($topPointList);

$app->render("index.html", array("topPods" => $topPods, "topIsk" => $topIsk, "topPoints" => $topPoints));
