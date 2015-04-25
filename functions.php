<?php

function connectToDB($host, $port, $user, $password, $db) {

	$link = mysqli_connect(
		"$host", 
		$user, 
		$password,
		$db
	);

	// Check connection
	if (mysqli_connect_errno())
	{
		echo "Failed to connect to MySQL: " . mysqli_connect_error();
	}
	else
		{
		echo "Successfully connected to database: ".$db." on host ".$host."<br><br>";
	}

	return $link;
}

function processPostMetaEntry($ID,$newID) {

	global $link_old;
	global $link_new;

	$q = "SELECT 
			* 
		FROM  
			`gcdlh_postmeta`
		WHERE
			post_id = $ID
		";


	echo "<br><br>Getting postmeta for original post ID: ".$ID."<br>";
	$result = $link_old->query($q) or die("Error in the consult.." . mysqli_error($link_old) . "<br>" . $q);
	$count=0;

	while ($meta_row=mysqli_fetch_array($result)) {

		$meta_key = str_replace('am_', '_zoner_', $meta_row[2]);
		$meta_key = str_replace('bathrooms', 'baths', $meta_key);
		$meta_key = str_replace('location', 'geo_location', $meta_key);
		$meta_key = str_replace('video', 'videos', $meta_key);
		$meta_key = str_replace('sqft', 'area', $meta_key);

//player.vimeo.com/video/122471751
		if ($meta_key == '_zoner_videos') {
			$meta_row[3] = 'a:1:{i:0;a:1:{s:17:"_zoner_link_video";s:34:"'.$meta_row["meta_value"].'";}}';
			$meta_row[3] = str_replace('https://','//player.',$meta_row[3]);
		}

		$q="INSERT INTO `hjwp_postmeta` 
			(
				`meta_id`,
				`post_id`,
				`meta_key`,
				`meta_value`
			) 
			VALUES (
				NULL,
				'$newID',
				'$meta_key',
				'".addslashes($meta_row[3])."'
			)";
		if (!$_GET['verbose'] || $_GET['verbose'] == 0) {
			echo "Running meta INSERT query...";
			$link_new->query($q) or die("Error in the consult.." . mysqli_error($link_new) . "<br>" . $q);
		}

		if ($_GET['debug']) {
			echo "<br>".$q;		
		}


		$count++;
	}
	echo "<br><br>Processed ".$count." postmeta entries for New post ID: ".$newID."<br><br>";

}

function insertIntoNewDB($data,$parentID) {

	global $link_new;

	if ($data["post_type"] == "post") {
		$post_type = "property";
		$post_parent = $data["post_parent"];
	} 
	else {
		$post_type = $data["post_type"];
		$post_parent = $parentID;
	}

	$q = "INSERT INTO `hjwp_posts` 
		(
			`ID`, 
			`post_author`, 
			`post_date`, 
			`post_date_gmt`, 
			`post_content`, 
			`post_title`, 
			`post_excerpt`, 
			`post_status`, 
			`comment_status`, 
			`ping_status`, 
			`post_password`, 
			`post_name`, 
			`to_ping`, 
			`pinged`, 
			`post_modified`, 
			`post_modified_gmt`, 
			`post_content_filtered`, 
			`post_parent`, 
			`guid`, 
			`menu_order`, 
			`post_type`, 
			`post_mime_type`, 
			`comment_count`
		) VALUES (
			NULL, 
			'$data[1]', 
			'$data[2]', 
			'$data[3]', 
			'".addslashes($data[4])."', 
			'".addslashes($data[5])."', 
			'$data[6]', 
			'$data[7]', 
			'$data[8]', 
			'$data[9]', 
			'$data[10]', 
			'$data[11]', 
			'$data[12]', 
			'$data[13]', 
			'$data[14]', 
			'$data[15]', 
			'$data[16]', 
			'$post_parent', 
			'$data[18]', 
			'$data[19]', 
			'$post_type', 
			'$data[21]', 
			'$data[22]'
		)
	";

	if (!$_GET['verbose'] || $_GET['verbose'] == 0) {
		echo "Running post INSERT query...";
		$result = $link_new->query($q) or die("Error in the consult.." . mysqli_error($link_new) . "<br>" . $q);
	}

	$q = "SELECT
			ID
		FROM
			`hjwp_posts`
		ORDER by
			ID DESC
		";

	$result = $link_new->query($q) or die("Error in the consult.." . mysqli_error($link_new) . "<br>" . $q);
	$ID = (mysqli_fetch_array($result)["ID"]);

	echo "Inserting post with new ID ".$ID." original ID: ".$data[0]." from ".$data[2]." by authorID: ".$data[1]." of type ".$post_type."<br>";

	return $ID;
//mysqli_query($q,$link_new);
}

function getPostAttachments($ID,$newID) {

	global $link_old;
	global $link_new;

	$q = "SELECT
			* 
		FROM `gcdlh_posts` 
		WHERE 
			`post_type` LIKE 'attachment'
			AND
			`post_parent` = $ID
		ORDER BY `post_date` DESC
		limit 1
		";


	$posts_result = $link_old->query($q) or die("Error in the consult.." . mysqli_error($link_old));

	while ($row=mysqli_fetch_array($posts_result)) {
		//var_dump($row);
		insertIntoNewDB($row,$newID);
		
		$q="INSERT INTO `hjwp_postmeta` 
			(
				`meta_id`,
				`post_id`,
				`meta_key`,
				`meta_value`
			) 
			VALUES (
				NULL,
				'$newID',
				'_wp_attached_file',
				'".addslashes($row['guid'])."'
			)";

		if (!$_GET['verbose'] || $_GET['verbose'] == 0) {
			echo "Running meta INSERT query...";
			$link_new->query($q) or die("Error in the consult.." . mysqli_error($link_new) . "<br>" . $q);
		}

	}

}

function checkMetaKeys() {

	global $link_old;
	global $link_new;

	$q = file_get_contents('sql/get_postmeta_old.sql');
	$test_result = $link_old->query($q) or die("Error in the consult.." . mysqli_error($link_old));

	while ($row=mysqli_fetch_array($test_result)) {
		echo $row["meta_key"]."<br>";
	}

	$q = file_get_contents('sql/get_postmeta_new.sql');
	$test_result = $link_new->query($q) or die("Error in the consult.." . mysqli_error($link_new));

	while ($row=mysqli_fetch_array($test_result)) {
		echo $row["meta_key"]."<br>";
	}

}


?>
