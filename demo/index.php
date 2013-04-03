<?php
require_once '../class.tpl.php';

$tpl = new tpl();
$tpl->assign(array(
		'hello' => 'Hello',
		'world' => 'World'
		));

$tpl->display('index.tpl');
?>
