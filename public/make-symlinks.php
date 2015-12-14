<?php
foreach([
	'redcat'=>'../redcat',
	'shared'=>'redcat/shared',
	'artist'=>'redcat/artist',
] as $link=>$target)
	if(!file_exists($link)&&file_exists($target))
		symlink($target,$link);