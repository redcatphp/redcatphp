<?php
if(!defined('SURIKAT_LINK'))
	define('SURIKAT_LINK','Surikat/');

if(!defined('SURIKAT_PATH'))
	define('SURIKAT_PATH',getcwd().'/');

if(!defined('SURIKAT_SPATH'))
	define('SURIKAT_SPATH',realpath(__DIR__.'/..').'/');

if(!defined('SURIKAT_TMP'))
	define('SURIKAT_TMP',SURIKAT_PATH.'.tmp/');