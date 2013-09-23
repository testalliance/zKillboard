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
 
// Load Twig globals
$app->view(new \Slim\Extras\Views\Twig());

// Theme
$viewtheme = null;
if(User::isLoggedIn())
	$viewtheme = UserConfig::get("viewtheme");
$cachepath = "cache/templates/" . ($viewtheme ? $viewtheme : "bootstrap");

\Slim\Extras\Views\Twig::$twigOptions = array(
    'charset'           => 'utf-8',
    'cache'             => $cachepath,
    'auto_reload'       => true,
    'strict_variables'  => false,
    'autoescape'        => true
);

$twig = $app->view()->getEnvironment();

// Twig globals
$twig->addGlobal("image_character", $imageServer."Character/");
$twig->addGlobal("image_corporation", $imageServer."Corporation/");
$twig->addGlobal("image_alliance", $imageServer."Alliance/");
$twig->addGlobal("image_item", $imageServer."Type/");
$twig->addGlobal("image_ship", $imageServer."Render/");

$twig->addGlobal("siteurl", $baseAddr);
$twig->addGlobal("fullsiteurl", $fullAddr);
$twig->addGlobal("requesturi", $_SERVER["REQUEST_URI"]);
$twig->addGlobal("topad", Adsense::top());
$twig->addGlobal("bottomad", Adsense::bottom());
$twig->addGlobal("mobiletopad", Adsense::mobileTop());
$twig->addGlobal("mobilebottomad", Adsense::mobileBottom());
$twig->addGlobal("igbtopad", Adsense::igbTop());
$twig->addGlobal("igbbottomad", Adsense::igbBottom());

$detect = new Mobile_Detect();
$twig->addGlobal("isMobile", ($detect->isMobile() ? true : false));
$twig->addGlobal("isTablet", ($detect->isTablet() ? true : false));

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
