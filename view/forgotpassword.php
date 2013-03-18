<?php
if($_POST)
{
    $email = "";
    if(isset($_POST["email"]))
        $email = $_POST["email"];
        
    if(isset($email))
    {
        $exists = Db::queryField("SELECT username FROM zz_users WHERE email = :email", "username", array(":email" => $email), 0);
        if($exists != NULL)
        {
            $date = date("Y-m-d H:i:s", strtotime("+24 hours"));
            $hash = sha1($date.$email);
            
            $alreadySent = Db::queryField("SELECT change_hash FROM zz_users WHERE email = :email", "change_hash", array(":email" => $email), 0);
            if($alreadySent != NULL)
            {
                $message = "A request to reset the password for this email, has already been sent";
                $messagetype = "error";
                $app->render("forgotpassword.html", array("message" => $message, "messagetype" => $messagetype));
            }
            else
            {
                global $baseAddr;
                $subject = "It seems you might have forgotten your password, so here is a link, that'll allow you to reset it: $baseAddr/changepassword/$hash";
                $header = "Password change for $email";
                Db::query("UPDATE zz_users SET change_hash = :hash, change_expiration = :expires WHERE email = :email", array(":hash" => $hash, ":expires" => $date, ":email" => $email));
                Email::send($email, $header, $subject);
                $message = "Sending password change email to: $email";
                $messagetype = "success";
                $app->render("forgotpassword.html", array("message" => $message, "messagetype" => $messagetype));
            }
        }
        else
        {
            $message = "No user with that email exists, try again";
            $messagetype = "error";
            $app->render("forgotpassword.html", array("message" => $message, "messagetype" => $messagetype));
        }
    }
    else
    {
        $message = "An error occured..";
        $messagetype = "error";
        $app->render("forgotpassword.html", array("message" => $message, "messagetype" => $messagetype));
    }
}
else
    $app->render("forgotpassword.html");