<?php
	require_once('../libs/autoload.php');
	require_once('../db.php');
	require_once('file_upload.php');
	
	//--------------------
	//Security Setup
	//--------------------

	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	session_start();

	if (isset($_SESSION['verified']))
	{
		if (!$_SESSION['verified']){
			header("HTTP/1.1 401 Unauthorized. Login to access site.");
			exit();
		} 
	} else {
		header("HTTP/1.1 401 Unauthorized. Login to access site.");
		exit();
	}

	//--------------------
	//Handle POST data
	//--------------------

	$orig_name = $_POST['orig_name'];
	$new_name = $_POST['new_name'];
	$system = $_POST['system'];
	$biome = $_POST['biome'];
	$weather = $_POST['weather'];
	$sentinel_level = $_POST['sentinel_level'];
	$fauna_level = $_POST['fauna_level'];
	$flora_level = $_POST['flora_level'];
	$resources_string = $_POST['resources'];
	$glyph_code = $_POST['glyph_code'];
	$discovery_date = $_POST['discovery_date'];
	$discoverer = $_POST['discoverer'];
	$screenshot = '';
	
	//--------------------
	//Handle POSTed images
	//--------------------

    $imgs = [['full', $_POST['full_img']], ['header', $_POST['header_img']], ['thumb', $_POST['thumb_img']]];
    $img_paths = [];

    foreach ($imgs as $img_arr)
    {
        try 
        {
            $img_paths[$img_arr[0]] = handle_base64($img_arr[1], $img_arr[0], '../upload/screenshots/planets/');        
        }
        catch (Exception $ex)
        {
            header("HTTP/1.1 500 Malformed request.");
			echo('Failure while uploading images. "' . $ex .'".');
			exit();
        }
    }

	//--------------------
	//Process system input
	//--------------------

	//Check if input is valid UUID
	if (Ramsey\Uuid\Uuid::isValid($system))
	{
		$system_uuid = $system;
	} else {
		//Check if system exists
		$system_uuid = get_item_by_name($conn, $system, $systems_table);
		if ($system_uuid == NULL)
		{
			header("HTTP/1.1 400 Malformed request.");
			echo('System does not exist. Given system parameter: "' . $system .'".');
			return;
		}
	}

	//--------------------
	//Process biome/weather/levels input
	//--------------------

	//Create array with references to the variables,
	//to replace with existing or new id's for planet table entering
	$check_array = [
		[&$biome, $biomes_table],
		[&$weather, $weathers_table],
		[&$sentinel_level, $sentinel_levels_table],
		[&$fauna_level, $life_levels_table],
		[&$flora_level, $life_levels_table],
	];
	
	//Loop through each paired item & item_table, looking up or inserting
	//the item into the item_table and changing the REFERENCED item variable to the id
	foreach ($check_array as $arr)
	{
		$arr[0] = find_or_insert_item($conn, $arr[0], $arr[1]);
	}

	//--------------------
	//Process resource input
	//--------------------

	//Resource string is given as comma delimited list
	$resources = explode(',', $resources_string);

    $resources = array_unique($resources);

	//Loop through each resource in the array, looking up or inserting
	//the resource into the resource_table and changing the REFERENCED resource array entry to the id
	foreach ($resources as &$resource)
	{
		$resource = find_or_insert_item($conn, $resource, $resources_table);
	}

	//Glue ids together with comma for sorage as string
	$resources = implode(',', $resources);

	//--------------------
	//Process glyph code input
	//--------------------

	//TODO

	//--------------------
	//Enter into database
	//--------------------

	//Generate UUID
	$uuid = Ramsey\Uuid\Uuid::uuid1();

	//Prepare Request

	//Form Request
	$params =   [ $uuid,
				  $system_uuid,
				  $orig_name,
				  $new_name,
				  $biome,
				  $weather,
				  $sentinel_level,
				  $flora_level,
				  $fauna_level,
				  $resources,
				  $discovery_date,
				  $img_paths['header'],
				  $discoverer,
				  $glyph_code
				  ];

	$sql = 'INSERT INTO '.$planets_table.' (
	id,
	parent_id,
	orig_name,
	name,
	biome,
	weather,
	sentinel_level,
	flora_level,
	fauna_level,
	resources,
	discovery_date,
	screenshot,
	discoverer,
	glyph_code
	) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

	$types = 'ssssssssssssss';

	try{
		$stmt = prepared_query($conn, $sql, $params, $types);
		$stmt->close();
		header("HTTP/1.1 201 Created.");
		header("Location: https://nms.bilwis.de/item.php?uuid=" . $uuid . '&type=planet',TRUE,303);
		
	} catch (Exception $ex){
		echo($ex);
		echo($conn->error);
		die();
		exit();
	}
		

?>
