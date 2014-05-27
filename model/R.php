<?php namespace surikat\model;
use surikat\control\Config;
use surikat\model\RedBean\IModelFormatter;
use surikat\model\RedBean\ModelHelper;
class ModelFormatter implements IModelFormatter{
    function formatModel($model){
		return class_exists($c='model\\Table_'.ucfirst($model))?$c:'model\\Table';
    }
}
class R extends RedBean\Facade{
	static function initialize(){
		//defined('MODEL_PREFIX','model\\');
		ModelHelper::setModelFormatter(new ModelFormatter());
		extract(Config::model());
		$port = isset($port)&&$port?';port='.$port:'';
		self::setup("$type:host=$host$port;dbname=$name",$user,$password,$frozen);
	}
	static function findOrNewOne($type,$params=array()){
		$query = array();
		$bind = array();
		foreach($params as $k=>$v){
			$query[] = $k.'=?';
			$bind[] = $v;
		}
		$query = implode(' AND ',$query);
		$type = (array)$type;
		foreach($type as $t)
			if($bean = R::findOne($t,$query,$bind))
				break;
		if(!$bean)
			$bean = R::newOne(array_pop($type),$params);
		return $bean;
	}
	static function newOne($type,$params=array()){
		$bean = self::dispense($type);
		foreach((array)$params as $k=>$v)
			$bean->$k = $v;
		return $bean;
	}
	static function storeMultiArray(array $a){
		foreach($a as $v)
			self::storeArray($v);
	}
	static function storeArray(array $a){
		$dataO = self::dispense($a['type']);
		foreach($a as $k=>$v){
			if($k=='type')
				continue;
			if(stripos($k,'own')===0){
				foreach((array)$v as $v2){
					$type = lcfirst(substr($k,3));
					$own = self::dispense($type);
					foreach((array)$v2 as $k3=>$v3)
						$own->$k3 = $v3;
					$dataO->{$k}[] = $own;
				}
			}
			elseif(stripos($k,'shared')===0){
				foreach((array)$v as $v2){
					$type = lcfirst(substr($k,6));
					if(!is_integer(filter_var($v2, FILTER_VALIDATE_INT)))
						$v2 = self::__callStatic('cell',array($type,array('select'=>'id','where'=>'label=?'),array($v2)));
					if($v2)
						$dataO->{$k}[] = self::load($type,$v2);
				}
			}
			else
				$dataO->$k = $v;
		}
		return self::transaction(function()use(&$dataO){
			return self::store($dataO);
		});
	}
}
R::initialize();
?>
