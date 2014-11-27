<?php

namespace Surikat\Model\RedBeanPHP;

use Surikat\Model\RedBeanPHP\ToolBox as ToolBox;
use Surikat\Model\RedBeanPHP\OODB as OODB;
use Surikat\Model\RedBeanPHP\QueryWriter as QueryWriter;
use Surikat\Model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use Surikat\Model\RedBeanPHP\AssociationManager as AssociationManager;
use Surikat\Model\RedBeanPHP\TagManager as TagManager;
use Surikat\Model\RedBeanPHP\DuplicationManager as DuplicationManager;
use Surikat\Model\RedBeanPHP\LabelMaker as LabelMaker;
use Surikat\Model\RedBeanPHP\Finder as Finder;
use Surikat\Model\RedBeanPHP\RedException\SQL as SQL;
use Surikat\Model\RedBeanPHP\RedException\Security as Security;
use Surikat\Model\RedBeanPHP\Logger as Logger;
use Surikat\Model\RedBeanPHP\Logger\RDefault as RDefault;
use Surikat\Model\RedBeanPHP\Logger\RDefault\Debug as Debug;
use Surikat\Model\RedBeanPHP\OODBBean as OODBBean;
use Surikat\Model\RedBeanPHP\SimpleModel as SimpleModel;
use Surikat\Model\RedBeanPHP\SimpleModelHelper as SimpleModelHelper;
use Surikat\Model\RedBeanPHP\Adapter as Adapter;
use Surikat\Model\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use Surikat\Model\RedBeanPHP\RedException as RedException;
use Surikat\Model\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;
use Surikat\Model\RedBeanPHP\Driver\RPDO as RPDO;

class Facade{
	
	const C_REDBEANPHP_VERSION = '4.1-surikat-fork';
	public static $toolbox;
	public static $currentDB = 'default';
	public static $databases = [];
	static function getVersion(){
		return self::C_REDBEANPHP_VERSION;
	}
	
	public static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '' ){
		if ( isset( self::$databases[$key] ) )
			throw new RedException( 'A database has already be specified for this key.' );
		self::$databases[$key] = new Database($dsn, $user, $pass, $frozen, $prefix);
	}
	
	public static function selectDatabase( $key ){
		if ( self::$currentDB === $key )
			return FALSE;
		if ( !isset( self::$databases[$key] ) )
			throw new RedException( 'No database has been specified for this key : '.$key.'.' );

		self::configureFacadeWithToolbox( self::$databases[$key]->getToolBox() );
		self::$currentDB = $key;

		return TRUE;
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