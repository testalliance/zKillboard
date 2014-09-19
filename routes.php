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

$app->notFound(function () use ($app) {
    $app->redirect("..", 302);
});

// Default route
$app->get("/(page/:page/)", function ($page = 1) use ($app){
    include( "view/index.php" );
});

$app->get("/kills.html/", function($page = "about") use ($app) {
die("<script type='text/javascript'>location.reload();</script>");
});

//  Information about zKillboard
$app->get("/information/(:page/)(:subPage/)", function($page = "about", $subPage = null) use ($app) {
    include( "view/information.php" );
});

/*
// Support
$app->get("/livechat/", function() use ($app) {
	include( "view/livechat.php" );
});

// Tickets
$app->map("/tickets/", function() use ($app) {
	include( "view/tickets.php" );
})->via("GET", "POST");

$app->map("/tickets/view/:id/", function($id) use ($app) {
	include( "view/tickets_view.php" );
})->via("GET", "POST");
*/

// Campaigns
$app->map("/campaign/:uri/", function($uri) use($app) {
    include( "view/campaign.php" );
})->via("GET");

// Tracker
$app->get("/tracker(/page/:page)/", function($page = 1) use ($app) {
    include( "view/tracker.php" );
});

// View kills
$app->get("/kills/page/:page/", function($page = 1) use ($app) {
    $type = NULL;
    include( "view/kills.php" );
});
$app->get("/kills(/:type)(/page/:page)/", function($type = NULL, $page = 1) use ($app) {
    include( "view/kills.php" );
});

// View related kills
$app->get("/related/:system/:time/(o/:options/)", function($system, $time, $options = "") use ($app) {
    include( "view/related.php" );
});

// View Battle Report
$app->get("/br/:battleID/", function($battleID) use ($app) {
    include( "view/battle_report.php" );
});

// View Battle Report
$app->get("/brsave/", function() use ($app) {
    include( "view/brsave.php" );
});

// View top
$app->get("/top/lasthour/", function() use ($app) {
    include( "view/lasthour.php" );
});
$app->get("/ranks/:pageType/:subType/", function($pageType, $subType) use ($app) {
    include( "view/ranks.php" );
});
$app->get("/top(/:type)(/:page)(/:time+)/", function($type = "weekly", $page = NULL, $time = array()) use ($app) {
    include( "view/top.php" );
});

// Raw Kill Detail
$app->get("/raw/:id/", function($id) use ($app) {
        include( "view/raw.php" );
});

// Kill Detail View
$app->get("/detail/:id(/:pageview)/", function($id, $pageview = "overview") use ($app) {
    $app->redirect("/kill/$id/", 301); // Permanent redirect
    die();
});
$app->get("/kill/:id(/:pageview)/", function($id, $pageview = "overview") use ($app) {
    include( "view/detail.php" );
})->via("GET", "POST");

// Search
$app->map("/search(/:search)/", function($search = NULL) use ($app) {
    include( "view/search.php" );
})->via("GET", "POST");

// Login stuff
$app->map("/dlogin/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/dlogin.php" );
})->via("GET", "POST");

$app->map("/login/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/login.php" );
})->via("GET", "POST");

// Sitemap
$app->get("/sitemap/", function() use ($app) {
    global $cookie_name, $cookie_time, $baseAddr;
    include( "view/sitemap.php" );
});

// Logout
$app->get("/logout/", function() use ($app) {
    global $cookie_name, $cookie_time, $baseAddr;
    include( "view/logout.php" );
});

// Forgot password
$app->map("/forgotpassword/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/forgotpassword.php" );
})->via("GET", "POST");

// Change password
$app->map("/changepassword/:hash/", function($hash) use ($app) {
    include( "view/changepassword.php" );
})->via("GET", "POST");

// Register
/*$app->map("/register/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/register.php" );
})->via("GET", "POST");
*/

// Account
$app->map("/account(/:req)(/:reqid)/", function($req = NULL, $reqid = NULL) use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/account.php" );
})->via("GET", "POST");

// Moderator
$app->map("/moderator(/:req)(/:id)(/page/:page)/", function ($req = NULL, $id = NULL, $page = 1) use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/moderator.php" );
})->via("GET", "POST");

// EveInfo
$app->get("/item/:id/", function($id) use ($app) {
    global $oracleURL;
    include ("view/item.php" );
});

// StackTrace
$app->get("/stacktrace/:hash/", function($hash) use ($app) {
    $q = Db::query("SELECT error, url FROM zz_errors WHERE id = :hash", array(":hash" => $hash));
	$trace = $q[0]["error"];
	$url = $q[0]["url"];
    $app->render("/components/stacktrace.html", array("stacktrace" => $trace, "url" => $url));
});

/*
$app->get("/comments/", function() use ($app) {
    $app->render("/comments.html");
});
*/

// API
$app->get("/api/stats/:flags+/", function($flags) use ($app) {
    include( "view/apistats.php" );
});

$app->get("/api/dna(/:flags+)/", function($flags = null) use ($app) {
    include( "view/apidna.php" );
});

$app->get("/api/:input+", function($input) use ($app) {
    include( "view/api.php" );
});

// Kills in the last hour
$app->get("/killslasthour/", function() use ($app) {
die("<script type='text/javascript'>location.reload();</script>");
    die(number_format(Storage::retrieve("KillsLastHour", null)));
});

// Post
$app->get("/post/", function() use ($app) {
	include( "view/postmail.php" );
});
$app->post("/post/", function() use ($app) {
	include( "view/postmail.php" );
});

// Autocomplete
$app->map("/autocomplete/", function() use ($app) {
	include( "view/autocomplete.php" );
})->via("POST");

// Intel
$app->get("/intel/supers/", function() use ($app) {
	include( "view/intel.php" );
});

// primer
$app->get("/primer/", function() use ($app) {
	include("view/primer.php");
});

// Sharing Crest Mails
$app->get("/crestmail/:killID/:hash/", function($killID, $hash) use ($app) {
	include("view/crestmail.php");
});

/*
// War!
$app->get("/war/:warID/", function($warID) use ($app) {
	include("view/war.php");
});
$app->get("/wars/", function() use ($app) {
	include("view/wars.php");
});
*/

// The Overview stuff
$app->get("/:input+/", function($input) use ($app) {
	include("view/overview.php");
});
