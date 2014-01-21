<?php
if($_POST)
{
    $username = "";
    $password = "";
    $autologin = 0;
    $requesturi = "";

    if(isset($_POST["username"]))
        $username = $_POST["username"];
    if(isset($_POST["password"]))
        $password = $_POST["password"];
    if(isset($_POST["autologin"]))
        $autologin = 1;
    if(isset($_POST["requesturi"]))
        $requesturi = $_POST["requesturi"];

    if(!$username)
    {
        $error = "No username given";
        $app->render("login.html", array("error" => $error));
    }
    elseif(!$password)
    {
        $error = "No password given";
        $app->render("login.html", array("error" => $error));
    }
    elseif($username && $password)
    {
        $check = User::checkLogin($username, $password);
        if($check) // Success
        {
            User::setLogin($username, $password, $autologin);
			$ignoreUris = array("/register/", "/login/", "/logout/");
            if (isset($requesturi) && !in_array($requesturi, $ignoreUris)) {
				$app->redirect($requesturi);
            }
			else
			{
				$app->redirect("/");
			}
        }
        else
        {
            $error = "No such user exists, try again";
            $app->render("login.html", array("error" => $error));
        }
    }
}
else $app->render("login.html");
