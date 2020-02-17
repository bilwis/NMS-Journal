<?php
	//--------------------
	//Security Setup
	//--------------------
	
	if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/item.php?uuid=' . $_GET['uuid'] . '&type=ship');
        
        /*
		header("HTTP/1.1 403 Forbidden.");
		echo('Error 403: Forbidden. <br> <br> Direct File Access Prohibited.');
		exit();
        */
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
	id, parent_id, name, inventory, type,
	price, discovery_date, discoverer, screenshot
	FROM ' . $ships_table . ' WHERE id = ?';
	$params = [$uuid, ];

	$stmt = prepared_query($conn, $sql, $params);
	$stmt->store_result();

	if ($stmt->num_rows < 1)
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> Ship UUID does not exist.');
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
					  $inventory,
					  $type_id,
					  $price,
					  $discovery_date,
					  $discoverer,
					  $screenshot
                      );

	$stmt->fetch();

	$system_name = get_item_by_uuid($conn, $system_id, $systems_table);

	//--------------------
	//Get text for type id
	//--------------------

	//Create array with references to the variables,
	//to replace with names for planet info display
	$check_array = [
		[&$type, $type_id, $ship_types_table],
	];
	
	//Loop through each paired item_id & item_table, looking up 
	//the item from the item_table and changing the REFERENCED item variable to the name
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_uuid($conn, $arr[1], $arr[2]);
	}

	//--------------------
	//Get articles for ship
	//--------------------
	$ship_articles =  get_articles_for_uuid($conn, $uuid, $articles_table);

	if (!$ship_articles == 0)
	{
		foreach($ship_articles as $article) {
			$article['source'] = 'Ship - ' . $name;
			$article['source_url'] = '#';
			$article['header_style'] = 'background-color: ' . $ship_color . '; color: ' . $ship_header_text_color . ';';
			
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
	$template->source_table = $ships_table;
	$template->return_type = 'ship';

	$template->page_title = 'Ship - ' . $name;
	$template->item_stylesheet = '../style/ship.css';

	$template->item_name = $name;
    $template->item_type = 'Ship';
    $template->item_in_on = 'in';
	$template->context_name = $system_name;
    $template->context_type = 'Star System';
    $template->context_url = '../item.php?uuid=' . $system_id . '&type=' . $system_id_str;

	$template->discovery_date = $discovery_date;
	$template->discoverer = $discoverer;
	$template->screenshot_path = $screenshot;
    $template->screenshot_path_hi = str_replace('_header', '_full', $screenshot);

	$template->glyph_code = '';

    $template->possible_entries = $possible_entries;

	//Set up info_table
	$info_table = [
		"Type" => $type,
		"Inventory" => $inventory,
		"Price" => number_format_locale($price, 0) . ' U',
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

