<?php
	//--------------------
	//Security Setup
	//--------------------
	
	if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/item.php?uuid=' . $_GET['uuid'] . '&type=fauna');

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
	id, parent_id, name, gender, behaviour,
	age, diet, bait, ecosystem, activity,
    height, weight, notes,
	discovery_date, discoverer, screenshot
	FROM ' . $fauna_table . ' WHERE id = ?';
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
					  $gender_id,
					  $behaviour_id,
					  $age_id,
					  $diet_id,
					  $bait_id,
					  $ecosystem_id,
                      $activity_id,
                      $height,
                      $weight,
                      $note_id,
					  $discovery_date,
					  $discoverer,
					  $screenshot);

	$stmt->fetch();

	$planet_name = get_item_by_uuid($conn, $planet_id, $planets_table);

	//--------------------
	//Get text for gender/behaviour/age/etc. ids
	//--------------------

	//Create array with references to the variables,
	//to replace with names for fauna info display
	$check_array = [
		[&$gender, $gender_id, $fauna_genders_table],
		[&$behaviour, $behaviour_id, $fauna_behaviours_table],
		[&$age, $age_id, $fauna_ages_table],
		[&$diet, $diet_id, $fauna_diets_table],
		[&$bait, $bait_id, $fauna_baits_table],
		[&$ecosystem, $ecosystem_id, $fauna_ecosystems_table],        
		[&$activity, $activity_id, $fauna_activities_table],
		[&$note, $note_id, $fauna_notes_table],
	];
	
	//Loop through each paired item_id & item_table, looking up 
	//the item from the item_table and changing the REFERENCED item variable to the name
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_uuid($conn, $arr[1], $arr[2]);
	}

	//--------------------
	//Get articles for fauna
	//--------------------

	$fauna_articles =  get_articles_for_uuid($conn, $uuid, $articles_table);

    if (!$fauna_articles == 0)
	{
		foreach($fauna_articles as $article) {
			$article['source'] = 'Fauna - ' . $name;
			$article['source_url'] = '#';
			$article['header_style'] = 'background-color: ' . $fauna_color . '; color: ' . $fauna_header_text_color . ';';
			
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
	$template->source_table = $fauna_table;
	$template->return_type = 'fauna';

	$template->page_title = 'Fauna - ' . $name;
	$template->item_stylesheet = '../style/fauna.css';

	$template->item_name = $name;
    $template->item_type = 'Fauna';
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
		"Height" => $height . 'm',
		"Weight" => $weight . 'kg',
		"Gender" => $gender,
		"Behaviour" => $behaviour,
		"Age" => $age,
		"Diet" => $diet,
        "Bait" => $bait,
		"Ecosystem" => $ecosystem,
		"Activity" => $activity,
        "Notes" => $note,
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

