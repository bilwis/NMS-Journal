<?php
	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: https://nms.bilwis.de/entry.php?type=ship');

	}
	//--------------------
	//Template Setup
	//--------------------

	$template = new PHPTAL('templates/ship_entry.html');
	
	//--------------------
	//Load from DB
	//--------------------
	
	//Get info from db
	$types = get_id_and_name($conn, $ship_types_table);
	$systems = get_id_and_name($conn, $systems_table);

    //--------------------
	//Put into template
	//--------------------

	$template->systems = $systems;
	$template->types = $types;

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

