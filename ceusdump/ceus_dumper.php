<?php
set_time_limit(0);
foreach( array('de','en') as $lang) {
if (file_put_contents ( "C:\Users\The User\Desktop\CEUS_dump\list_" . $lang . ".json", fopen ( "https://lss.jku.at/studienhandbuch/api/0.1/list.php?authtoken=mqsXtg5zwbJf6IKP&lang=" . $lang, 'r' ) ))
	echo '<p>Dumped list.php</p>';
$aCurricula = json_decode ( file_get_contents ( "C:\Users\The User\Desktop\CEUS_dump\list_" . $lang . ".json" ) );
foreach ( $aCurricula as $oCurriculum ) {
	if (file_put_contents ( "C:\Users\The User\Desktop\CEUS_dump\curr_" . $oCurriculum->id . "_" . $lang . ".json", fopen ( "https://lss.jku.at/studienhandbuch/api/0.1/curr.php?authtoken=mqsXtg5zwbJf6IKP&id=" . $oCurriculum->id. '&lang=' . $lang, 'r' ) ))
		echo '<p>Dumped curr.php for Curriculum ' . $oCurriculum->id . '</p>';
	else
		echo '<p><strong>Error with Curriculum ' . $oCurriculum->id . '</strong></p>';
	$aCourses = json_decode ( file_get_contents ( "C:\Users\The User\Desktop\CEUS_dump\curr_" . $oCurriculum->id . "_" . $lang . ".json" ) )->tree;
	foreach ( $aCourses as $oCourse ) {
		dump_rec ( $oCourse, $lang );
	}
}
}
function dump_rec($oCourse, $lang) {
	if (file_put_contents ( "C:\Users\The User\Desktop\CEUS_dump\detail_" . $oCourse->id . "_" . $lang . ".json", fopen ( "https://lss.jku.at/studienhandbuch/api/0.1/detail.php?authtoken=mqsXtg5zwbJf6IKP&id=" . $oCourse->id . '&lang=' . $lang,  'r' ) ))
		echo '<p>Dumped detail.php for Course ' . $oCourse->id . '</p>';
	else
		echo '<p><strong>Error with Course ' . $oCourse->id . '</strong></p>';
	foreach ( $oCourse->subtree as $oChild ) {
		dump_rec ( $oChild, $lang );
	}
}
?>