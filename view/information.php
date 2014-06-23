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

$validPages = array("about", "legal", "killmails", "payments", "faq");
if (!in_array($page, $validPages)) $app->redirect("/");

$info = array();
$info["kills"] = Storage::retrieve("totalKills");
$info["total"] = Storage::retrieve("actualKills");
$info["pointValues"] = Points::getPointValues();
$info["NextWalletFetch"] = Storage::retrieve("NextWalletFetch");
$info["apistats"] = Db::query("select errorCode, count(*) count from zz_api_log where requestTime >= date_sub(now(), interval 1 hour) group by 1");

$app->render("information/$page.html", array("pageview" => $page, "info" => $info));
