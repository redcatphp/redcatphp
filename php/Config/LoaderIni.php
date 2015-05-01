<?php namespace Config;
use Vars\Arrays;
use FileSystem\INI as FileINI;
class LoaderIni extends Loader{
	protected $extension = '.ini';
	function toString($contents){
		return FileINI::arrayToStr($contents);
	}
	protected function getConf($inc){
		return parse_ini_file($inc,true);
	}
}