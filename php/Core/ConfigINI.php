<?php namespace Surikat\Core;
use Surikat\Core\FileINI;
class ConfigINI extends Config {
	protected $extension = '.ini';
	protected function getConf($inc){
		return parse_ini_file($inc,true);
	}
	protected function getString(){
		return FileINI::arrayToStr($this->conf);
	}
}