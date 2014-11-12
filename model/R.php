<?php namespace surikat\model;
use surikat\model;
use surikat\control;
use surikat\control\Config;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use surikat\model\RedBeanPHP\RedException;
use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter;
use surikat\model\Query4D;
class R extends RedBeanPHP\Facade{
	static function initialize(){
		$conf = Config::model();
		if(!$conf)
			return;
		extract($conf);
		if(!isset($frozen))
			$frozen = !control::devHas(control::dev_model);
		$port = isset($port)&&$port?';port='.$port:'';
		$prefix = isset($prefix)?$prefix:'';
		$host = $host?'host='.$host:(isset($file)?$file:'');
		$dbname = $name?';dbname='.$name:'';
		self::setup($type.':'.$host.$port.$dbname,$user,$password,$frozen,$prefix);
		if(control::devHas(control::dev_model_redbean))
			self::debug(true,2);
	}
}
R::initialize();