<?php
$deps = array(
'/media/docs/prog/www/tools/php/phpMyTemplate/demo/templates/index.tpl' => '4f22288ba229a8d96000982484809e5a',
'/media/docs/prog/www/tools/php/phpMyTemplate/demo/templates/layout.tpl' => 'de3e53aedd38436fe69d691a4353b8e4',
);
?>
<?php
$function = 'tpl6860e67e05edb97cc9aa1d5068dbbf00';
?>
<?php function tpl6860e67e05edb97cc9aa1d5068dbbf00($_tpl) { ?>
<!DOCTYPE html>
<html>
<head>
	<title>Demo</title>
</head>
<body>
	<a href="index.php">index</a>
	<a href="index2.php">index2</a>
	<br />
	
	<?php tpltools::parseVar(@$_tpl->vars["hello"], '', "echo", $_tpl); ?> <?php tpltools::parseVar(@$_tpl->vars["world"], '', "echo", $_tpl); ?>!

</body>
</html>

<?php } ?>
