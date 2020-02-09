<?php
	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/entry.php?type=planet');
        
        /*
		header("HTTP/1.1 403 Forbidden.");
		echo('Error 403: Forbidden. <br> <br> Direct File Access Prohibited.');
		exit();
        */
	}
	//--------------------
	//Template Setup
	//--------------------

	$template = new PHPTAL('templates/planet_entry.html');
	
	//--------------------
	//Load from DB
	//--------------------
	
	//Get info from db
	$systems = get_id_and_name($conn, $systems_table);
	$biomes = get_id_and_name($conn, $biomes_table);
	$weathers = get_id_and_name($conn, $weathers_table);
	$life_levels = get_id_and_name($conn, $life_levels_table);
	$sentinel_levels = get_id_and_name($conn, $sentinel_levels_table);
	$resources = get_id_and_name($conn, $resources_table);

    //--------------------
	//Put into template
	//--------------------

	$template->systems = $systems;
	$template->biomes = $biomes;
	$template->weathers = $weathers;
	$template->life_levels = $life_levels;
	$template->sentinel_levels = $sentinel_levels;
	$template->resources = $resources;

    if ($uuid)
    {
        $template->preselect_name = get_item_by_uuid($conn, $uuid, $systems_table);
    } else {
        $template->preselect_name = '';
    }

	try {
		echo $template->execute();
	} catch (Exception $e) {
		echo $e;
	}

?>

