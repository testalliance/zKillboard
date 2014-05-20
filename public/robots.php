<?php

$serverName = $_SERVER["SERVER_NAME"];

// Don't index on subdomains
$disallow = (($serverName == "zkillboard.com") ? "Disallow: /api/" : "Disallow: *");

header("Content-type: text/plain");
echo "User-agent: *\n";
echo "$disallow\n";
echo "\nSitemap: https://zkillboard.com/sitemaps/sitemaps.xml\n";
