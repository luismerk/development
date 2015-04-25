<?php

include('config.php');
/**
 * Connect to the mysql database.
 */

// Connect to OLD database
$link_old = connectToDB($host1, $port1, $user1, $password1, $db1);

// Connect to NEW database
$link_new = connectToDB($host2, $port2, $user2, $password2, $db2);

?>
