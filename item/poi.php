<?php
	//--------------------
	//Security Setup
	//--------------------
	
	if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/item.php?uuid=' . $_GET['uuid'] . '&type=poi');

	}

	//--------------------
	//Options
	//--------------------

    $child_types_to_display = [];
	
	//--------------------
	//Get PoI data
	//--------------------

	//Check wheter PoI with given UUID exists
	$sql = 'SELECT 
	id, parent_id, name, planet_lat, planet_long, type,
	discovery_date, discoverer, screenshot
	FROM ' . $pois_table . ' WHERE id = ?';
	$params = [$uuid, ];

	$stmt = prepared_query($conn, $sql, $params);
	$stmt->store_result();

	if ($stmt->num_rows < 1)
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> PoI UUID does not exist.');
		exit();
	} elseif ($stmt->num_rows > 1) {
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> Multiple matches for UUID.');
		exit();
	} 

	//If yes, bind results

	$stmt->bind_result($uuid,
					  $planet_id,
					  $name,
					  $lat,
					  $long,
					  $poi_type_id,
					  $discovery_date,
					  $discoverer,
					  $screenshot);

	$stmt->fetch();

	$planet_name = get_item_by_uuid($conn, $planet_id, $planets_table);

	//--------------------
	//Get text for type id
	//--------------------

	//Create array with references to the variables,
	//to replace with names for PoI info display
	$check_array = [
		[&$poi_type, $poi_type_id, $poi_types_table],
	];
	
	//Loop through each paired item_id & item_table, looking up 
	//the item from the item_table and changing the REFERENCED item variable to the name
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_uuid($conn, $arr[1], $arr[2]);
	}

	//--------------------
	//Get articles for PoI
	//--------------------

	$poi_articles =  get_articles_for_uuid($conn, $uuid, $articles_table);

    if (!$poi_articles == 0)
	{
		foreach($poi_articles as $article) {
			$article['source'] = 'PoI - ' . $name;
			$article['source_url'] = '#';
			$article['header_style'] = 'background-color: ' . $poi_color . '; color: ' . $poi_header_text_color . ';';
			
			$articles[] = $article; 
		}
	}

    //Sort articles by timestamp, last to first
    usort($articles, function($a, $b) {return $b['timestamp'] <=> $a['timestamp'];});

    //--------------------
	//Set up possible entries
	//--------------------

    $uuid_get = '&uuid=' . $uuid;

    $possible_entries = [];

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
	$template->source_table = $pois_table;
	$template->return_type = 'poi';

	$template->page_title = 'PoI - ' . $name;
	$template->item_stylesheet = '../style/poi.css';

	$template->item_name = $name;
    $template->item_type = 'Point of Interest';
    $template->item_in_on = 'on';
	$template->context_name = $planet_name;
    $template->context_type = 'Planet';
    $template->context_url = '../item.php?uuid=' . $planet_id . '&type=' . $planet_id_str;

	$template->discovery_date = $discovery_date;
	$template->discoverer = $discoverer;
	$template->screenshot_path = $screenshot;
    $template->screenshot_path_hi = str_replace('_header', '_full', $screenshot);

	$template->glyph_code = '';

    $template->possible_entries = $possible_entries;

	//Set up info_table
	$info_table = [
		"Latitude" => $lat,
		"Longitude" => $long,
		"Type" => $poi_type,
	];
	
	$template->info_table = $info_table;

    $template->child_cards = '';

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

