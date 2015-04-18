<?php
namespace Surikat\Component\Database\RedBeanPHP;

use Surikat\Component\Database\RedBeanPHP\ToolBox as ToolBox;
use Surikat\Component\Database\RedBeanPHP\RedException as RedException;

use Surikat\Component\Vars\STR;
use Surikat\Component\DependencyInjection\Container;
use Surikat\Component\DependencyInjection\MutatorPropertyTrait;
use Surikat\Component\DependencyInjection\FacadeTrait;

class Facade{
	use MutatorPropertyTrait;
	use FacadeTrait;
	const C_REDBEANPHP_VERSION = '4.2-Surikat-Forked';
	public static $currentDB = 'default';
	public static $databases = [];
	static function getVersion(){
		return self::C_REDBEANPHP_VERSION;
	}
	public static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '', $case = true ){
		if ( isset( self::$databases[$key] ) )
			throw new RedException( 'A database has already be specified for this key.' );
		self::$databases[$key] = new Database($key, $dsn, $user, $pass, $frozen, $prefix, $case);
	}
	public static function selectDatabaseKey( $key ){
		if ( self::$currentDB !== $key ){
			if ( !isset( self::$databases[$key] ) )
				throw new RedException( 'No database has been specified for this key : '.$key.'.' );
			self::$currentDB = $key;
		}
		return self::$databases[$key];
	}
	public static function __callStatic( $func, $params ){
		if( !isset( self::$databases[self::$currentDB] ) ){
			throw new RedException('Setup database first using: R::setup()');
		}
		return call_user_func_array( [ self::$databases[self::$currentDB], $func ], $params );
	}
	public static function getInstance( $key = null ){
		if(!isset($key))
			$key = self::$currentDB;
		if( !isset( self::$databases[$key] ) ){
			throw new RedException('Undefined database');
		}
		return self::$databases[$key];
	}
	public static function setErrorHandlingFUSE( $mode, $func = NULL ){
		return OODBBean::setErrorHandlingFUSE( $mode, $func );
	}
	
	
	
	static function loadDB($key){
		$getter = 'db';
		if(!$key)
			$key = 'default';
		if($key!='default')
			$getter = $getter.'_'.implode('_',explode('/',$key));
			
		$config = Container::get()->Config($getter);
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
			$frozen = !Container::get('Dev\Level')->DB;
		if(!isset($case))
			$case = true;
		$dsn = $type.':'.$host.$port.$name;
		
		
		self::addDatabase($key,$dsn,$user,$password,$frozen,$prefix,$case);
		
		return true;
	}
	static function getDatabase($key=null){
		if(!$key)
			$key = 'default';
		if(!isset(self::$databases[$key]))
			self::loadDB($key);
		return self::getInstance($key);
	}
	static function selectDatabase($key){
		if(!$key)
			$key = 'default';
		if(!isset(self::$databases[$key]))
			self::loadDB($key);
		return self::selectDatabaseKey($key);
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
	
}