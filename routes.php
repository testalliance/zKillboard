<?php
$app->notFound(function () use ($app) {
    $app->render('404.html');
});

// Default route
$app->get("/", function () use ($app){
    include( "view/index.php" );
});

//  information about zKillboard
$app->get("/information/", function() use ($app) {
    include( "view/information.php" );
});
$app->get("/information/:page/", function($page) use ($app) {
    include( "view/information.php" );
});

// Tracker
$app->get("/tracker/", function() use ($app) {
    include( "view/tracker.php" );
});

// View kills
$app->get("/kills/", function() use ($app) {
    include( "view/kills.php" );
});
$app->get("/kills/:type/", function($type) use ($app) {
    include( "view/kills.php" );
});

// View related kills
$app->get("/related/:system/:time/", function($system, $time) use ($app) {
    include( "view/related.php" );
});
// View related kills
$app->get("/r2/:system/:time/", function($system, $time) use ($app) {
    include( "view/r2.php" );
});

// View top
$app->get("/top/lasthour/", function() use ($app) {
    include( "view/lasthour.php" );
});
$app->get("/ranks/:pageType/:subType/", function($pageType, $subType) use ($app) {
    include( "view/ranks.php" );
});
$app->get("/top/", function() use ($app) {
    include( "view/top.php" );
});
$app->get("/top/:type/", function($type) use ($app) {
    include( "view/top.php" );
});
$app->get("/top/:type/:page/(:time+/)", function($type, $page, $time = array()) use ($app) {
    include( "view/top.php" );
});

// Raw Kill Detail
$app->get("/raw/:id/", function($id) use ($app) {
        include( "view/raw.php" );
});

// Kill Detail View
$app->get("/detail/:id/", function($id) use ($app) {
    include( "view/detail.php" );
});
$app->get("/detail/:id/:pageview/", function($id, $pageview) use ($app) {
    include( "view/detail.php" );
});
$app->post("/detail/:id/:pageview/", function($id, $pageview) use ($app) {
    include( "view/detail.php" );
});

// Search
$app->get("/search/:search/", function($search) use ($app) {
    include( "view/search.php" );
});
$app->post("/search/", function() use ($app) {
    include( "view/search.php" );
});

// Login stuff
$app->get("/login/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/login.php" );
});
$app->post("/login/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/login.php" );
});

// Logout
$app->get("/logout/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/logout.php" );
});

// Forgot password
$app->get("/forgotpassword/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/forgotpassword.php" );
});
$app->post("/forgotpassword/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/forgotpassword.php" );
});

// Change password
$app->get("/changepassword/:hash", function($hash) use ($app) {
    include( "view/changepassword.php" );
});

// Change password
$app->post("/changepassword/:hash", function($hash) use ($app) {
    include( "view/changepassword.php" );
});

// Register
$app->get("/register/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/register.php" );
});
$app->post("/register/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/register.php" );
});

// Account
$app->get("/account/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/account.php" );
});
$app->post("/account/", function() use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/account.php" );
});
$app->get("/account/:req/", function($req) use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/account.php" );
});
$app->post("/account/:req/", function($req) use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/account.php" );
});

// Moderator

// Admin
$app->get("/admin/", function () use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/admin.php" );
});
$app->post("/admin/", function () use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/admin.php" );
});
$app->get("/admin/:req/", function ($req) use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/admin.php" );
});
$app->post("/admin/:req/", function ($req) use ($app) {
    global $cookie_name, $cookie_time;
    include( "view/admin.php" );
});

// EveInfo
$app->get("/item/:id/", function($id) use ($app) {
    global $oracleURL;
    include ("view/item.php" );
});

// Give list of character id's to evewho
$app->get("/evewhoc/", function() use ($app) { include("view/evewhoc.php");});

// StackTrace
$app->get("/stacktrace/:hash/", function($hash) use ($app) {
    $q = Db::query("SELECT error, url FROM zz_errors WHERE id = :hash", array(":hash" => $hash));
	$trace = $q[0]["error"];
	$url = $q[0]["url"];
    $app->render("/components/stacktrace.html", array("stacktrace" => $trace, "url" => $url));
});

// API
$app->get("/api/stats/:type/:id/(:return_method)/", function($type, $id, $return_method = 'json') use ($app) {
    include( "view/apistats.php" );
});

$app->get("/api/:input+", function($input) use ($app) {
    include( "view/api.php" );
});

// RSS
$app->get("/chart/:chartID/", function($chartID) use ($app) {
    include( "view/chart.php" );
});

// Kills in the last hour
$app->get("/killslasthour/", function() use ($app) {
	die("-9,000,000");
    echo number_format(Storage::retrieve("KillsLastHour", null));
});

// Post
$app->get("/post/", function() use ($app) {
	include( "view/postmail.php" );
});
$app->post("/post/", function() use ($app) {
	include( "view/postmail.php" );
});

// Revoke
$app->get("/revoke/", function() use ($app) {
	$app->render("revoked_reason.html");
});

// Autocomplete
$app->post("/autocomplete/", function() use ($app) {
	include( "view/autocomplete.php" );
});

// EVE-KILL kill_detail intercept
$app->get("/evekilldetailintercept/:id/", function($id) use ($app) {
	include( "view/evekilldetailintercept.php" );
});

// EVE-KILL kill_related intercept
$app->get("/evekillrelatedintercept/:id/", function($id) use ($app) {
	include( "view/evekillrelatedintercept.php" );
});

// The Overview stuff
$app->get("/:input+/", function($input) use ($app) {
	include("view/overview.php");
});

