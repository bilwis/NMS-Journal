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
	$planet = $_POST['planet'];
	$age = $_POST['age'];
	$roots = $_POST['roots'];
	$food = $_POST['food'];
	$note = $_POST['note'];
	$resource_primary = $_POST['prim_resource'];
    $resource_secondary = $_POST['sec_resource'];
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
            $img_paths[$img_arr[0]] = handle_base64($img_arr[1], $img_arr[0], '../upload/screenshots/flora/');        
        }
        catch (Exception $ex)
        {
            header("HTTP/1.1 500 Malformed request.");
			echo('Failure while uploading images. "' . $ex .'".');
			exit();
        }
    }

	//--------------------
	//Process planet input
	//--------------------

	//Check if input is valid UUID
	if (Ramsey\Uuid\Uuid::isValid($planet))
	{
		$planet_uuid = $planet;
	} else {
		//Check if system exists
		$planet_uuid = get_item_by_name($conn, $planet, $planets_table);
		if ($planet_uuid == NULL)
		{
			header("HTTP/1.1 400 Malformed request.");
			echo('System does not exist. Given system parameter: "' . $planet .'".');
			return;
		}
	}

	//--------------------
	//Process biome/weather/levels input
	//--------------------

	//Create array with references to the variables,
	//to replace with existing or new id's for planet table entering
	$check_array = [
		[&$age, $flora_ages_table],
		[&$roots, $flora_roots_table],
		[&$food, $flora_food_table],
		[&$note, $flora_notes_table],
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

    $resource_primary_id = find_or_insert_item($conn, $resource_primary, $resources_table);
    $resource_secondary_id = find_or_insert_item($conn, $resource_secondary, $resources_table);

	//--------------------
	//Enter into database
	//--------------------

	//Generate UUID
	$uuid = Ramsey\Uuid\Uuid::uuid1();

	//Prepare Request

	//Form Request
	$params =   [ $uuid,
				  $planet_uuid,
				  $orig_name,
				  $new_name,
				  $age,
				  $roots,
				  $food,
				  $note,
				  $resource_primary_id,
                  $resource_secondary_id,
				  $discovery_date,
				  $img_paths['header'],
				  $discoverer,
				  ];

	$sql = 'INSERT INTO '.$flora_table.' (
	id,
	parent_id,
	orig_name,
	name,
	age,
	roots,
	food,
	notes,
	primary_element,
	secondary_element,
	discovery_date,
	screenshot,
	discoverer
	) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';

	$types = 'sssssssssssss';

	try{
		$stmt = prepared_query($conn, $sql, $params, $types);
		$stmt->close();
		header("HTTP/1.1 201 Created.");
		header("Location: https://nms.bilwis.de/item.php?uuid=" . $uuid . '&type=flora',TRUE,303);
		
	} catch (Exception $ex){
		echo($ex);
		echo($conn->error);
		die();
		exit();
	}
		

?>
