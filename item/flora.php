<?php
	//--------------------
	//Security Setup
	//--------------------
	
	if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/item.php?uuid=' . $_GET['uuid'] . '&type=flora');

	}

	//--------------------
	//Options
	//--------------------

    $child_types_to_display = [];
	
	//--------------------
	//Get planet data
	//--------------------

	//Check wheter Planet with given UUID exists
	$sql = 'SELECT 
	id, parent_id, name, age, roots,
	food, notes, primary_element, secondary_element,
	discovery_date, discoverer, screenshot
	FROM ' . $flora_table . ' WHERE id = ?';
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
					  $planet_id,
					  $name,
					  $age_id,
					  $roots_id,
					  $food_id,
					  $notes_id,
					  $primary_element_id,
					  $secondary_element_id,
					  $discovery_date,
					  $discoverer,
					  $screenshot);

	$stmt->fetch();

	$planet_name = get_item_by_uuid($conn, $planet_id, $planets_table);

	//--------------------
	//Get text for gender/behaviour/age/etc. ids
	//--------------------

	//Create array with references to the variables,
	//to replace with names for flora info display
	$check_array = [
		[&$age, $age_id, $flora_ages_table],
		[&$roots, $roots_id, $flora_roots_table],
		[&$food, $food_id, $flora_food_table],
		[&$notes, $notes_id, $flora_notes_table],
		[&$primary_resource, $primary_element_id, $resources_table],
		[&$secondary_resource, $secondary_element_id, $resources_table],      
	];
	
	//Loop through each paired item_id & item_table, looking up 
	//the item from the item_table and changing the REFERENCED item variable to the name
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_uuid($conn, $arr[1], $arr[2]);
	}

    if ($primary_resource == '')
    {
        $primary_resource = ' - ';
    }

    if ($secondary_resource == '')
    {
        $secondary_resource = ' - ';
    }

	//--------------------
	//Get articles for fauna
	//--------------------

	$flora_articles =  get_articles_for_uuid($conn, $uuid, $articles_table);

    if (!$flora_articles == 0)
	{
		foreach($flora_articles as $article) {
			$article['source'] = 'Flora - ' . $name;
			$article['source_url'] = '#';
			$article['header_style'] = 'background-color: ' . $flora_color . '; color: ' . $flora_header_text_color . ';';
			
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
	$template->source_table = $flora_table;
	$template->return_type = 'flora';

	$template->page_title = 'Flora - ' . $name;
	$template->item_stylesheet = '../style/flora.css';

	$template->item_name = $name;
    $template->item_type = 'Flora';
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
		"Age" => $age,
		"Roots" => $roots,
		"Food" => $food,
		"Note" => $notes,
        "Prim. Resource" => $primary_resource,
		"Sec. Resource" => $secondary_resource,
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

