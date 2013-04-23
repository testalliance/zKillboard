<?php

switch($type)
{
	case "all": // All campaigns.
		$data = Campaigns::getAllCampaigns();
	break;
}

$app->render("campaigns.html", array("data" => $data));