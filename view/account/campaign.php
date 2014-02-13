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

$error = "";
$data = array();

$campaignID = Util::getPost("campaignID");
$userID = User::getUserID();

$campaign = Db::queryRow("SELECT * FROM zz_campaigns WHERE id = :id and userID = :userID", array(":id" => $id, ":userID" => $userID), 0);
if (!$campaign) {
	$error = "This is not your campaign or that campaign does not exist.";
} else {
	$definition = json_decode($result, true);

}
