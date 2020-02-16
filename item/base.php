<?php
	//--------------------
	//Security Setup
	//--------------------
	
	if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/item.php?uuid=' . $_GET['uuid'] . '&type=base');

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
	id, parent_id, name, planet_lat, planet_long, facilities,
	discovery_date, discoverer, screenshot
	FROM ' . $bases_table . ' WHERE id = ?';
	$params = [$uuid, ];

	$stmt = prepared_query($conn, $sql, $params);
	$stmt->store_result();

	if ($stmt->num_rows < 1)
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> Base UUID does not exist.');
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
					  $facilities_ids_str,
					  $discovery_date,
					  $discoverer,
					  $screenshot);

	$stmt->fetch();

	$planet_name = get_item_by_uuid($conn, $planet_id, $planets_table);

	//--------------------
	//Get text for facilities
	//--------------------

	$facilities_ids = explode(',', $facilities_ids_str);
	$facilities = [];

	foreach($facilities_ids as $facility_id)
	{
		$facilities[] = get_item_by_uuid($conn, $facility_id, $base_facilities_table);
	}

	$facilities_str = implode(', ', $facilities);

	//--------------------
	//Get articles for PoI
	//--------------------

	$base_articles =  get_articles_for_uuid($conn, $uuid, $articles_table);

    if (!$base_articles == 0)
	{
		foreach($base_articles as $article) {
			$article['source'] = 'Base - ' . $name;
			$article['source_url'] = '#';
			$article['header_style'] = 'background-color: ' . $base_color . '; color: ' . $base_header_text_color . ';';
			
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
	$template->source_table = $bases_table;
	$template->return_type = 'base';

	$template->page_title = 'Base - ' . $name;
	$template->item_stylesheet = '../style/base.css';

	$template->item_name = $name;
    $template->item_type = 'Base';
    $template->item_in_on = 'on';
	$template->context_name = $planet_name;
    $template->context_type = 'Planet';
    $template->context_url = '../item.php?uuid=' . $planet_id . '&type=' . $planet_id_str;

	$template->discovery_date = $discovery_date;
	$template->discoverer = $discoverer;
	$template->screenshot_path = $screenshot;

	$template->glyph_code = '';

    $template->possible_entries = $possible_entries;

	//Set up info_table
	$info_table = [
		"Latitude" => $lat,
		"Longitude" => $long,
		"Facilities" => $facilities_str,
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

