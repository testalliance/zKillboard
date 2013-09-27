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
        $app->render("dlogin.html", array("error" => $error));
    }
    elseif(!$password)
    {
        $error = "No password given";
        $app->render("dlogin.html", array("error" => $error));
    }   
    elseif($username && $password)
    {
        $check = User::checkLogin($username, $password);
        if($check) // Success
        {
            $bool = User::setLogin($username, $password, $autologin);
            $app->render("dlogin.html", array("close" => $bool));
        }
        else
        {
            $error = "No such user exists, try again";
            $app->render("dlogin.html", array("error" => $error));
        }
    }
}
else $app->render("dlogin.html");
