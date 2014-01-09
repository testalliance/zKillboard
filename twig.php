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

$uri = $_SERVER["REQUEST_URI"];
$explode = explode("/", $uri);
foreach($explode as $key => $ex)
{

    if(in_array($ex, array("year", "month", "page")))
    {
        // find the key for the page array
        unset($explode[$key]);
        unset($explode[$key+1]);
    }

}

$actualURI = implode("/", $explode);
$twig->addGlobal("actualURI", $actualURI);
$uriParams = Util::convertUriToParameters();
$twig->addGlobal("year", (isset($uriParams["year"]) ? $uriParams["year"] : date("Y")));
$twig->addGlobal("month", (isset($uriParams["month"]) ? $uriParams["month"] : date("m")));
// Twig globals
$twig->addGlobal("image_character", $imageServer."Character/");
$twig->addGlobal("image_corporation", $imageServer."Corporation/");
$twig->addGlobal("image_alliance", $imageServer."Alliance/");
$twig->addGlobal("image_item", $imageServer."Type/");
$twig->addGlobal("image_ship", $imageServer."Render/");

$twig->addGlobal("siteurl", $baseAddr);
$twig->addGlobal("fullsiteurl", $fullAddr);
$twig->addGlobal("requesturi", $_SERVER["REQUEST_URI"]);
$twig->addGlobal("topad", zKillboard::top());
$twig->addGlobal("bottomad", zKillboard::bottom());
$twig->addGlobal("mobiletopad", zKillboard::mobileTop());
$twig->addGlobal("mobilebottomad", zKillboard::mobileBottom());
$twig->addGlobal("igbtopad", zKillboard::igbTop());
$twig->addGlobal("igbbottomad", zKillboard::igbBottom());
$twig->addGlobal("analytics", zKillboard::analytics());
$twig->addGlobal("disqusLoad", $disqus);
if($disqus)
{
    $twig->addGlobal("disqusShortName", $disqusShortName);
    $twig->addglobal("disqus", Disqus::init());
}

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
$twig->addFunction("isMaintenance", new Twig_Function_Function("Util::isMaintenanceMode"));
$twig->addFunction("getMaintenanceReason", new Twig_Function_Function("Util::getMaintenanceReason"));

$igb = false;
if(stristr(@$_SERVER["HTTP_USER_AGENT"], "EVE-IGB"))
	$igb = true;
$twig->addGlobal("eveigb", $igb);
