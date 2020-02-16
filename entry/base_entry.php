<?php
	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/entry.php?type=base');

	}
	//--------------------
	//Template Setup
	//--------------------

	$template = new PHPTAL('templates/base_entry.html');
	
	//--------------------
	//Load from DB
	//--------------------
	
	//Get info from db
	$planets = get_id_and_name($conn, $planets_table);
	$facilities = get_id_and_name($conn, $base_facilities_table);
	
    //--------------------
	//Put into template
	//--------------------

	$template->planets = $planets;
	$template->facilities = $facilities;

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

