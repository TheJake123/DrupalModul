<?php
header('Content-Type: application/json');
if (isset($_GET['lang']))
	$lang = $_GET['lang'];
else
	$lang = 'de';
echo file_get_contents ( dirname(__FILE__) . "\dump\list_" . $lang . ".json" );
?>