<?php

if (!User::isAdmin()) $app->notFound();

if(!isset($req))
    $req = "users";

$message = "";

if($_POST)
{
    if(isset($_POST["blog"]))
        $blog = $_POST["blog"];
    if(isset($_POST["title"]))
        $title = $_POST["title"];
    if(isset($_POST["delete"]))
        $delete = $_POST["delete"];
    if(isset($_POST["ircuserid"]))
        $ircuserid = $_POST["ircuserid"];
    if(isset($_POST["accessLevelID"]))
        $accessLevelID = $_POST["accessLevelID"];
    if(isset($_POST["accessLevel"]))
        $accessLevel = $_POST["accessLevel"];
    if(isset($_POST["grantadmin"]))
		$grantadmin = $_POST["grantadmin"];
    if(isset($_POST["grantmoderator"]))
		$grantmoderator = $_POST["grantmoderator"];
    if(isset($_POST["grantaccess"]))
		$grantaccess = $_POST["grantaccess"];
    if(isset($_POST["revokeadmin"]))
		$revokeadmin = $_POST["revokeadmin"];
    if(isset($_POST["revokemoderator"]))
		$revokemoderator = $_POST["revokemoderator"];
    if(isset($_POST["revokeaccess"]))
		$revokeaccess = $_POST["revokeaccess"];
	if(isset($_POST["reason"]))
		$reason = $_POST["reason"];
	if(isset($_POST["userID"]))
		$userID = $_POST["userID"];
		
    if(isset($blog) && isset($title))
    {
        $url = str_replace(" ", "-", $title);
        $url = urlencode($url);
        $by = User::getUserInfo();
        Db::execute("INSERT INTO zz_blog (url, title, postedBy, post) VALUES (:url, :title, :by, :post)", array(":url" => $url, ":title" => $title, ":by" => $by["username"], ":post" => $blog));
        $message = "Blog post is inserted";
    }
    if(isset($delete))
    {
        Db::execute("DELETE FROM zz_blog WHERE url = :url", array(":url" => $delete));
        $url = "/blog/".$delete."/";
        Db::execute("DELETE FROM zz_comments WHERE pageID = :url", array(":url" => $url));
        $message = "Blog post deleted";
    }
    if(isset($ircuserid))
    {
        Db::execute("DELETE FROM zz_irc_access WHERE id = :id", array(":id" => $ircuserid));
        $message = "User deleted";
    }
    if(isset($accessLevel) && isset($accessLevelID))
    {
        Db::execute("UPDATE zz_irc_access SET accessLevel = :accessLevel WHERE id = :id", array(":accessLevel" => $accessLevel, ":id" => $accessLevelID));
        $message = "User's access has been updated";
    }
	if(isset($grantadmin) && isset($userID))
	{
		Db::execute("UPDATE zz_users SET admin = :access WHERE id = :id", array(":id" => $userID, ":access" => 1));
		$message = "User has been granted admin access";
	}
	if(isset($revokeadmin) && isset($userID))
	{
		Db::execute("UPDATE zz_users SET admin = :access WHERE id = :id", array(":id" => $userID, ":access" => 0));
		$message = "User has had admin access revoked";
	}
	if(isset($grantmoderator) && isset($userID))
	{
		Db::execute("UPDATE zz_users SET moderator = :access WHERE id = :id", array(":id" => $userID, ":access" => 1));
		$message = "User has been granted moderator access";
	}
	if(isset($revokemoderator) && isset($userID))
	{
		Db::execute("UPDATE zz_users SET moderator = :access WHERE id = :id", array(":id" => $userID, ":access" => 0));
		$message = "User has had moderator access revoked";
	}
	if(isset($grantaccess) && isset($userID))
	{
		Db::execute("UPDATE zz_users SET revoked = :access WHERE id = :id", array(":id" => $userID, ":access" => 0));
		$message = "User has been granted access to the site";
	}
	if(isset($revokeaccess) && isset($userID) && isset($reason))
	{
		Db::execute("UPDATE zz_users SET revoked = :access WHERE id = :id", array(":id" => $userID, ":access" => 1));
		Db::execute("UPDATE zz_users SET revoked_reason = :reason WHERE id = :id", array(":id" => $userID, ":reason" => $reason));
		$message = "User has had access to the site revoked";
	}
}

if($req == "users")
{
    $info = Db::query("SELECT * FROM zz_users order by username", array(), 0);
}
elseif($req == "blog")
{
    $info = Db::query("SELECT * FROM zz_blog ORDER BY date DESC", array(), 0);
    foreach($info as $key => $val)
    {
        // Should probably do a query here to find all the comments, and append them or something.. oh well
        $info[$key]["commentcount"] = Comments::getPageCommentCount("blog:".$info[$key]["blogID"]);
    }
}
elseif($req == "revokes")
{
    $info = Db::query("SELECT id, username, email, revoked_reason FROM zz_users WHERE revoked = 1 ORDER BY id DESC", array(), 0);
}
elseif($req == "irc")
{
    $info = Db::query("SELECT * FROM zz_irc_access ORDER BY name DESC", array(), 0);
}
elseif($req == "commandlog")
{
    $info = Db::query("SELECT * FROM zz_irc_log ORDER BY date DESC", array(), 0);
}
elseif($req == "email")
{
    $info = array();
}

$app->render("admin/admin.html", array("info" => $info, "key" => $req, "message" => $message));
