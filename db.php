<?php

//--------------------
//Open connection
//--------------------

require_once('credentials.php');

//--------------------
//Set locale
//--------------------

//setlocale(LC_ALL, Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']));

//--------------------
//Setting table vars
//--------------------

$regions_table = 'nms_regions';
$systems_table = 'nms_systems';

$planets_table = 'nms_planets';
$fauna_table = 'nms_fauna';
$flora_table = 'nms_flora';
$bases_table = 'nms_bases';
$pois_table = 'nms_pois';

$ships_table = 'nms_ships';
$tools_table = 'nms_tools';

$resources_table = 'nms_resources';

$articles_table = 'articles';

$biomes_table = 'db_planet_biome';
$weathers_table = 'db_planet_weather';
$life_levels_table = 'db_planet_life';
$sentinel_levels_table = 'db_planet_sentinels';

$economy_type_table = 'db_system_economy_type';
$economy_wealth_table = 'db_system_economy_wealth';
$conflict_levels_table = 'db_system_conflict';
$lifeforms_table = 'db_system_lifeform';
$system_specials_table = 'db_system_special';

$fauna_activities_table = 'db_fauna_activity';
$fauna_ages_table = 'db_fauna_age';
$fauna_baits_table = 'db_fauna_bait';
$fauna_behaviours_table = 'db_fauna_behaviour';
$fauna_diets_table = 'db_fauna_diet';
$fauna_ecosystems_table = 'db_fauna_ecosystem';
$fauna_genders_table = 'db_fauna_gender';
$fauna_notes_table = 'db_fauna_notes';

$flora_ages_table = 'db_flora_age';
$flora_roots_table = 'db_flora_roots';
$flora_food_table = 'db_flora_food';
$flora_notes_table = 'db_flora_notes';

$ship_types_table = 'db_ship_types';

$poi_types_table = 'db_poi_types';

$base_facilities_table = 'db_base_facilities';

//--------------------
//Setting id_str vars
//--------------------

$region_id_str = 'region';
$system_id_str = 'system';
$planet_id_str = 'planet';
$fauna_id_str = 'fauna';
$flora_id_str = 'flora';
$base_id_str = 'base';
$poi_id_str = 'poi';
$ship_id_str = 'ship';
$tool_id_str = 'tool';

//define('ROOT_PATH', dirname(__FILE__));

//--------------------
//Get color vars as globals
//--------------------

require_once('./style/color_vars.php');

//--------------------
//Setup Regex
//--------------------

$lightbox_re = '/<img src="([^_]*)_header(\.[^"]*)" \/>/m';
$lightbox_replacement = '<a href="$1_full$2" class="glightbox"><img src="$1_header$2" /></a>';

$linkbox_re = '/(?><a href="item\.php\?uuid=)(?<uuid>[[:xdigit:]-]*)(?>[^=]*=)(?<type>[^"]*)(?>">)(?<caption>[^<]*)(?><\/a>)/m';

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
        echo('<br><br>');
        echo($params);
        echo($sql);
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
            echo('<br><br>');
            echo('Trying to fetch or insert "' . $name . '" with table "' . $item_table . '.');
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
        ['table' => $GLOBALS['pois_table'], 'id_str' => $GLOBALS['poi_id_str']],
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

        if ($temp_articles != 0)
        {
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

                    case $GLOBALS['fauna_id_str']:
                        $bg_color = $GLOBALS['fauna_color'];
                        $fg_color = $GLOBALS['fauna_header_text_color'];
                        break;

                    case $GLOBALS['flora_id_str']:
                        $bg_color = $GLOBALS['flora_color'];
                        $fg_color = $GLOBALS['flora_header_text_color'];
                        break;

                    case $GLOBALS['ship_id_str']:
                        $bg_color = $GLOBALS['ship_color'];
                        $fg_color = $GLOBALS['ship_header_text_color'];
                        break;

                    case $GLOBALS['poi_id_str']:
                        $bg_color = $GLOBALS['poi_color'];
                        $fg_color = $GLOBALS['poi_header_text_color'];
                        break;

                    case $GLOBALS['base_id_str']:
                        $bg_color = $GLOBALS['base_color'];
                        $fg_color = $GLOBALS['base_header_text_color'];
                        break;

                }

                $article['header_style'] = 'background-color: ' . $bg_color . '; color: ' . $fg_color . ';';

                $articles[] = $article;
            }
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
			//'timestamp_format' => date('d.m.Y', strtotime($timestamp)),
            'timestamp_format' => strftime('%d. %B %Y', strtotime($timestamp)),
            'timestamp' => $timestamp,
			'author' => $author,
			'heading' => $heading,
            //'content' => $content,
			'content' => add_lightbox($content),
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

function star_to_portal($star_coords_str)
{
    $star_coords = str_replace(':', '', $star_coords_str);
    $x = hexdec(substr($star_coords, 0, 4));
    $y = hexdec(substr($star_coords, 4, 4));
    $z = hexdec(substr($star_coords, 8, 4));
    $ssi = hexdec(substr($star_coords, 13, 3));
    $portal_number = dechex(0);

    $x = $x + hexdec('801');
    $y = $y + hexdec('81');
    $z = $z + hexdec('801');
    
    $x = $x % hexdec('1000');
    $y = $y % hexdec('100');
    $z = $z % hexdec('1000');
    
    $portal_coord = $portal_number . str_pad(dechex($ssi), 3, '0', STR_PAD_LEFT) . str_pad(dechex($y), 2, '0', STR_PAD_LEFT) .  str_pad(dechex($z), 3, '0', STR_PAD_LEFT) . str_pad(dechex($x), 3, '0', STR_PAD_LEFT);
    
    return strtoupper($portal_coord);
}

function number_format_locale($number,$decimals=2) {
    $locale = localeconv();
    return number_format($number,$decimals,
               $locale['decimal_point'],
               $locale['thousands_sep']);
 }


function add_lightbox($content)
{
    return preg_replace($GLOBALS['lightbox_re'], $GLOBALS['lightbox_replacement'], $content);
}

function process_child($child, $conn)
{
    $child_card = [];
    $child_card['name'] = str_replace('bilwii', 'b.', $child['name']);
    $child_card['type'] = $child['id_str'];
    $child_card['url'] = 'item.php?uuid=' . $child['uuid'] . '&type=' . $child['id_str'];

    //Required info: name, url, type, thumb, biome, resource_str, discoverer, discovery_date
    switch ($child['id_str'])
    {
        //Child is a fauna item, fetch ecosystem, activity, diet
        //+ discovery and discovery date 
        case $GLOBALS['fauna_id_str']:
            $sql = 'SELECT  
            ecosystem, activity, diet, discovery_date, discoverer, screenshot
            FROM ' . $GLOBALS['fauna_table'] . ' WHERE id = ?';
            $params = [$child['uuid'], ];

            $stmt = prepared_query($conn, $sql, $params);
            $stmt->store_result();

            $stmt->bind_result($child_ecosystem_id,
                               $child_activity_id,
                               $child_diet_id,
                               $child_discovery_date,
                               $child_discoverer,
                               $child_screenshot);

            $stmt->fetch();

            //Add text for table items to card             
            $child_card['ecosystem'] = get_item_by_uuid($conn, $child_ecosystem_id, $GLOBALS['fauna_ecosystems_table']);
            $child_card['activity'] = get_item_by_uuid($conn, $child_activity_id, $GLOBALS['fauna_activities_table']);
            $child_card['diet'] = get_item_by_uuid($conn, $child_diet_id, $GLOBALS['fauna_diets_table']);

            //Add header style
            $child_card['header_style'] = 'background-color: ' . $GLOBALS['fauna_color'] . '; color: ' . $GLOBALS['fauna_header_text_color'] . ';';

            break;
        
        //Child is a flora item, fetch ecosystem, activity, diet
        //+ discovery and discovery date 
        case $GLOBALS['flora_id_str']:
            $sql = 'SELECT  
            age, roots, food, primary_element, secondary_element, discovery_date, discoverer, screenshot
            FROM ' . $GLOBALS['flora_table'] . ' WHERE id = ?';
            $params = [$child['uuid'], ];

            $stmt = prepared_query($conn, $sql, $params);
            $stmt->store_result();

            $stmt->bind_result($child_age_id,
                               $child_roots_id,
                               $child_food_id,
                               $child_prim_resource_id,
                               $child_sec_resource_id,
                               $child_discovery_date,
                               $child_discoverer,
                               $child_screenshot);

            $stmt->fetch();

            //Add text for table items to card             
            $child_card['age'] = get_item_by_uuid($conn, $child_age_id, $GLOBALS['flora_ages_table']);
            $child_card['roots'] = get_item_by_uuid($conn, $child_roots_id, $GLOBALS['flora_roots_table']);
            $child_card['food'] = get_item_by_uuid($conn, $child_food_id, $GLOBALS['flora_food_table']);
            $child_card['primary_resource'] = get_item_by_uuid($conn, $child_prim_resource_id, $GLOBALS['resources_table']);
            $child_card['secondary_resource'] = get_item_by_uuid($conn, $child_sec_resource_id, $GLOBALS['resources_table']);

            if ($child_card['primary_resource'] == '')
            {
                $child_card['primary_resource'] = ' - ';
            }

            if ($child_card['secondary_resource'] == '')
            {
                $child_card['secondary_resource'] = ' - ';
            }

            //Add header style
            $child_card['header_style'] = 'background-color: ' . $GLOBALS['flora_color'] . '; color: ' . $GLOBALS['flora_header_text_color'] . ';';

            break;

        //Child is a PoI, fetch type, long + lat
        //+ discovery and discovery date 
        case $GLOBALS['poi_id_str']:
            $sql = 'SELECT  
            type, planet_lat, planet_long, discovery_date, discoverer, screenshot
            FROM ' . $GLOBALS['pois_table'] . ' WHERE id = ?';
            $params = [$child['uuid'], ];

            $stmt = prepared_query($conn, $sql, $params);
            $stmt->store_result();

            $stmt->bind_result($child_type_id,
                               $child_lat,
                               $child_long,
                               $child_discovery_date,
                               $child_discoverer,
                               $child_screenshot);

            $stmt->fetch();

            //Add text for table items to card             
            $child_card['poi_type'] = get_item_by_uuid($conn, $child_type_id, $GLOBALS['poi_types_table']);
            $child_card['lat'] = $child_lat;
            $child_card['long'] = $child_long;

            $child_card['type'] = 'PoI';

            //Add header style
            $child_card['header_style'] = 'background-color: ' . $GLOBALS['poi_color'] . '; color: ' . $GLOBALS['poi_header_text_color'] . ';';

            break;

        //Child is a Base, fetch facilities, long + lat
        //+ discovery and discovery date    
        case $GLOBALS['base_id_str']:
            $sql = 'SELECT  
            facilities, planet_lat, planet_long, discovery_date, discoverer, screenshot
            FROM ' . $GLOBALS['bases_table'] . ' WHERE id = ?';
            $params = [$child['uuid'], ];

            $stmt = prepared_query($conn, $sql, $params);
            $stmt->store_result();

            $stmt->bind_result($child_facilities_ids_str,
                               $child_lat,
                               $child_long,
                               $child_discovery_date,
                               $child_discoverer,
                               $child_screenshot);

            $stmt->fetch();

            //Add text for table items to card             
            $child_card['lat'] = $child_lat;
            $child_card['long'] = $child_long;

            //Get text for resources
            $child_facilities_ids = explode(',', $child_facilities_ids_str);
            $child_facilities = [];

            foreach($child_facilities_ids as $child_facilities_id)
            {
                $child_facilities[] = get_item_by_uuid($conn, $child_facilities_id, $GLOBALS['base_facilities_table']);
            }

            //Add resources to card
            $child_card['facilities'] = implode(', ', $child_facilities);

            //Add header style
            $child_card['header_style'] = 'background-color: ' . $GLOBALS['base_color'] . '; color: ' . $GLOBALS['base_header_text_color'] . ';';

            break;
            
        //Child is a planet, fetch biome and resource information in addition
        //to the discoverer and discovery date 
        case $GLOBALS['planet_id_str']:
            $sql = 'SELECT  
            biome, resources, discovery_date, discoverer, screenshot, moon
            FROM ' . $GLOBALS['planets_table'] . ' WHERE id = ?';
            $params = [$child['uuid'], ];

            $stmt = prepared_query($conn, $sql, $params);
            $stmt->store_result();

            $stmt->bind_result($child_biome_id,
                               $child_resource_ids_str,
                               $child_discovery_date,
                               $child_discoverer,
                               $child_screenshot,
                               $child_moon);

            $stmt->fetch();

            if ($child_moon)
            {
                $child_card['type'] = 'moon';
            }

            //Add text for biome to card             
            $child_card['biome'] = get_item_by_uuid($conn, $child_biome_id, $GLOBALS['biomes_table']);

            //Get text for resources
            $child_resource_ids = explode(',', $child_resource_ids_str);
            $child_resources = [];

            foreach($child_resource_ids as $child_resource_id)
            {
                $child_resources[] = get_item_by_uuid($conn, $child_resource_id, $GLOBALS['resources_table']);
            }

            $child_card['header_style'] = 'background-color: ' . $GLOBALS['planet_color'] . '; color: ' . $GLOBALS['planet_header_text_color'] . ';';

            //Add resources to card
            $child_card['resources'] = implode(', ', $child_resources);

            break;

        //Child is a ship, fetch type, inventory and price in addition
        //to the discoverer and discovery date 
        case $GLOBALS['ship_id_str']:
            $sql = 'SELECT  
            type, inventory, price, discovery_date, discoverer, screenshot
            FROM ' . $GLOBALS['ships_table'] . ' WHERE id = ?';
            $params = [$child['uuid'], ];

            $stmt = prepared_query($conn, $sql, $params);
            $stmt->store_result();

            $stmt->bind_result($child_type_id,
                               $child_inventory,
                               $child_price,
                               $child_discovery_date,
                               $child_discoverer,
                               $child_screenshot);

            $stmt->fetch();

            //Add text for type to card             
            $child_card['ship_type'] = get_item_by_uuid($conn, $child_type_id, $GLOBALS['ship_types_table']);

            //Add price and inventory to card
            $child_card['inventory'] = $child_inventory;
            $child_card['price'] = number_format_locale($child_price, 0) . ' U';


            //Add header style
            $child_card['header_style'] = 'background-color: ' . $GLOBALS['ship_color'] . '; color: ' . $GLOBALS['ship_header_text_color'] . ';';

            break;
            
        default:
            return '';
            
    }

    //Add discovery info to card
    $child_card['discovery_date'] = strftime('%x', strtotime($child_discovery_date));
    $child_card['discoverer'] = $child_discoverer;

    //Get thumbnail
    $child_card['thumb'] = str_replace('header', 'thumb', $child_screenshot);
    
    return $child_card;
}

/*
//TODO: Produce child cards to hover on link, preferably using templates

function add_linkbox($content)
{

    
    return preg_replace_callback($GLOBALS['linkbox_re'], 'link_card_creator', $content);
}

function link_card_creator($matches)
{
    $uuid = $matches['uuid'];
    $type = $matches['type'];
    $caption = $matches['caption'];
    
    $return_str = '<div class="linkbox>
        <a href="item.php?uuid='.$uuid.'&type='.$type.">.$caption.</a>
        <span ;
    
}

*/

?>