<?php

//--------------------
//Open connection
//--------------------

require_once('credentials.php');

//--------------------
//Setting table vars
//--------------------

$regions_table = 'nms_regions';
$systems_table = 'nms_systems';

$planets_table = 'nms_planets';
$fauna_table = 'nms_fauna';
$flora_table = 'nms_flora';
$bases_table = 'nms_bases';

$ships_table = 'nms_ships';
$tools_table = 'nms_tools';

$articles_table = 'articles';

$biomes_table = 'db_planet_biome';
$weathers_table = 'db_planet_weather';
$life_levels_table = 'db_planet_life';
$sentinel_levels_table = 'db_planet_sentinels';
$resources_table = 'db_planet_resources';

$economy_type_table = 'db_system_economy_type';
$economy_wealth_table = 'db_system_economy_wealth';
$conflict_levels_table = 'db_system_conflict';
$lifeforms_table = 'db_system_lifeform';
$system_specials_table = 'db_system_special';

//--------------------
//Setting id_str vars
//--------------------

$region_id_str = 'region';
$system_id_str = 'system';
$planet_id_str = 'planet';
$fauna_id_str = 'fauna';
$flora_id_str = 'flora';
$base_id_str = 'base';
$ship_id_str = 'ship';
$tool_id_str = 'tool';

//define('ROOT_PATH', dirname(__FILE__));

//--------------------
//Helper functions
//--------------------

function prepared_query($mysqli, $sql, $params, $types = "")
{
	try{
		$types = $types ?: str_repeat("s", count($params));
		$stmt = $mysqli->prepare($sql);
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		return $stmt;
	} catch (Exception $ex){
		echo($ex);
		echo($mysqli->error);
		die();
	}
}

function get_item_by_uuid($mysqli, $uuid, $item_table, $bound_lower = TRUE)
{
	$sql = 'SELECT name FROM ' . $item_table . ' WHERE id=?';
	$params = [$uuid,];
	$stmt = prepared_query($mysqli, $sql, $params);
	
	$stmt->store_result();

	if ($bound_lower && $stmt->num_rows < 1)
	{
		//header("HTTP/1.1 500 Internal Server Error");
		echo('Error 500: Internal Server Error. <br><br> Item ID ' . $uuid . ' not found.');
		exit();
	} elseif ($stmt->num_rows > 1) {
		//header("HTTP/1.1 500 Internal Server Error");
		echo('Error 500: Internal Server Error. <br><br> Multiple matches for Item ID ' . $uuid . ' in table '.  $item_table .'.');
		exit();
	} 
	
	$stmt->bind_result($name);
	$stmt->fetch();	
    
    $stmt->close();
	
	return $name;
}

function get_item_by_name($mysqli, $name, $item_table, $bound_lower = TRUE, $bound_upper = TRUE)
{
	$sql = 'SELECT id FROM ' . $item_table . ' WHERE name=?';
	$params = [$name,];
	$stmt = prepared_query($mysqli, $sql, $params);
	
	$stmt->store_result();

	if ($bound_lower && $stmt->num_rows < 1)
	{
		//header("HTTP/1.1 500 Internal Server Error");
		echo('Error 500: Internal Server Error. <br><br> No matches for Item Name ' . $name . ' in Table ' .$item_table. '.');
		exit();
	} elseif ($bound_upper && $stmt->num_rows > 1) {
		//header("HTTP/1.1 500 Internal Server Error");
		echo('Error 500: Internal Server Error. <br><br> Multiple matches for Item Name.');
		exit();
	} 
	
	$stmt->bind_result($uuid);
	$stmt->fetch();	
    
    $stmt->close();
	
	return $uuid;
}

function find_or_insert_item($mysqli, $name, $item_table)
{
	$sql = 'SELECT id FROM ' . $item_table . ' WHERE name=?';
	$params = [$name,];
	$stmt = prepared_query($mysqli, $sql, $params);
	
	$stmt->store_result();

	if ($stmt->num_rows == 1) //found item, return id
	{
		$stmt->bind_result($uuid);
		$stmt->fetch();	
        $stmt->close();

		return $uuid;
	} elseif ($stmt->num_rows > 1) { //more than one match, error out
		//header("HTTP/1.1 500 Internal Server Error");
		echo('Error 500: Internal Server Error. <br><br> Multiple matches for Item Name.');
		exit();
	} else { //no match found, insert item
		//Add new item to DB
		$params =   [ $name, ];
		$sql = 'INSERT INTO ' . $item_table . ' (
		name
		) VALUES (?)';
		$types = 's';

		//Execute Query
		try{
			$stmt = prepared_query($mysqli, $sql, $params, $types);
            $insert_id = $stmt->insert_id;
            $stmt->close();
			return $insert_id;
		} catch (Exception $ex){
			//header("HTTP/1.1 500 Internal Server Error");
			echo($ex);
			echo($mysqli->error);
			die();
		}
	}	
}

//Returns an array of children in the format [child_uuid, child_name, child_type_string], e.g.
// ['53133ce6-4482-11ea-aa95-633f570ddae2', 'TestBase', 'base'], ['f3bca6ca-4485-11ea-bcde-b9b52edcaf81', 'Flower', 'fauna']
function get_system_children($mysqli, $system_id)
{
	$children_arr = [
		['table' => $GLOBALS['ships_table'], 'id_str' => $GLOBALS['ship_id_str']],
		['table' => $GLOBALS['tools_table'], 'id_str' => $GLOBALS['tool_id_str']],
	];
	
	$children = [];
	
	//Get all planets in system
	$planets = get_children($mysqli, $system_id, $GLOBALS['planets_table']);
	
	//Iterate through each planet...
	foreach ($planets as $planet)
	{
		//...add planet to system children...
		$children[] = ['uuid' => $planet['uuid'], 'name' => $planet['name'], 'id_str' => $GLOBALS['planet_id_str']];
		
		//...and add planet's children to system children.
		$children = array_merge($children, get_planet_children($mysqli, $planet['uuid']));
	}
	
	//Now get the immediate system children...
	$children = array_merge($children, get_children_by_arr($mysqli, $system_id, $children_arr));	
	
	return $children;
}

//Returns an array of children in the format [child_uuid, child_name, child_type_string], e.g.
// ['53133ce6-4482-11ea-aa95-633f570ddae2', 'TestBase', 'base'], ['f3bca6ca-4485-11ea-bcde-b9b52edcaf81', 'Flower', 'fauna']
function get_planet_children($mysqli, $planet_id)
{
	$children_arr = [
		['table' => $GLOBALS['bases_table'], 'id_str' => $GLOBALS['base_id_str']],
		['table' => $GLOBALS['fauna_table'], 'id_str' => $GLOBALS['fauna_id_str']],
		['table' => $GLOBALS['flora_table'], 'id_str' => $GLOBALS['flora_id_str']],
	];
	
	return get_children_by_arr($mysqli, $planet_id, $children_arr);
}

function get_children_by_arr($mysqli, $parent_id, $children_arr)
{
	$children = [];
	
	//Returns an array of children in the format [child_uuid, child_name, child_type_string], e.g.
	// ['53133ce6-4482-11ea-aa95-633f570ddae2', 'TestBase', 'base'], ['f3bca6ca-4485-11ea-bcde-b9b52edcaf81', 'Flower', 'fauna']
	foreach ($children_arr as $child_arr)
	{
		$child_ids_names = get_children($mysqli, $parent_id, $child_arr['table']);
		foreach ($child_ids_names as $child_id_name)
		{
			//uuid, name, type
			$children[] = ['uuid' => $child_id_name['uuid'], 
						   'name' => $child_id_name['name'], 
						   'id_str' => $child_arr['id_str']];
		}
	}
	
	return $children;
}

function get_children($mysqli, $parent_id, $children_table)
{
	$sql = 'SELECT id, name FROM ' . $children_table . ' WHERE parent_id=?';
	$params = [$parent_id,];
	$stmt = prepared_query($mysqli, $sql, $params);
	
	$stmt->store_result();
	
	$stmt->bind_result($uuid, $name);
	
	$children = [];
	while($stmt->fetch())
	{
		$child = [
			'uuid' => $uuid,
			'name' => $name,
		];
		
		$children[] = $child;
	}
	
    $stmt->close();
    
	return $children;
}

function get_articles_from_childlist($mysqli, $child_list, $articles_table)
{
	$articles = [];
	
	//child is comprised of [uuid, name, type_str]
	foreach ($child_list as $child)
	{
		$temp_articles = get_articles_for_uuid($mysqli, $child['uuid'], $articles_table);
		//echo($child['uuid'] . '<br>');
		
		foreach ($temp_articles as $article)
		{
			$article['source'] = (ucfirst($child['id_str']) . ' - ' . $child['name']);
			$article['source_url'] = 'item.php?uuid=' . $child['uuid'] . '&type=' . $child['id_str'];
			
			$bg_color = 'white';
			$fg_color = 'black';
			
			switch ($child['id_str'])
			{
				case $GLOBALS['system_id_str']:
					$bg_color = $GLOBALS['system_color'];
					$fg_color = $GLOBALS['system_header_text_color'];
					break;
					
				case $GLOBALS['planet_id_str']:
					$bg_color = $GLOBALS['planet_color'];
					$fg_color = $GLOBALS['planet_header_text_color'];
					break;
					
			}

			$article['header_style'] = 'background-color: ' . $bg_color . '; color: ' . $fg_color . ';';
			
			$articles[] = $article;
		}	
	}
	
	/*foreach ($articles as $article)
		{ echo($article['heading']); }*/
	
	return $articles;
}

function get_articles_for_uuid($mysqli, $parent_uuid, $articles_table)
{
	$sql = 'SELECT id, timestamp, author, heading, content FROM ' . $articles_table . ' WHERE parent=? ORDER BY timestamp DESC';
	$params = [$parent_uuid,];
	
	$stmt_articles = prepared_query($mysqli, $sql, $params);
	
	$stmt_articles->store_result();

	if ($stmt_articles->num_rows < 1)
	{
		//header("HTTP/1.1 500 Internal Server Error");
		//echo('No Articles found for parent UUID ' . $parent_uuid . ' .');
		return 0;
		//exit();
	}
	
	$stmt_articles->bind_result($uuid,
							   $timestamp,
							   $author,
							   $heading,
							   $content);
	
	$articles = [];
	
	while($stmt_articles->fetch())
	{
		$article = [
			'uuid' => $uuid,
			'timestamp' => $timestamp,
			'author' => $author,
			'heading' => $heading,
			'content' => $content,
		];
		
		$articles[] = $article;
	}
    
    $stmt_articles->close();
	
	return $articles;
}

function get_id_and_name($mysqli, $table_name)
{
	$sql = 'SELECT id, name FROM ' . $table_name . ' ORDER BY name DESC';
	
	$results = $mysqli->query($sql);
	
	if ($results->num_rows > 0) {
		return ($results->fetch_all());
	}

}


?>