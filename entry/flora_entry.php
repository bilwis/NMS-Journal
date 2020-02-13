<?php
	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/entry.php?type=flora');

	}
	//--------------------
	//Template Setup
	//--------------------

	$template = new PHPTAL('templates/flora_entry.html');
	
	//--------------------
	//Load from DB
	//--------------------
	
	//Get info from db
	$planets = get_id_and_name($conn, $planets_table);
	$age = get_id_and_name($conn, $flora_ages_table);
	$roots = get_id_and_name($conn, $flora_roots_table);
	$food = get_id_and_name($conn, $flora_food_table);
	$notes = get_id_and_name($conn, $flora_notes_table);
	$resources = get_id_and_name($conn, $resources_table);
	
    //--------------------
	//Put into template
	//--------------------

	$template->planets = $planets;
	$template->ages = $age;
	$template->roots = $roots;
	$template->foods = $food;
	$template->notes = $notes;
	$template->resources = $resources;

    if ($uuid)
    {
        $template->preselect_name = get_item_by_uuid($conn, $uuid, $planets_table);
    } else {
        $template->preselect_name = '';
    }

	try {
		echo $template->execute();
	} catch (Exception $e) {
		echo $e;
	}

?>

