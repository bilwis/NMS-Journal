<?php
	//--------------------
	//Security Setup
	//--------------------
	
	if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/item.php?uuid=' . $_GET['uuid'] . '&type=planet');
        
        /*
		header("HTTP/1.1 403 Forbidden.");
		echo('Error 403: Forbidden. <br> <br> Direct File Access Prohibited.');
		exit();
        */
	}

	//--------------------
	//Options
	//--------------------

    $child_types_to_display = [$fauna_id_str, $flora_id_str, $poi_id_str];
	
	//--------------------
	//Get planet data
	//--------------------

	//Check wheter Planet with given UUID exists
	$sql = 'SELECT 
	id, parent_id, name, biome, weather,
	sentinel_level, flora_level, fauna_level, resources, 
	discovery_date, discoverer, screenshot, glyph_code, moon
	FROM ' . $planets_table . ' WHERE id = ?';
	$params = [$uuid, ];

	$stmt = prepared_query($conn, $sql, $params);
	$stmt->store_result();

	if ($stmt->num_rows < 1)
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> System UUID does not exist.');
		exit();
	} elseif ($stmt->num_rows > 1) {
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> Multiple matches for UUID.');
		exit();
	} 

	//If yes, bind results

	$stmt->bind_result($uuid,
					  $system_id,
					  $name,
					  $biome_id,
					  $weather_id,
					  $sentinel_level_id,
					  $flora_level_id,
					  $fauna_level_id,
					  $resource_ids_str,
					  $discovery_date,
					  $discoverer,
					  $screenshot,
					  $glyph_code,
                      $moon);

	$stmt->fetch();

	$system_name = get_item_by_uuid($conn, $system_id, $systems_table);

	//--------------------
	//Get text for biome/weather/life/sentinel ids
	//--------------------

	//Create array with references to the variables,
	//to replace with names for planet info display
	$check_array = [
		[&$biome, $biome_id, $biomes_table],
		[&$weather, $weather_id, $weathers_table],
		[&$sentinel_level, $sentinel_level_id, $sentinel_levels_table],
		[&$fauna_level, $fauna_level_id, $life_levels_table],
		[&$flora_level, $flora_level_id, $life_levels_table],
	];
	
	//Loop through each paired item_id & item_table, looking up 
	//the item from the item_table and changing the REFERENCED item variable to the name
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_uuid($conn, $arr[1], $arr[2]);
	}

	//--------------------
	//Get text for resources
	//--------------------

	$resource_ids = explode(',', $resource_ids_str);
	$resources = [];

	foreach($resource_ids as $resource_id)
	{
		$resources[] = get_item_by_uuid($conn, $resource_id, $resources_table);
	}

	$resources_str = implode(', ', $resources);

	//--------------------
	//Get articles for planet and its children
	//--------------------
	
	$children = get_planet_children($conn, $uuid);
	$articles = get_articles_from_childlist($conn, $children, $articles_table);

	$planet_articles =  get_articles_for_uuid($conn, $uuid, $articles_table);

	if (!$planet_articles == 0)
	{
		foreach($planet_articles as $article) {
			$article['source'] = 'Planet - ' . $name;
			$article['source_url'] = '#';
			$article['header_style'] = 'background-color: ' . $planet_color . '; color: ' . $planet_header_text_color . ';';
			
			$articles[] = $article; 
		}
	}

    //Sort articles by timestamp, last to first
    usort($articles, function($a, $b) {return $b['timestamp'] <=> $a['timestamp'];});

    //--------------------
	//Prepare child cards
	//--------------------

    $child_cards = [];

    foreach ($children as $child)
    {
        if (in_array($child['id_str'], $child_types_to_display))
        {
            $child_card['name'] = $child['name'];
            $child_card['type'] = $child['id_str'];
            $child_card['url'] = 'item.php?uuid=' . $child['uuid'] . '&type=' . $child['id_str'];
            
            //Required info: name, url, type, thumb, biome, resource_str, discoverer, discovery_date
            switch ($child['id_str'])
            {

                //Child is a fauna item, fetch ecosystem, activity, diet
                //+ discovery and discovery date 
                case $fauna_id_str:
                    $sql = 'SELECT  
                    ecosystem, activity, diet, discovery_date, discoverer, screenshot
                    FROM ' . $fauna_table . ' WHERE id = ?';
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
                    $child_card['ecosystem'] = get_item_by_uuid($conn, $child_ecosystem_id, $fauna_ecosystems_table);
                    $child_card['activity'] = get_item_by_uuid($conn, $child_activity_id, $fauna_activities_table);
                    $child_card['diet'] = get_item_by_uuid($conn, $child_diet_id, $fauna_diets_table);
                    
                    //Add header style
                    $child_card['header_style'] = 'background-color: ' . $fauna_color . '; color: ' . $fauna_header_text_color . ';';

                    break;
                    
                //Child is a flora item, fetch ecosystem, activity, diet
                //+ discovery and discovery date 
                case $flora_id_str:
                    $sql = 'SELECT  
                    age, roots, food, primary_element, secondary_element, discovery_date, discoverer, screenshot
                    FROM ' . $flora_table . ' WHERE id = ?';
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
                    $child_card['age'] = get_item_by_uuid($conn, $child_age_id, $flora_ages_table);
                    $child_card['roots'] = get_item_by_uuid($conn, $child_roots_id, $flora_roots_table);
                    $child_card['food'] = get_item_by_uuid($conn, $child_food_id, $flora_food_table);
                    $child_card['primary_resource'] = get_item_by_uuid($conn, $child_prim_resource_id, $resources_table);
                    $child_card['secondary_resource'] = get_item_by_uuid($conn, $child_sec_resource_id, $resources_table);
                    
                    if ($child_card['primary_resource'] == '')
                    {
                        $child_card['primary_resource'] = ' - ';
                    }

                    if ($child_card['secondary_resource'] == '')
                    {
                        $child_card['secondary_resource'] = ' - ';
                    }
                    
                    //Add header style
                    $child_card['header_style'] = 'background-color: ' . $flora_color . '; color: ' . $flora_header_text_color . ';';

                    break;
                    
                //Child is a PoI, fetch type, long + lat
                //+ discovery and discovery date 
                case $poi_id_str:
                    $sql = 'SELECT  
                    type, planet_lat, planet_long, discovery_date, discoverer, screenshot
                    FROM ' . $pois_table . ' WHERE id = ?';
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
                    $child_card['poi_type'] = get_item_by_uuid($conn, $child_type_id, $poi_types_table);
                    $child_card['lat'] = $child_lat;
                    $child_card['long'] = $child_long;
                    
                    $child_card['type'] = 'PoI';
                    
                    //Add header style
                    $child_card['header_style'] = 'background-color: ' . $poi_color . '; color: ' . $poi_header_text_color . ';';

                    break;
            }
            
            //Add discovery info to card
            $child_card['discovery_date'] = strftime('%x', strtotime($child_discovery_date));
            $child_card['discoverer'] = $child_discoverer;

            //Get thumbnail
            $child_card['thumb'] = str_replace('header', 'thumb', $child_screenshot);
            
            $child_cards[] = $child_card;
        }
    }

    //Sort child cards by discovery date, last to first
    usort($child_cards, function($a, $b) {return $b['discovery_date'] <=> $a['discovery_date'];});

    //--------------------
	//Set up possible entries
	//--------------------

    $uuid_get = '&uuid=' . $uuid;

    $possible_entries = [
        ['type' => 'Fauna', 'url' => '../entry.php?type=fauna' . $uuid_get,],
        ['type' => 'Flora', 'url' => '../entry.php?type=flora'. $uuid_get ,],
        ['type' => 'Base', 'url' => '../entry.php?type=base' . $uuid_get,],    
        ['type' => 'PoI', 'url' => '../entry.php?type=poi' . $uuid_get,],   
    ];

	//--------------------
	//Prepare template
	//--------------------

	$template->verified = FALSE;

	//Check for login
	if (isset($_SESSION['verified']))
	{
		if ($_SESSION['verified']){
			$template->verified = TRUE;
		} 
	} 

	$template->uuid = $uuid;
	$template->source_table = $planets_table;
	$template->return_type = 'planet';

	$template->page_title = (($moon) ? 'Moon - ' : 'Planet - ') . $name;
	$template->item_stylesheet = '../style/planet.css';

	$template->item_name = $name;
    $template->item_type = (($moon) ? 'Moon' : 'Planet');
    $template->item_in_on = 'in';
	$template->context_name = $system_name;
    $template->context_type = 'Star System';
    $template->context_url = '../item.php?uuid=' . $system_id . '&type=' . $system_id_str;

	$template->discovery_date = $discovery_date;
	$template->discoverer = $discoverer;
	$template->screenshot_path = $screenshot;

	$template->glyph_code = $glyph_code;

    $template->possible_entries = $possible_entries;

	//Set up info_table
	$info_table = [
		"Biome" => $biome,
		"Weather" => $weather,
		"Sentinels" => $sentinel_level,
		"Fauna" => $fauna_level,
		"Flora" => $flora_level,
		"Resources" => $resources_str,
	];
	
	$template->info_table = $info_table;

    $template->child_cards = $child_cards;

	$template->articles = $articles;

	//--------------------
	//Execute template
	//--------------------

	try {
		echo $template->execute();
	} catch (Exception $e) {
		echo $e;
	}
	
?>

