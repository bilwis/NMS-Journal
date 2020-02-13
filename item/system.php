<?php
	//--------------------
	//Security Setup
	//--------------------

	if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/item.php?uuid=' . $_GET['uuid'] . '&type=system');
        
        /*
		header("HTTP/1.1 403 Forbidden.");
		echo('Error 403: Forbidden. <br> <br> Direct File Access Prohibited.');
		exit();
        */
	}

	//--------------------
	//Options
	//--------------------

    $child_types_to_display = [$planet_id_str,];
	
	//--------------------
	//Get system data
	//--------------------

	//Check wheter System with given UUID exists
	$sql = 'SELECT 
	id, parent_id, name, spectral_class, water,
	lifeform, wealth, econ_type, conflict, 
	planets, moons, special, discovery_date, discoverer, screenshot
	FROM ' . $systems_table . ' WHERE id = ?';
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
					  $region_id,
					  $name,
					  $spectral_class,
					  $water_bool,
					  $lifeform_id,
					  $econ_wealth_id,
					  $econ_type_id,
					  $conflict_id,
					  $no_planets,
					  $no_moons,
					  $special_id,
					  $discovery_date,
					  $discoverer,
					  $screenshot);

	$stmt->fetch();

	$region_name = get_item_by_uuid($conn, $region_id, $regions_table);

    //--------------------
	//Get text for biome/weather/life/sentinel ids
	//--------------------

	//Create array with references to the variables,
	//to replace with names for planet info display
	$check_array = [
		[&$lifeform, $lifeform_id, $lifeforms_table],
		[&$econ_wealth, $econ_wealth_id, $economy_wealth_table],
		[&$econ_type, $econ_type_id, $economy_type_table],
		[&$conflict, $conflict_id, $conflict_levels_table],
		[&$special, $special_id, $system_specials_table],
	];
	
	//Loop through each paired item_id & item_table, looking up 
	//the item from the item_table and changing the REFERENCED item variable to the name
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_uuid($conn, $arr[1], $arr[2]);
	}

	//--------------------
	//Get articles for system (and all children)
	//--------------------

	//Cascade articles
	//1. Get all planets, tools, ships in system
	//2. Get all bases, fauna, flora for all planets
	
	$children = get_system_children($conn, $uuid);
	$articles = get_articles_from_childlist($conn, $children, $articles_table);

	$system_articles =  get_articles_for_uuid($conn, $uuid, $articles_table);

	if (!$system_articles == 0)
	{
		foreach($system_articles as $article) {
			$article['source'] = 'System - ' . $name;
			$article['source_url'] = '#';
			$article['header_style'] = 'background-color: ' . $system_color . '; color: ' . $system_header_text_color . ';';
			
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
            $child_card['url'] = 'https://nms.bilwis.de/item.php?uuid=' . $child['uuid'] . '&type=' . $child['id_str'];
            
            //Required info: name, url, type, thumb, biome, resource_str, discoverer, discovery_date
            switch ($child['id_str'])
            {

                //Child is a planet, fetch biome and resource information in addition
                //to the discovery and discovery date 
                case $planet_id_str:
                    $sql = 'SELECT  
                    biome, resources, discovery_date, discoverer, screenshot, moon
                    FROM ' . $planets_table . ' WHERE id = ?';
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
                    $child_card['biome'] = get_item_by_uuid($conn, $child_biome_id, $biomes_table);

                    //Get text for resources
                    $child_resource_ids = explode(',', $child_resource_ids_str);
                    $child_resources = [];

                    foreach($child_resource_ids as $child_resource_id)
                    {
                        $child_resources[] = get_item_by_uuid($conn, $child_resource_id, $resources_table);
                    }

                    $child_card['header_style'] = 'background-color: ' . $planet_color . '; color: ' . $planet_header_text_color . ';';

                    //Add resources to card
                    $child_card['resources'] = implode(', ', $child_resources);

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
        ['type' => 'Planet', 'url' => '../entry.php?type=planet' . $uuid_get,],
        ['type' => 'Ship', 'url' => '../entry.php?type=ship' . $uuid_get,],  
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
	$template->source_table = $systems_table;
	$template->return_type = 'system';

	$template->page_title = 'System - ' . $name;
	$template->item_stylesheet = '../style/system.css';

	$template->item_name = $name;
    $template->item_type = 'Star System';
	$template->context_name = $region_name;
    $template->context_type = 'Region';
    $template->context_url = '#';

	$template->discovery_date = $discovery_date;
	$template->discoverer = $discoverer;
	$template->screenshot_path = $screenshot;

	$template->glyph_code = ''; //TODO: move to planet specific template

    $template->possible_entries = $possible_entries;

	//Set up info_table
	$info_table = [
		"Spectral Class" => $spectral_class,
		"Water" => $water_bool,
		"Lifeform" => $lifeform,
		"Wealth" => $econ_wealth,
		"Economy" => $econ_type,
		"Conflict" => $conflict,
		"Planets" => $no_planets,
		"Moons" => $no_moons,
		"Special" => $special,
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
