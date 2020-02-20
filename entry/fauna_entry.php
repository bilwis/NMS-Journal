<?php
	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    if (!defined('SECURE_PAGE'))
	{
        header("HTTP/1.1 301 Moved permanently.");
        header('Location: entry.php?type=fauna');

	}
	//--------------------
	//Template Setup
	//--------------------

	$template = new PHPTAL('templates/fauna_entry.html');
	
	//--------------------
	//Load from DB
	//--------------------
	
	//Get info from db
	$planets = get_id_and_name($conn, $planets_table);
	$genders = get_id_and_name($conn, $fauna_genders_table);
	$behaviours = get_id_and_name($conn, $fauna_behaviours_table);
	$ages = get_id_and_name($conn, $fauna_ages_table);
	$diets = get_id_and_name($conn, $fauna_diets_table);
	$baits = get_id_and_name($conn, $fauna_baits_table);
	$ecosystems = get_id_and_name($conn, $fauna_ecosystems_table);
	$activities = get_id_and_name($conn, $fauna_activities_table);
	$notes = get_id_and_name($conn, $fauna_notes_table);
	
    //--------------------
	//Put into template
	//--------------------

	$template->planets = $planets;
	$template->genders = $genders;
	$template->behaviours = $behaviours;
	$template->ages = $ages;
	$template->diets = $diets;
	$template->baits = $baits;
	$template->ecosystems = $ecosystems;
	$template->activities = $activities;
	$template->notes = $notes;

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

