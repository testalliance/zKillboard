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

if (!User::isLoggedIn()) {
	$app->render("login.html");
	die();
}

$userID = User::getUserID();
$key = "me";
$error = "";

if(isset($req))
	$key = $req;

if($_POST)
{
	// Check for adfree purchase
	$purchase = Util::getPost("purchase");
	if ($purchase)
	{
		global $twig;
		if ($purchase == "donate")
		{
			$amount = User::getBalance($userID);
			if ($amount > 0) {
				Db::execute("insert into zz_account_history (userID, purchase, amount) values (:userID, :purchase, :amount)",
					array(":userID" => $userID, ":purchase" => "donation", ":amount" => $amount));
				Db::execute("update zz_account_balance set balance = 0 where userID = :userID", array(":userID" => $userID));
				$twig->addGlobal("accountBalance", User::getBalance($userID));
				$error = "Thank you VERY much for your donation!";
			} else $error = "Gee, thanks for nothing...";
		}
		else
		{
			global $adFreeMonthCost;

			$months = str_replace("buy", "", $purchase);
			if ($months > 12 || $months < 0) $months = 1;
			$balance = 1000000; User::getBalance($userID);
			$amount = $adFreeMonthCost * $months;
			$bonus = floor($months / 6);
			$months += $bonus;
			if ($balance >= $amount && $months)
			{
				$dttm = UserConfig::get("adFreeUntil", null);
				$now = $dttm == null ? " now() " : "'$dttm'";
				$newDTTM = Db::queryField("select date_add($now, interval $months month) as dttm", "dttm", array(), 0);
				Db::execute("update zz_account_balance set balance = balance - :amount where userID = :userID",
						array(":userID" => $userID, ":amount" => $amount));
				Db::execute("insert into zz_account_history (userID, purchase, amount) values (:userID, :purchase, :amount)",
						array(":userID" => $userID, ":purchase" => $purchase, ":amount" => $amount));
				UserConfig::set("adFreeUntil", $newDTTM);

				$twig->addGlobal("accountBalance", User::getBalance($userID));
				$error = "Funds have been applied for $months month" . ($months == 1 ? "" : "s") . ", you are now ad free until $newDTTM";
				Log::log("Ad free time purchased by user $userID for $months months with " . number_format($amount) . " ISK");
			} else $error = "Insufficient Funds... Nice try though....";
		}
	}


	$keyid = Util::getPost("keyid");
	$vcode = Util::getPost("vcode");
	$label = Util::getPost("label");
	// Apikey stuff
	if(isset($keyid) || isset($vcode))
	{
		$check = Api::checkAPI($keyid, $vcode);
		if($check == "success")
		{
			$error = Api::addKey($keyid, $vcode, $label);
		}
		else
		{
			$error = $check;
		}
	}

	$deletesessionid = Util::getPost("deletesessionid");
	// delete a session
	if(isset($deletesessionid))
		User::deleteSession($userID, $deletesessionid);

	$deletekeyid = Util::getPost("deletekeyid");
	$deleteentity = Util::getPost("deleteentity");
	// Delete an apikey
	if(isset($deletekeyid) && !isset($deleteentity))
		$error = Api::deleteKey($deletekeyid);

	$viewtheme = Util::getPost("viewtheme");
	// Theme stuff
	if(isset($viewtheme))
	{
		UserConfig::set("viewtheme", $viewtheme);
		$app->redirect($_SERVER["REQUEST_URI"]);
	}

	$theme = Util::getPost("theme");
	if(isset($theme))
		UserConfig::set("theme", $theme);

	$orgpw = Util::getPost("orgpw");
	$password = Util::getPost("password");
	$password2 = Util::getPost("password2");
	// Password
	if(isset($orgpw) && isset($password) && isset($password2))
	{
		if($password != $password2)
			$error = "Passwords don't match, try again";
		elseif(Password::checkPassword($orgpw) == true)
		{
			Password::updatePassword($password);
			$error = "Password updated";
		}
		else
			$error = "Original password is wrong, please try again";
	}

	$timeago = Util::getPost("timeago");
	if(isset($timeago))
		UserConfig::set("timeago", $timeago);

	$deleteentityid = Util::getPost("deleteentityid");
	$deleteentitytype = Util::getPost("deleteentitytype");
	// Tracker
	if(isset($deleteentityid) && isset($deleteentitytype))
	{
		$q = UserConfig::get("tracker_" . $deleteentitytype);
		foreach($q as $k => $ent)
		{
			if($ent["id"] == $deleteentityid)
			{
				unset($q[$k]);
				$error = $ent["name"]." has been removed";
			}
		}
		UserConfig::set("tracker_" . $deleteentitytype, $q);
	}

	$entity = Util::getPost("entity");
	$entitymetadata = Util::getPost("entitymetadata");
	// Tracker
	if((isset($entity) && $entity != null) && (isset($entitymetadata) && $entitymetadata != null))
	{
		$entitymetadata = json_decode("$entitymetadata", true);
		$entities = UserConfig::get("tracker_" . $entitymetadata['type']);
		$entity = array('id' => $entitymetadata['id'], 'name' => $entitymetadata['name']);

		if(empty($entities) || !in_array($entity, $entities))
		{
			$entities[] = $entity;
			UserConfig::set("tracker_" . $entitymetadata['type'], $entities);
			$error = "{$entitymetadata['name']} has been added to your tracking list";
		}
		else
			$error = "{$entitymetadata['name']} is already being tracked";
	}

	$ddcombine = Util::getPost("ddcombine");
	if(isset($ddcombine))
		UserConfig::set("ddcombine", $ddcombine);

	$ddmonthyear = Util::getPost("ddmonthyear");
	if(isset($ddmonthyear))
		UserConfig::set("ddmonthyear",$ddmonthyear);

	$useSummaryAccordion = Util::getPost("useSummaryAccordion");
	if(isset($useSummaryAccordion))
		UserConfig::set("useSummaryAccordion", $useSummaryAccordion);
}

$data["entities"] = Account::getUserTrackerData();
$data["themes"] = Util::bootstrapThemes();
$data["viewthemes"] = Util::themesAvailable();
$data["apiKeys"] = Api::getKeys($userID);
$data["apiChars"] = Api::getCharacters($userID);
$charKeys = Api::getCharacterKeys($userID);
$charKeys = Info::addInfo($charKeys);
$data["apiCharKeys"] = $charKeys;
$data["userInfo"] = User::getUserInfo();
$data["currentTheme"] = UserConfig::get("theme", "default");
$data["timeago"] = UserConfig::get("timeago");
$data["ddcombine"] = UserConfig::get("ddcombine");
$data["ddmonthyear"] = UserConfig::get("ddmonthyear");
$data["useSummaryAccordion"] = UserConfig::get("useSummaryAccordion");
$data["sessions"] = User::getSessions($userID);
$data["history"] = User::getPaymentHistory($userID);

$app->render("account.html", array("data" => $data, "message" => $error, "key" => $key, "reqid" => $reqid));
