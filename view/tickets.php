<?php
$message = array();

if($_POST)
{
	$tags = "";
	$ticket = "";

	$info = User::getUserInfo();
	$name = $info["username"];
	$email = $info["email"];
	if(isset($_POST["hidden-tags"]))
		$tags = $_POST["hidden-tags"];
	if(isset($_POST["ticket"]))
		$ticket = $_POST["ticket"];
		
	if(isset($name) && isset($email) && isset($tags) && isset($ticket))
	{
		$check = Db::query("SELECT * FROM zz_tickets WHERE ticket = :ticket AND email = :email", array(":ticket" => $ticket, ":email" => $email), 0);
		if(!$check)
		{
			Db::execute("INSERT INTO zz_tickets (userid, name, email, tags, ticket) VALUES (:userid, :name, :email, :tags, :ticket)", array(":userid" => User::getUserID(), ":name" => $name, ":email" => $email, ":tags" => $tags, ":ticket" => $ticket));
			$id = Db::queryField("SELECT id FROM zz_tickets WHERE userid = :userid AND name = :name AND tags = :tags AND ticket = :ticket", "id", array(":userid" => User::getUserID(), ":name" => $name, ":tags" => $tags, ":ticket" => $ticket));
			$app->redirect("/tickets/view/$id/");
		}
		else
			$message = array("type" => "error", "message" => "Ticket already posted");
	}
	else
	{
		die("no");
	}
	die();
}

$tickets = Db::query("SELECT * FROM zz_tickets WHERE userid = :userid ORDER BY datePosted DESC", array(":userid" => User::getUserID()), 0);
foreach($tickets as $key => $val)
{
	if($val["tags"])
		$tickets[$key]["tags"] = explode(",", $val["tags"]);
}

$userInfo = User::getUserInfo();
$app->render("tickets.html", array("userInfo" => $userInfo, "tickets" => $tickets, "message" => $message));