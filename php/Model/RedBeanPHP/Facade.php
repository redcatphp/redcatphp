<?php
namespace Surikat\Model\RedBeanPHP;
use Surikat\Model\RedBeanPHP\ToolBox as ToolBox;
use Surikat\Model\RedBeanPHP\RedException as RedException;
class Facade{
	const C_REDBEANPHP_VERSION = '4.1.1-Surikat-Forked';
	public static $toolbox;
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
	public static function selectDatabase( $key ){
		if ( self::$currentDB === $key )
			return false;
		if ( !isset( self::$databases[$key] ) )
			throw new RedException( 'No database has been specified for this key : '.$key.'.' );
		self::configureFacadeWithToolbox( self::$databases[$key]->getToolBox() );
		self::$currentDB = $key;
		return true;
	}
	public static function configureFacadeWithToolbox( ToolBox $tb ){
		$oldTools                 = self::$toolbox;
		self::$toolbox            = $tb;
		return $oldTools;
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
}