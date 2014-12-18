<?php namespace Surikat\Core;
class ConfigINI extends Config {
	protected $extension = '.ini';
	protected function getConf($inc){
		return parse_ini_file($inc,true);
	}
}