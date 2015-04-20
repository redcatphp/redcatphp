<?php namespace Surikat\Component\Database;
use Surikat\Component\Database\RedBeanPHP\Facade;

use Surikat\Component\Database\RedBeanPHP\ToolBox;
use Surikat\Component\Database\RedBeanPHP\RedException;

use Surikat\Component\DependencyInjection\Container;
use Surikat\Component\DependencyInjection\MutatorPropertyTrait;
use Surikat\Component\DependencyInjection\FacadeTrait;
use Surikat\Component\Vars\STR;

use Surikat\Component\Vars\ArrayObject;

class R extends Facade{
	use MutatorPropertyTrait;
	use FacadeTrait;
	static function getConfigFilename($args){
		$name = 'db';
		if(is_array($args)&&!empty($args)){
			$key = array_shift($args);
			if(!empty($key))
				$name .= '.'.$key;
		}
		return $name;
	}
	function setConfig($config){
		$config = new ArrayObject($config);
		$type = $config->type;
		if(!$type)
			return;
		$port = $config->port;
		$host = $config->host;
		$file = $config->file;
		$name = $config->name;
		$prefix = $config->prefix;
		$case = $config->case;
		$frozen = $config->frozen;
		$user = $config->user;
		$password = $config->password;
		
		if($port)
			$port = ';port='.$port;
		if($host)
			$host = 'host='.$host;
		elseif($file)
			$host = $file;
		if($name)
			$name = ';dbname='.$name;
		if(!isset($frozen))
			$frozen = !Container::get()->Dev_Level->DB;
		if(!isset($case))
			$case = true;
		$dsn = $type.':'.$host.$port.$name;
		$this->construct($dsn, $user, $password, $frozen, $prefix, $case);
	}	

	static function nestBinding($sql,$binds){
		do{
			list($sql,$binds) = self::pointBindingLoop($sql,(array)$binds);
			list($sql,$binds) = self::nestBindingLoop($sql,(array)$binds);
			$containA = false;
			foreach($binds as $v)
				if($containA=is_array($v))
					break;
		}
		while($containA);
		return [$sql,$binds];
	}
	private static function pointBindingLoop($sql,$binds){
		$nBinds = [];
		foreach($binds as $k=>$v){
			if(is_integer($k))
				$nBinds[] = $v;
		}
		$i = 0;
		foreach($binds as $k=>$v){
			if(!is_integer($k)){
				$find = ':'.ltrim($k,':');
				while(false!==$p=strpos($sql,$find)){
					$preSql = substr($sql,0,$p);
					$sql = $preSql.'?'.substr($sql,$p+strlen($find));
					$c = count(explode('?',$preSql))-1;
					array_splice($nBinds,$c,0,[$v]);
				}
			}
			$i++;
		}
		return [$sql,$nBinds];
	}
	private static function nestBindingLoop($sql,$binds){
		$nBinds = [];
		$ln = 0;
		foreach($binds as $k=>$v){
			if(is_array($v)){
				$c = count($v);
				$av = array_values($v);
				if($ln)
					$p = strpos($sql,'?',$ln);
				else
					$p = STR::posnth($sql,'?',$k);
				if($p!==false){
					$nSql = substr($sql,0,$p);
					$nSql .= '('.implode(',',array_fill(0,$c,'?')).')';
					$ln = strlen($nSql);
					$nSql .= substr($sql,$p+1);
					$sql = $nSql;
					for($y=0;$y<$c;$y++)
						$nBinds[] = $av[$y];
				}
			}
			else{
				if($ln)
					$p = strpos($sql,'?',$ln);
				else
					$p = STR::posnth($sql,'?',$k);
				$ln = $p+1;
				$nBinds[] = $v;
			}
		}
		return [$sql,$nBinds];
	}
	
	//static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '', $case = true ){}
	//static function selectDatabase( $key ){}
}