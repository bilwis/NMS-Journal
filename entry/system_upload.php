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
	$spectral_class_raw = $_POST['spectral_class'];
	$water = $_POST['water'];
	$region = $_POST['region'];
	$lifeform = $_POST['lifeform'];
	$econ_type = $_POST['econ_type'];
	$econ_wealth = $_POST['econ_wealth'];
	$conflict = $_POST['conflict_level'];
	$no_planets = $_POST['no_planets'];
	$no_moons = $_POST['no_moons'];
	$special = $_POST['special'];
	$discovery_date = $_POST['discovery_date'];
	$discoverer = $_POST['discoverer'];
	$screenshot = '';

	//--------------------
	//Upload screenshot file
	//--------------------

    $imgs = [['full', $_POST['full_img']], ['header', $_POST['header_img']], ['thumb', $_POST['thumb_img']]];
    $img_paths = [];

    foreach ($imgs as $img_arr)
    {
        try 
        {
            $img_paths[$img_arr[0]] = handle_base64($img_arr[1], $img_arr[0], '../upload/screenshots/systems/');        
        }
        catch (Exception $ex)
        {
            header("HTTP/1.1 500 Malformed request.");
			echo('Failure while uploading images. "' . $ex .'".');
			exit();
        }
    }

	//--------------------
	//Check region input
	//--------------------

	//Check if input is valid UUID
	if (Ramsey\Uuid\Uuid::isValid($region))
	{
		$region_uuid = $region;
	} else {
		//Check if region exists
		$region_uuid = get_item_by_name($conn, $region, $regions_table, FALSE);
		if ($region_uuid == NULL)
		{
			$region_uuid = Ramsey\Uuid\Uuid::Uuid1();
			
			//Add new region to DB
			$params =   [ $region_uuid, $region, ];

			$sql = 'INSERT INTO ' . $regions_table . ' (
			id,
			name
			) VALUES (?,?)';

			$types = 'ss';

			//Execute Query
			try{
				$stmt = prepared_query($conn, $sql, $params, $types);
				$stmt->close();

			} catch (Exception $ex){
				echo($ex);
				echo($conn->error);
				die();
			}
		}
	}

	//--------------------
	//Sanitize POST data
	//--------------------

	//Parse spectral class
	$major_class = $spectral_class_raw[0];
	$subclass = $spectral_class_raw[1];
	$peculiarities = substr($spectral_class_raw, 2);

	$json = file_get_contents('./json/system_data.json');	
	$system_data_params = json_decode($json, TRUE);

	if (!in_array($major_class, $system_data_params['spectral_class']))
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Major spectral class not recognized. <br> Given class: "' . $major_class .'".');
		return;
	}

	if (intval($subclass) <0 || intval($subclass) >9)
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Spectral subclass not recognized. <br> Given subclass: "' . $subclass .'".');
		return;
	}

	foreach ($peculiarities as $pec_char)
	{
		if (!in_array($pec_char, $system_data_params['spectral_oddities']))
		{
			header("HTTP/1.1 400 Malformed request.");
			echo('Spectral peculiarity not recognized. <br> Given peculiarity: "' . $pec_char .'".');
			return;
		}
	}

	$spectral_class = $major_class . $subclass . $peculiarities;

	//Handle Checkbox input
	if ($water == 'on') { $water_bool = '1'; }
	if ($water == NULL) { $water_bool = '0'; }

    //--------------------
	//Process datalist input
	//--------------------

	//Create array with references to the variables,
	//to replace with existing or new id's for planet table entering
	$check_array = [
		[&$conflict, $conflict_levels_table],
		[&$lifeform, $lifeforms_table],
		[&$econ_type, $economy_type_table],
		[&$econ_wealth, $economy_wealth_table],
		[&$special, $system_specials_table],
	];
	
	//Loop through each paired item & item_table, looking up or inserting
	//the item into the item_table and changing the REFERENCED item variable to the id
	foreach ($check_array as $arr)
	{
		$arr[0] = get_item_by_name($conn, $arr[0], $arr[1]);
	}


	//--------------------
	//Enter into database
	//--------------------

	//Generate UUID
	$uuid = Ramsey\Uuid\Uuid::uuid1();

	//Prepare Request

	//Form Request
	$params =   [ $uuid,
				  $region_uuid,
				  $orig_name,
				  $new_name,
				  $spectral_class,
				  $water_bool,
				  $lifeform,
				  $econ_wealth,
				  $econ_type,
				  $conflict,
				  $no_planets,
				  $no_moons,
				  $special,
				  $discovery_date,
				  $discoverer,
				  $img_paths['header']
				  ];

	$sql = 'INSERT INTO nms_systems (
	id,
	parent_id,
	orig_name,
	name,
	spectral_class,
	water,
	lifeform,
	wealth,
	econ_type,
	conflict,
	planets,
	moons,
	special,
	discovery_date,
	discoverer,
	screenshot
	) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

	$types = 'ssssssssssssssss';

	try{
		$stmt = prepared_query($conn, $sql, $params, $types);
		$stmt->close();
		header("HTTP/1.1 201 Created.");
		header("Location: https://nms.bilwis.de/item.php?uuid=" . $uuid . '&type=system',TRUE,303);
		
	} catch (Exception $ex){
		echo($ex);
		echo($conn->error);
		die();
		exit();
	}
		

?>