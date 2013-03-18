<?php
require_once "init.php";

ob_start();

if (strlen($subDomain) > 1) $baseUrl = str_replace("http://", "http://$subDomain.", $baseUrl);
$timer = new Timer();

$pageName = sizeof($p) > 0 ? strtolower($p[0]) : "home";
$page = new $pageName();
$page->initialize();

$page->callControllers();
ob_end_clean();

$types = array("Title", "Meta", "Header", "Menu", "LeftPane", "MidPane", "RightPane", "Footer");
$views = array();
foreach($types as $type) {
	$functionName = "view{$type}";
	$$type = $page->$functionName(null);
}

$queryCount = Db::getQueryCount();
$elapsed = (int)$timer->stop();


echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
        'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
      <html xmlns='http://www.w3.org/1999/xhtml' lang='en' xml:lang='en'>
<head>
	<meta http-equiv='content-type' content='text/html; charset=UTF-8' />
	<title>$Title</title>
$Meta
</head>
<body>
<table id='mainTable'>
	<tr><td id='header' colspan='3'>$Header</td></tr>
	<tr><td id='menu' colspan='3'>$Menu</td></tr>
	<tr>
		<td id='leftPane'>
$LeftPane
		</td>
		<td id='middlePane'>
$MidPane
		</td>
		<td id='rightPane'>
$RightPane
		</td>
	</tr>
	<tr><td id='footer'>$Footer {$elapsed}ms/{$queryCount}q</td></tr>
</table>
</body>
</html>";
