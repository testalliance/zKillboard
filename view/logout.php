<?php

if(isset($_SERVER["HTTP_REFERER"])) $requesturi = $_SERVER["HTTP_REFERER"];

unset($_SESSION["loggedin"]);
$app->view(new \Slim\Extras\Views\Twig());
$twig = $app->view()->getEnvironment();
$twig->addGlobal("sessionusername", "");
$twig->addGlobal("sessionuserid", "");
$twig->addGlobal("sessionadmin", "");
$twig->addGlobal("sessionmoderator", "");
setcookie($cookie_name, "", time()-$cookie_time, "/");
setcookie($cookie_name, "", time()-$cookie_time, "/", ".".$baseAddr);
if (isset($requesturi)) $app->redirect($requesturi);
else $app->render("logout.html", array("message" => "You are now logged out"));
