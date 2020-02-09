<?php
	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/entry.php?type=system');
        
	}

	//--------------------
	//Template Setup
	//--------------------

	$template = new PHPTAL('templates/system_entry.html');
	
	//--------------------
	//Load from DB
	//--------------------

    //Get info from db
	$regions = get_id_and_name($conn, $regions_table);
	$lifeforms = get_id_and_name($conn, $lifeforms_table);
	$econ_types = get_id_and_name($conn, $economy_type_table);
	$econ_wealth = get_id_and_name($conn, $economy_wealth_table);
	$conflict_levels = get_id_and_name($conn, $conflict_levels_table);
	$specials = get_id_and_name($conn, $system_specials_table);

	//--------------------
	//Put into template
	//--------------------
	
	$template->regions = $regions;
	$template->lifeforms = $lifeforms;
	$template->econ_types = $econ_types;
	$template->econ_wealths = $econ_wealth;
	$template->conflict_levels = $conflict_levels;
	$template->specials = $specials;

    if ($uuid)
    {
        $template->preselect_name = get_item_by_uuid($conn, $uuid, $regions_table);
    } else {
        $template->preselect_name = '';
    }

	try {
		echo $template->execute();
	} catch (Exception $e) {
		echo $e;
	}

?>

