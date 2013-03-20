<?php
// Load Twig globals
$app->view(new \Slim\Extras\Views\Twig());

\Slim\Extras\Views\Twig::$twigOptions = array(
    'charset'           => 'utf-8',
    'cache'             => "cache/templates",
    'auto_reload'       => true,
    'strict_variables'  => false,
    'autoescape'        => true
);

$twig = $app->view()->getEnvironment();
// Twig globals
$twig->addGlobal("siteurl", $baseAddr);
$twig->addGlobal("fullsiteurl", "https://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]);
$twig->addGlobal("image_character", "//image.zkillboard.com/Character/");
$twig->addGlobal("image_corporation", "//image.zkillboard.com/Corporation/");
$twig->addGlobal("image_alliance", "//image.zkillboard.com/Alliance/");
$twig->addGlobal("image_item", "//image.zkillboard.com/Type/");
$twig->addGlobal("image_ship", "//image.zkillboard.com/Render/");
$twig->addGlobal("requesturi", $_SERVER["REQUEST_URI"]);
$twig->addGlobal("topad", Adsense::top());
$twig->addGlobal("bottomad", Adsense::bottom());
$twig->addGlobal("mobilead", Adsense::mobile());
$twig->addGlobal("isMobile", mDetect::isMobile());
$twig->addGlobal("isTablet", mDetect::isTablet());

$twig->addExtension(new UserGlobals());

$twig->addFunction("pageTimer", new Twig_Function_Function("Util::pageTimer"));
$twig->addFunction("queryCount", new Twig_Function_Function("Db::getQueryCount"));
$twig->addFunction("isActive", new Twig_Function_Function("Util::isActive"));
$twig->addFunction("firstUpper", new Twig_Function_Function("Util::firstUpper"));
$twig->addFunction("pluralize", new Twig_Function_Function("Util::pluralize"));
$twig->addFunction("calcX", new Twig_Function_Function("Util::calcX"));
$twig->addFunction("calcY", new Twig_Function_Function("Util::calcY"));
$twig->addFunction("formatIsk", new Twig_Function_Function("Util::formatIsk"));
$twig->addFunction("shortNum", new Twig_Function_Function("Util::formatIsk"));
$twig->addFunction("shortString", new Twig_Function_Function("Util::shortString"));
$twig->addFunction("truncate", new Twig_Function_Function("Util::truncate"));
$twig->addFunction("chart", new Twig_Function_Function("Chart::addChart"));
$twig->addFunction("getMonth", new Twig_Function_Function("Util::getMonth"));
$twig->addFunction("getLongMonth", new Twig_Function_Function("Util::getLongMonth"));

$igb = false;
if(stristr(@$_SERVER["HTTP_USER_AGENT"], "EVE-IGB"))
	$igb = true;
$twig->addGlobal("eveigb", $igb);
