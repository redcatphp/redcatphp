<?php namespace surikat\model;
use surikat\control\Config;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
class R extends RedBeanPHP\Facade{
	static function initialize(){
		extract(Config::model());
		$port = isset($port)&&$port?';port='.$port:'';
		self::setup("$type:host=$host$port;dbname=$name",$user,$password,$frozen);
	}
	static function getModelClass($type){
		$prefix = defined('REDBEAN_MODEL_PREFIX')?constant('REDBEAN_MODEL_PREFIX'):'\\model\\Table_';
		return class_exists($c=$prefix.ucfirst($type))?$c:rtrim($prefix,'_');
	}
	static function findOrNewOne($type,$params=array(),$insert=null){
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
		if(!$bean){
			if(is_array($insert))
				$params = array_merge($params,$insert);
			$bean = R::newOne(array_pop($type),$params);
		}
		return $bean;
	}
	static function newOne($type,$params=array()){
		$bean = self::dispense($type);
		foreach((array)$params as $k=>$v)
			$bean->$k = $v;
		return $bean;
	}
	static function queryModelUpdateArgsAutowrap(&$insertSQL,$type,$insertcolumns,$insertvalues=null){
		//patched RedBeanPHP/QueryWriter/AQueryWriter.php
		if(func_num_args()<4){
			$updatevalues = $insertcolumns;
			$insertcolumns = $insertvalues = array();
			foreach ( $updatevalues as $pair ) {
				$insertcolumns[] = $pair['property'];
				$insertvalues[]  = $pair['value'];
			}
		}
		$c = self::getModelClass($type);
		foreach($insertvalues[0] as $i=>$v){
			$k = $insertcolumns[$i];
			$k = trim($k,'`');
			if(is_string($v)){
				if(isset($c::$metaCastWrap[$k]))
					self::queryWrapArg($insertSQL,$c::$metaCastWrap[$k],$i);
				if(isset($c::$metaCast[$k]))
					switch($c::$metaCast[$k]){
						case 'point':
							//https://groups.google.com/forum/#!topic/redbeanorm/jQTW2Oqvlqg
							//https://groups.google.com/forum/#!topic/redbeanorm/wz2lJCLuclE
							if(strpos($v,'POINT(')===0&&substr($v,-1)==')')
								self::queryWrapArg($insertSQL,'GeomFromText(?)',$i);
						break;
					}
			}
		}
	}
	static function queryWrapArg(&$query,$wrap,$arg){
		$x = explode('?',$query);
		$s = '';
		for($i=0;$i<count($x)-1;$i++)
			$s .= $x[$i].($arg===$i?$wrap:'?');
		$s .= array_pop($x);
		$query = $s;
		return $query;
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
						if($k3!='type')
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
