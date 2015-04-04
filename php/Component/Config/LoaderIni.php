<?php namespace Surikat\Component\Config;
use Surikat\Component\Vars\Arrays;
use Surikat\Component\FileSystem\INI as FileINI;
class LoaderIni extends Loader{
	protected $extension = '.ini';
	function toString($contents){
		return FileINI::arrayToStr($contents);
	}
	protected function getConf($inc){
		return parse_ini_file($inc,true);
	}
}