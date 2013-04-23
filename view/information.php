<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
