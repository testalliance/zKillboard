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

$ek = Db::queryField("SELECT mKillID FROM zz_manual_mails WHERE eveKillID = :id", "mKillID", array(":id" => $id), 0);
$time = Db::query("SELECT dttm, solarSystemID FROM zz_participants WHERE killID = :id", array(":id" => "-".$ek), 0);

if(isset($time[0]["dttm"]))
	$date = date("YmdH00", strtotime($time[0]["dttm"]));
else
	$app->notFound();

$app->redirect("/related/" . $time[0]["solarSystemID"] . "/$date/", 301);
