<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $html->charset(); ?>
	<title><?php echo $title_for_layout; ?></title>
	<?php
	echo $html->css('cake.generic');
	echo $scripts_for_layout;
	?>
</head>
<body><?php echo $content_for_layout; ?></body>
</html>
