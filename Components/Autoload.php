<?php
$dirs = array(ROOT.'/Components/');
foreach ($dirs as $dir) {
	$files = array_diff(scandir($dir), array('.', '..'));
	foreach ($files as $file) {
		require_once $dir.$file;
	}
}
