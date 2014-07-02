<?php
header('Content-Type: application/json');
if (isset($_GET['lang']))
	$lang = $_GET['lang'];
else
	$lang = 'de';
if (isset($_GET['id'])) {
	$id = $_GET['id'];
	if (file_exists(dirname(__FILE__) . "\dump\detail_" . $id . "_" . $lang .".json"))
		echo file_get_contents ( dirname(__FILE__) . "\dump\detail_" . $id . "_" . $lang .".json" );
	else
		echo json_encode(array('error' => 'id not allowed'));

} else {
	echo json_encode(array('error' => 'no id or code parameter'));
}
?>