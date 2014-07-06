<?php
$sCurrType = '';
$aTaxonomyTypes = 'curriculum'; /*
                                 * array( 'curriculum', 'itsv', 'specialisation' );
                                 */
$sLang = 'de';
if (isset ( $_GET ['currtype'] ))
	$sCurrType = $_GET ['currtype'];
if (isset ( $_GET ['taxtypes'] ))
	$aTaxonomyTypes = $_GET ['taxtypes'];
if (isset ( $_GET ['lang'] ))
	$sLang = $_GET ['lang'];
?>
<!DOCTYPE html>
<html>
<head>

<script src="http://code.jquery.com/jquery-2.1.0.min.js"></script>
<script src="http://code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
<link rel="stylesheet" type="text/css" href="css/curriculum_style.css">
<script src="js/graph.js"></script>
</head>
<body>
	<div id="curriculum_display" data-currtype="<?php echo $sCurrType?>"
		data-curriculums="<?php echo $aTaxonomyTypes?>"></div>
</body>
</html>