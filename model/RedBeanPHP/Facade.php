<?php

namespace surikat\model\RedBeanPHP;

use surikat\model\RedBeanPHP\ToolBox as ToolBox;
use surikat\model\RedBeanPHP\OODB as OODB;
use surikat\model\RedBeanPHP\QueryWriter as QueryWriter;
use surikat\model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use surikat\model\RedBeanPHP\AssociationManager as AssociationManager;
use surikat\model\RedBeanPHP\TagManager as TagManager;
use surikat\model\RedBeanPHP\DuplicationManager as DuplicationManager;
use surikat\model\RedBeanPHP\LabelMaker as LabelMaker;
use surikat\model\RedBeanPHP\Finder as Finder;
use surikat\model\RedBeanPHP\RedException\SQL as SQL;
use surikat\model\RedBeanPHP\RedException\Security as Security;
use surikat\model\RedBeanPHP\Logger as Logger;
use surikat\model\RedBeanPHP\Logger\RDefault as RDefault;
use surikat\model\RedBeanPHP\Logger\RDefault\Debug as Debug;
use surikat\model\RedBeanPHP\OODBBean as OODBBean;
use surikat\model\RedBeanPHP\SimpleModel as SimpleModel;
use surikat\model\RedBeanPHP\SimpleModelHelper as SimpleModelHelper;
use surikat\model\RedBeanPHP\Adapter as Adapter;
use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use surikat\model\RedBeanPHP\RedException as RedException;
use surikat\model\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;
use surikat\model\RedBeanPHP\Driver\RPDO as RPDO;

class Facade{
	
	const C_REDBEANPHP_VERSION = '4.1-surikat-fork';
	private static $toolbox;
	private static $databases = [];
	private static $currentDB = '';
	static function getVersion(){
		return self::C_REDBEANPHP_VERSION;
	}

	public static function setup( $dsn = NULL, $username = NULL, $password = NULL, $frozen = FALSE, $prefix = '' )
	{
		if ( is_null( $dsn ) ) {
			$dsn = 'sqlite:/' . sys_get_temp_dir() . '/red.db';
		}

		self::addDatabase( 'default', $dsn, $username, $password, $frozen, $prefix );
		self::selectDatabase( 'default' );

		return self::$toolbox;
	}
	
	public static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '' )
	{
		
		if ( isset( self::$databases[$key] ) ) {
			throw new RedException( 'A database has already be specified for this key.' );
		}
		self::$databases[$key] = new Database($dsn, $user, $pass, $frozen, $prefix);
	}
	
	public static function selectDatabase( $key )
	{
		if ( self::$currentDB === $key ) {
			return FALSE;
		}

		self::configureFacadeWithToolbox( self::$databases[$key]->getToolBox() );
		self::$currentDB = $key;

		return TRUE;
	}

	
	public static function configureFacadeWithToolbox( ToolBox $tb )
	{
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