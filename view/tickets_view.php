<?php
$message = array();
$info = User::getUserInfo();
$ticket = Db::query("SELECT * FROM zz_tickets WHERE id = :id", array(":id" => $id), 0);
if($ticket[0]["status"] == 0)
	$message = array("status" => "error", "message" => "Ticket has been closed, you cannot post, only view it");
elseif($ticket[0]["userid"] != $info["id"])
	$app->notFound();
	
if($_POST)
{
	$reply = "";
	if(isset($_POST["reply"]))
		$reply = $_POST["reply"];
	
	if($reply && $ticket[0]["status"] != 0)
	{
		$name = $info["username"];
		$moderator = $info["moderator"];
		$check = Db::query("SELECT * FROM zz_tickets_replies WHERE reply = :reply AND userid = :userid AND belongsTo = :id", array(":reply" => $reply, ":userid" => $info["id"], ":id" => $id), 0);
		if(!$check)
		{
			Db::execute("INSERT INTO zz_tickets_replies (userid, belongsTo, name, reply, moderator) VALUES (:userid, :belongsTo, :name, :reply, :moderator)", array(":userid" => $info["id"], ":belongsTo" => $id, ":name" => $name, ":reply" => $reply, ":moderator" => $moderator));
			$app->redirect("/tickets/view/$id/");
		}
	}
	else
	{
		$message = array("status" => "error", "message" => "No...");
	}
}
	
$replies = Db::query("SELECT * FROM zz_tickets_replies WHERE belongsTo = :id", array(":id" => $id), 0);

$userInfo = User::getUserInfo();
$app->render("tickets_view.html", array("page" => $id, "message" => $message, "ticket" => $ticket, "replies" => $replies));