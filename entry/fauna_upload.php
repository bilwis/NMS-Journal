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
	$gender = $_POST['gender'];
	$behaviour = $_POST['behaviour'];
	$age = $_POST['age'];
	$diet = $_POST['diet'];
	$bait = $_POST['bait'];
	$ecosystem = $_POST['ecosystem'];
	$activity = $_POST['activity'];
	$note = $_POST['note'];
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
            $img_paths[$img_arr[0]] = handle_base64($img_arr[1], $img_arr[0], '../upload/screenshots/fauna/');        
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
		[&$gender, $fauna_genders_table],
		[&$behaviour, $fauna_behaviours_table],
		[&$age, $fauna_ages_table],
		[&$diet, $fauna_diets_table],
		[&$bait, $fauna_baits_table],
        [&$ecosystem, $fauna_ecosystems_table],
		[&$activity, $fauna_activities_table],
		[&$note, $fauna_notes_table],
	];
	
	//Loop through each paired item & item_table, looking up or inserting
	//the item into the item_table and changing the REFERENCED item variable to the id
	foreach ($check_array as $arr)
	{
		$arr[0] = find_or_insert_item($conn, $arr[0], $arr[1]);
	}

	//--------------------
	//Process float input
	//--------------------

    $height = floatval($_POST['height']);
	$weight = floatval($_POST['weight']);

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
				  $gender,
				  $behaviour,
				  $age,
				  $diet,
				  $bait,
				  $ecosystem,
                  $activity,
                  $note,
                  $height,
                  $weight,
				  $discovery_date,
				  $img_paths['header'],
				  $discoverer,
				  ];

	$sql = 'INSERT INTO '.$fauna_table.' (
	id,
	parent_id,
	orig_name,
	name,
	gender,
	behaviour,
	age,
	diet,
	bait,
	ecosystem,
    activity,
	notes,
    height,
    weight,
	discovery_date,
	screenshot,
	discoverer
	) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

	$types = 'ssssssssssssddsss';

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
