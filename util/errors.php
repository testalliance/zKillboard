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

$base = dirname(__FILE__);
require_once "$base/../init.php";
require_once "$base/pheal/config.php";
require_once "$base/cron.php";

try {
	$pheal = Util::getPheal();
	$pheal->scope = "eve";
	$errors = $pheal->ErrorList();
	foreach($errors->errors as $error) {
		$errorCode = $error["errorCode"];
		$errorText = $error["errorText"];
		echo "$errorCode $errorText\n";
		$key = "api_error:$errorCode";
		Db::execute("replace into zz_storage (locker, contents) values (:c, :t)", array(":c" => $key, ":t" => $errorText));
	}
} catch (Exception $ex) {
	print_r($ex);
}
