<?php

include('database.php');
global $link_old;
global $link_new;

//checkMetaKeys();

$q = file_get_contents('sql/get_posts_old.sql');

$posts_result = $link_old->query($q) or die("Error in the consult.." . mysqli_error($link_old));

echo $q."<br>";

$count=0;

if ($_GET['verbose'] && $_GET['verbose'] == 1) {
	echo "<br>VERBOSE is ON. INSERT statements will not run!<br>";
}

while ($row=mysqli_fetch_array($posts_result)) {

	//if ($count < 2){

		$ID = $row["ID"];

		echo "<br>Processing Post with original ID: ".$ID."<br>";
		$newID = insertIntoNewDB($row,'');

		processPostMetaEntry($ID,$newID);

		getPostAttachments($ID,$newID);

		echo "---------------------------------------------<br>";

	    $count++;
	//}
}

echo "<br>Imported ".$count." post entries...<br>";
echo "---------------------------------------------<br>";
// echo "Beginning user import<br>";

// $count=0;

// $u = file_get_contents('sql/get_users_old.sql');

// $users_result = $link_old->query($u) or die("Error in the consult.." . mysqli_error($link_old));

// while ($row=mysqli_fetch_array($users_result)) {

// 	//if ($count < 2){

// 		$ID = $row["ID"];

// 		echo "<br>Processing user with original ID: ".$ID."<br>";
// 		$newID = insertUserIntoNewDB($row,'');

// 		processUserMetaEntry($ID,$newID);

// 		if ($ID != $newID) {
// 			echo "WARNING - ID UPDATED, NEED TO UPDATE ALL FOREIGN KEYS ON USERS POSTS";
// 			echo "user with original ID: ".$ID;
// 			//update any posts associated with the old ID
// 			// $up = file_get_contents('sql/get_user_posts_new.sql'); //this nees to be a string to accept the IDs

// 		}

// 		echo "---------------------------------------------<br>";

// 	    $count++;
// 	//}
// }

// echo "<br>Imported ".$count." user entries...<br>";
// echo "---------------------------------------------<br>";

?>
