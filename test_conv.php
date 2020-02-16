<?php

    require_once('db.php');

    $test = '09ED:0084:014E:011D';

    echo($test . '<br>');

    $portal = star_to_portal($test);

    echo($portal);

?>