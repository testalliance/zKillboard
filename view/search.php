<?php

$entities = array();

if($_POST)
    $search = $_POST["searchbox"];
   
if($search)
{
	$entities = Info::findEntity($search);

	// if there is only one result, we redirect.
	if(count($entities) == 1) {
		$type = $entities[0]["type"];
		$values = array_values($entities[0]);
		$id = $values[0];
		$app->redirect("/$type/$id/");
	}
}

$app->render("search.html", array("data" => $entities));
