<?php

$targetpath = "uploads/";
$inputname = "upload";

$tempfile = $_FILES[$inputname]['tmp_name'];
$targetpath .= basename($_FILES[$inputname]['name']);

if( move_uploaded_file($tempfile, $targetpath) )
	echo $targetpath;
else
	echo "Error";

?>