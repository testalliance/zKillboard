<?php
$info = array();
if($page == "statistics")
{
    $info["kills"] = Db::queryField("select count(*) count from zz_killmails where processed = 1", "count", array(), 300);
    $info["ignored"] = Db::queryField("select count(*) count from zz_killmails where processed = 3", "count", array(), 300);
    $info["total"] = Db::queryField("select count(*) count from zz_killmails", "count", array(), 300);
	//$info["apicallsprhour"] = json_encode(Db::query("select hour(requestTime) as x, count(*) as y from ( select requestTime from zz_api_log where requestTime >= date_sub(now(), interval 24 hour)) as foo group by 1 order by requestTime", array(), 300));
	//var_dump($info);
}
$info["pointValues"] = Points::getPointValues();

$app->render("information.html", array("pageview" => $page, "info" => $info));
