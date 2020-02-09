<?php
	require_once('libs/autoload.php');
	require_once('db.php');
	

	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	session_start();
	
	if (isset($_SESSION['verified']))
	{
		if (!$_SESSION['verified']){
			header("HTTP/1.1 403 Site access denied.");
			exit();
		} 
	} else {
		header("HTTP/1.1 403 Site access denied.");
		exit();
	}

	//--------------------
	//Template Setup
	//--------------------

	$template = new PHPTAL('fauna_entry.html');
	
	//--------------------
	//Load from DB
	//--------------------
	
	//Get regions from db
	$regions = array();

	$result = $conn->query("SELECT id, name FROM nms_regions");

	if ($result->num_rows > 0) {
		$regions = $result->fetch_all();
	}

	//Get economy types from db
	$econ_types = get_types($conn, "db_system_economy_type");
	$econ_wealth = get_types($conn, "db_system_economy_wealth");
	$conflict_levels = get_types($conn, "db_system_conflict");

	//Get info from db
	$planets = get_id_and_name($conn, $planets_table);
	$biomes = get_id_and_name($conn, $biomes_table);
	$weathers = get_id_and_name($conn, $weathers_table);
	$life_levels = get_id_and_name($conn, $life_levels_table);
	$sentinel_levels = get_id_and_name($conn, $sentinel_levels_table);
	$resources = get_id_and_name($conn, $resources_table);

	

	//--------------------
	//Load from JSON
	//--------------------

	$json = file_get_contents('./json/system_data.json');	
	$system_data = json_decode($json, TRUE);

	$lifeforms = $system_data["lifeforms"];
	$specials = $system_data["special"];

	
	$template->regions = $regions;
	$template->lifeforms = $lifeforms;
	$template->econ_types = $econ_types;
	$template->econ_wealth = $econ_wealth;
	$template->conflict_levels = $conflict_levels;
	$template->specials = $specials;

	try {
		echo $template->execute();
	} catch (Exception $e) {
		echo $e;
	}

?>

