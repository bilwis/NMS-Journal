<?php

	require_once('libs/autoload.php');
	require_once('db.php');
	require_once('style/color_vars.php');

	//--------------------
	//Security Setup
	//--------------------
	
	header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
	session_start();

    //--------------------
	//Set up default variables
	//--------------------
    $possible_types = ['articles', ];
    $default_items = ['articles' => 5, ]; //corresponding to the possible types array
    $session_var_name = ['articles' => 'articles', ];

	//--------------------
	//Get pagination GET data
	//--------------------

echo($_GET['type']);
    $pagination_type = $_GET['type']; //articles or items or ...
    $page = $_GET['pageNumber'];
    $items_per_page = intval($_GET['pageSize']);

	if ($pagination_type == '')
	{
		header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> No pagination type specified.');
		exit();
	} elseif (!in_array($pagination_type, $possible_types))
    {
        header("HTTP/1.1 400 Malformed request.");
		echo('Error 400: Malformed request. <br><br> Pagination type undefined or not implemented.');
		exit();
    }

    if ($page = '') {
		$page = 0;
	} 

    if ($items_per_page == 0) {
		$items_per_page = $default_items[$pagination_type];
	} 

    //--------------------
	//Check for session data
	//--------------------

    $session_data = $_SESSION[$session_var_name[$pagination_type]];

    if ($session_data == '')
	{
		header("HTTP/1.1 500 Session data not found.");
		echo('Error 500 Internal Server Error. <br><br> No session data for type ' . $pagination_type . ' found.');
		exit();
	}

    //--------------------
	//Produce next/previous links
	//--------------------

    $prev_link = '';

    if ($page > 0)
    {
        $prev_link = 'paginate.php?type=' . $pagination_type . '&page=' . ($page-1) . '&items=' . $items_per_page;
    }
    
    $next_link = 'paginate.php?type=' . $pagination_type . '&page=' . ($page+1) . '&items=' . $items_per_page;

    //--------------------
	//Check if enough array is left to slice
	//--------------------

    $total_items = count($session_data);
    $items_remaining = $total_items - ($page * $items_per_page);
    $items_to_show = $items_per_page; //Default

    if ($items_remaining == 0)
    {
        //If there are exactly no items left, just display the last page again.
        $page = $page - 1; 
        $next_link = '';
    } elseif ($items_remaining < 0)
    {
        //If items_remaining is less than zero (if the page variable was advanced too much),
        //return to the last page and its (reduced) items
        $page = floor($total_items / $items_per_page);
        $items_to_show = $total_items % $items_per_page;
        
        if ($items_to_show == 0)
        {
            $items_to_show = $items_per_page;
        }
        
        $next_link = '';
           
    } elseif ($items_remaining < $items_per_page)
    {
        $items_to_show = $items_remaining;
        $next_link = '';
    }

	//--------------------
	//Slice session data to size
	//--------------------

    $sliced_data = array_slice($session_data, ($page * $items_per_page), $items_to_show);


	//--------------------
	//Prepare template
	//--------------------

	if ($pagination_type == 'articles')
    {
        $page_template = new PHPTAL('templates/article_display.html');
        $page_template->article_stylesheet = 'style/articles.css';
        $page_template->articles = $sliced_data;
        $page_template->prev_link = $prev_link;
        $page_template->page = $page;
        $page_template->next_link = $next_link;
    }

	//--------------------
	//Execute template
	//--------------------

	try {
        header("HTTP/1.1 200 OK.");
		echo $page_template->execute();
	} catch (Exception $e) {
		echo $e;
	}

    exit();
	
?>

