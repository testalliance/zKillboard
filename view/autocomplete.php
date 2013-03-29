<?php
//get the query value
if ($app->request()->isPost())
{
	$query = $app->request()->post('query');
}

//declare the base data/sql etc		
$entities = array(
	array('type' => 'faction',     'query' => 'SELECT factionID AS id, name FROM zz_factions WHERE name LIKE :query LIMIT 9',                                          'image' => 'Alliance/%1$d_32.png'),
	array('type' => 'alliance',    'query' => 'SELECT allianceID AS id, name FROM zz_alliances WHERE name LIKE :query OR ticker LIKE :query LIMIT 9',                  'image' => 'Alliance/%1$d_32.png'),
	array('type' => 'corporation', 'query' => 'SELECT corporationID AS id, name FROM zz_corporations WHERE name LIKE :query OR ticker LIKE :query LIMIT 9',            'image' => 'Corporation/%1$d_32.png'),
	array('type' => 'character',   'query' => 'SELECT characterID AS id, name FROM zz_characters WHERE name LIKE :query LIMIT 9',                                      'image' => 'Character/%1$d_32.jpg'),
	array('type' => 'item',        'query' => 'SELECT typeID AS id, typeName AS name, groupID FROM ccp_invTypes WHERE published = 1 AND typeName LIKE :query LIMIT 9', 'image' => 'Type/%1$d_32.png'),
	array('type' => 'system',      'query' => 'SELECT solarSystemID  AS id, solarSystemName AS name FROM ccp_systems WHERE solarSystemName LIKE :query LIMIT 9',       'image' => ''),
	array('type' => 'region',      'query' => 'SELECT regionID AS id, regionName AS name FROM ccp_regions WHERE regionName LIKE :query LIMIT 9',                       'image' => ''),
);

//define our array for the results
$search_results = array();

//for each entity type, get any matches and process them
foreach ($entities as $entity)
{
	//see if we have any things that matches the thing
	$results = Db::query($entity['query'], array(":query" => $query . '%'), 30);

	//merge the reults into an single array to throw back to the browser
	foreach ($results as $result)
	{	
		$search_results[] = array_merge($result, array('type' => $entity['type'], 'image' => sprintf($entity['image'], $result['id'])));
	}
}

//declare out json return type
$app->contentType('application/json; charset=utf-8');

//if we have some results then sort them
if (count($search_results) > 0)
{
	//get the values of the column we intend to sort by
	foreach ($search_results as $key => $row) { $sort_by[$key] = $row['name']; }

	//perfom and return the sorted array
	array_multisort($sort_by, SORT_ASC, SORT_NATURAL, $search_results);	
}

//return the top 15 results as a json object
echo json_encode(array_slice($search_results, 0, 15));