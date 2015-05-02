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

function processPostMetaEntry($ID,$newID,$imagePostID) {

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
		$meta_key = str_replace('location', 'city', $meta_key);
		$meta_key = str_replace('video', 'videos', $meta_key);
		$meta_key = str_replace('sqft', 'area', $meta_key);

		if ($meta_key == '_zoner_videos') {
			$meta_row[3] = 'a:1:{i:0;a:1:{s:17:"_zoner_link_video";s:34:"'.$meta_row["meta_value"].'";}}';
			$meta_row[3] = str_replace('https://','//player.',$meta_row[3]);
		}

		if ($meta_key == '_thumbnail_id') {
			$meta_row[3] = $imagePostID;
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

	if ($_GET['debug']) {
		echo "<br>".$q."<br><br>";
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

		echo $row['ID'];
		$imagePostID = insertIntoNewDB($row,$newID);

		echo "<br>Process attachment post metas for attachment post with new ID ".$imagePostID."...";
		processPostMetaEntry($row['ID'],$imagePostID);

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

		if ($_GET['debug']) {
			echo "<br>".$q."<br>";
		}
	}
	return $imagePostID;
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

function processUserMetaEntry($ID, $newID) {
	global $link_old;
	global $link_new;

	$q = "SELECT
			*
		FROM
			`gcdlh_usermeta`
		WHERE
			user_id = $ID
		";

		echo "<br><br>Getting usermeta for original user ID: ".$ID."<br>";
	$result = $link_old->query($q) or die("Error in the consult.." . mysqli_error($link_old) . "<br>" . $q);
	$count=0;

	while ($meta_row=mysqli_fetch_array($result)) {

		// $meta_key = str_replace('am_', '_zoner_', $meta_row[2]);
		$meta_row[2] = str_replace('phone', '_zoner_tel', $meta_row[2]);
		$meta_row[2] = str_replace('phone2', '_zoner_mob', $meta_row[2]);
		$meta_row[2] = str_replace('street', '_zoner_street', $meta_row[2]);
		// $meta_row[2] = str_replace('street2', '_zoner_street2', $meta_row[2]);
		$meta_row[2] = str_replace('city', '_zoner_city', $meta_row[2]);
		$meta_row[2] = str_replace('state', '_zoner_state', $meta_row[2]);
		$meta_row[2] = str_replace('zip', '_zoner_zip', $meta_row[2]);
		$meta_row[2] = str_replace('cava', '_zoner_company_image', $meta_row[2]);
		$meta_row[2] = str_replace('ava', '_zoner_avatar', $meta_row[2]);
		$meta_row[2] = str_replace('gcdlh_capabilities', 'hjwp_capabilities', $meta_row[2]);

//update user permissions
		if ($meta_row['3'] == 'a:1:{s:7:"realtor";b:1;}'||
				$meta_row['3'] == 'a:1:{s:7:"builder";b:1;}'||
				$meta_row['3'] == 'a:1:{s:5:\"owner\";b:1;}'||
				$meta_row['3'] == 'a:1:{s:5:"owner";b:1;}' ){
						$meta_row['3'] = 'a:1:{s:5:"agent";b:1;}';
		}

		if ($meta_row[0] < 35) {
			$meta_row[0] +=100;
		}

		$q="INSERT INTO `hjwp_usermeta`
			(
				`umeta_id`,
				`user_id`,
				`meta_key`,
				`meta_value`
			)
			VALUES (
				$meta_row[0],
				'$newID',
				'$meta_row[2]',
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
	echo "<br><br>Processed ".$count." usermeta entries for User ID: ".$newID."<br><br>";
}

function insertUserIntoNewDB($data,$parentID) {

	global $link_new;

	// var_dump($data);
	echo $data[0];

	$q = "INSERT INTO `hjwp_users`
		(
			`ID`,
			`user_login`,
			`user_pass`,
			`user_nicename`,
			`user_email`,
			`user_url`,
			`user_registered`,
			`user_activation_key`,
			`user_status`,
			`display_name`
		) VALUES (
			'$data[0]',
			'$data[1]',
			'$data[2]',
			'".addslashes($data[3])."',
			'$data[4]',
			'$data[5]',
			'$data[6]',
			'$data[7]',
			'$data[8]',
			'".addslashes($data[9])."'
		)
	";

	if (!$_GET['verbose'] || $_GET['verbose'] == 0) {
		echo "Running user INSERT query...";
		$result = $link_new->query($q) or die("Error in the consult.." . mysqli_error($link_new) . "<br>" . $q);
	}

	$q = "SELECT
			ID
		FROM
			`hjwp_users`
		ORDER by
			ID DESC
		";

	$result = $link_new->query($q) or die("Error in the consult.." . mysqli_error($link_new) . "<br>" . $q);
	$ID = (mysqli_fetch_array($result)["ID"]);

	echo "Inserting user with new ID ".$data[0]." original ID: ".$data[0];

	return $data[0];
}

?>
