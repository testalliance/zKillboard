<?php
$error = "";

if($_POST && !User::isRevoked())
{
	@$keyid = trim($_POST["keyid"]);
	@$vcode = trim($_POST["vcode"]);
	@$killmail = $_POST["killmail"];
	$label = "";
		
	// Apikey stuff
	if($keyid || $vcode)
	{
		$check = Api::checkAPI($keyid, $vcode);
		if($check == "success")
		{
			$error = array(Api::addKey($keyid, $vcode, $label));
		}
		else
		{
			$error = array($check);
		}
	}
	
	if($killmail)
	{
		$u = User::getUserInfo();
		if(User::isLoggedIn())
		{
			$return = Parser::parseRaw($killmail, $u["id"]);
			if(isset($return["success"]))
				$app->redirect("/detail/".$return["success"]."/");
			if(isset($return["dupe"]))
				$app->redirect("/detail/".$return["dupe"]."/");
			if(isset($return["error"]))
				$error = $return["error"];
		}
		else
			$error = array("Sorry, you need to be logged in to post manual killmails");
	}
}
if($_POST && User::isRevoked())
	$app->render("revoked.html");
else
	$app->render("postmail.html", array("message" => $error));
