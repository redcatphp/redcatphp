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

class Database{
	private $toolbox;
	private $redbean;
	private $writer;
	private $adapter;
	private $associationManager;
	private $tagManager;
	private $duplicationManager;
	private $labelMaker;
	private $finder;
	private $plugins = [];
	private $exportCaseStyle = 'default';
	
	function __construct( $dsn = NULL, $username = NULL, $password = NULL, $frozen = FALSE, $prefix = '' ){
		
		$this->setup($dsn, $username, $password, $frozen, $prefix);
		
		$this->finder             = new Finder( $this->toolbox );
		$this->associationManager = new AssociationManager( $this->toolbox );
		$this->redbean->setAssociationManager( $this->associationManager );
		$this->labelMaker         = new LabelMaker( $this->toolbox );
		$helper                   = new SimpleModelHelper();
		$helper->attachEventListeners( $this->redbean );
		$this->redbean->setBeanHelper( new SimpleFacadeBeanHelper );
		$this->duplicationManager = new DuplicationManager( $this->toolbox );
		$this->tagManager         = new TagManager( $this->toolbox );
		
	}
	function setup( $dsn = NULL, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '' ){
		if ( is_object($dsn) ) {
			$db  = new RPDO( $dsn );
			$dbType = $db->getDatabaseType();
		} else {
			$dbType = substr( $dsn, 0, strpos( $dsn, ':' ) );
			if(is_numeric(substr($dbType,-1)))
				$dsn = substr($dsn,0,($l=strlen($dbType))-1).substr($dsn,$l);
			$db = new RPDO( $dsn, $user, $pass, TRUE );
		}
		$this->adapter = new DBAdapter( $db );
		$writers     = [
			'mysql'  => 'MySQL',
			'pgsql'  => 'PostgreSQL',
			'sqlite' => 'SQLiteT',
			'pgsql9'  => 'PostgreSQL',
			'pgsql8'  => 'PostgreSQL8BC',
			'cubrid' => 'CUBRID',
		];
		$wkey = trim( strtolower( $dbType ) );
		if ( !isset( $writers[$wkey] ) ) trigger_error( 'Unsupported DSN: '.$wkey );
		$writerClass = '\\surikat\\model\\RedBeanPHP\\QueryWriter\\'.$writers[$wkey];
		$this->writer      = new $writerClass( $this->adapter, $prefix );
		$this->redbean     = new OODB( $this->writer );
		$this->redbean->freeze( ( $frozen === TRUE ) );
		$this->toolbox = new ToolBox( $this->redbean, $this->adapter, $this->writer );
		return $this->toolbox;
	}
	
	private function query( $method, $sql, $bindings ){
		if ( !$this->redbean->isFrozen() ) {
			try {
				$rs = $this->adapter->$method( $sql, $bindings );
			} catch ( SQL $exception ) {
				if ( $this->writer->sqlStateIn( $exception->getSQLState(),
					[
						QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
						QueryWriter::C_SQLSTATE_NO_SUCH_TABLE ]
					)
				) {
					return ( $method === 'getCell' ) ? NULL : [];
				} else {
					throw $exception;
				}
			}

			return $rs;
		} else {
			return $this->adapter->$method( $sql, $bindings );
		}
	}
	function testConnection(){
		if ( !isset( $this->adapter ) ) return FALSE;

		$database = $this->adapter->getDatabase();
		try {
			@$database->connect();
		} catch ( \Exception $e ) {}
		return $database->isConnected();
	}
	
	function setNarrowFieldMode( $mode ){
		AQueryWriter::setNarrowFieldMode( $mode );
	}
	
	function transaction( $callback )
	{
		if ( !is_callable( $callback ) ) {
			throw new RedException( 'R::transaction needs a valid callback.' );
		}

		$depth = 0;
		$result = null;
		try {
			if ( $depth == 0 ) {
				$this->begin();
			}
			$depth++;
			$result = call_user_func( $callback ); //maintain 5.2 compatibility
			$depth--;
			if ( $depth == 0 ) {
				$this->commit();
			}
		} catch (\Exception $exception ) {
			$depth--;
			if ( $depth == 0 ) {
				$this->rollback();
			}
			throw $exception;
		}
		return $result;
	}

	function debug( $tf = TRUE, $mode = 0 ){
		if ($mode > 1) {
			$mode -= 2;
			$logger = new Debug;
		} else {
			$logger = new RDefault;
		}

		if ( !isset( $this->adapter ) ) {
			throw new RedException( 'Use R::setup() first.' );
		}
		$logger->setMode($mode);
		$this->adapter->getDatabase()->setDebugMode( $tf, $logger );

		return $logger;
	}

	function inspect( $type = NULL ){
		return ($type === NULL) ? $this->writer->getTables() : $this->writer->getColumns( $type );
	}

	function store( $bean ){
		return $this->redbean->store( $bean );
	}

	function freeze( $tf = TRUE ){
		$this->redbean->freeze( $tf );
	}

	function loadMulti( $types, $id ){
		if ( is_string( $types ) ) {
			$types = explode( ',', $types );
		}

		if ( !is_array( $types ) ) {
			return [];
		}

		foreach ( $types as $k => $typeItem ) {
			$types[$k] = $this->redbean->load( $typeItem, $id );
		}

		return $types;
	}
	
	function load( $type, $id ){
		return $this->redbean->load( $type, $id );
	}

	function trash( $bean ){
		$this->redbean->trash( $bean );
	}

	function dispense( $typeOrBeanArray, $num = 1, $alwaysReturnArray = FALSE ){
		if ( is_array($typeOrBeanArray) ) {
			if ( !isset( $typeOrBeanArray['_type'] ) ) throw new RedException('Missing _type field.');
			$import = $typeOrBeanArray;
			$type = $import['_type'];
			unset( $import['_type'] );
		} else {
			$type = $typeOrBeanArray;
		}

		if ( !preg_match( '/^[a-z0-9]+$/', $type ) ) {
			throw new RedException( 'Invalid type: ' . $type );
		}

		$beanOrBeans = $this->redbean->dispense( $type, $num, $alwaysReturnArray );

		if ( isset( $import ) ) {
			$beanOrBeans->import( $import );
		}

		return $beanOrBeans;
	}

	function dispenseAll( $order, $onlyArrays = FALSE )
	{

		$list = [];

		foreach( explode( ',', $order ) as $order ) {
			if ( strpos( $order, '*' ) !== false ) {
				list( $type, $amount ) = explode( '*', $order );
			} else {
				$type   = $order;
				$amount = 1;
			}

			$list[] = $this->dispense( $type, $amount, $onlyArrays );
		}

		return $list;
	}

	function findOrDispense( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findOrDispense( $type, $sql, $bindings );
	}

	function find( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->find( $type, $sql, $bindings );
	}

	function findAll( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->find( $type, $sql, $bindings );
	}

	function findAndExport( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findAndExport( $type, $sql, $bindings );
	}

	function findOne( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findOne( $type, $sql, $bindings );
	}

	function findLast( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findLast( $type, $sql, $bindings );
	}

	function batch( $type, $ids )
	{
		return $this->redbean->batch( $type, $ids );
	}

	function loadAll( $type, $ids )
	{
		return $this->redbean->batch( $type, $ids );
	}

	function exec( $sql, $bindings = [] )
	{
		return $this->query( 'exec', $sql, $bindings );
	}

	function getAll( $sql, $bindings = [] )
	{
		return $this->query( 'get', $sql, $bindings );
	}

	function getCell( $sql, $bindings = [] )
	{
		return $this->query( 'getCell', $sql, $bindings );
	}

	function getRow( $sql, $bindings = [] )
	{
		return $this->query( 'getRow', $sql, $bindings );
	}

	function getCol( $sql, $bindings = [] )
	{
		return $this->query( 'getCol', $sql, $bindings );
	}

	function getAssoc( $sql, $bindings = [] )
	{
		return $this->query( 'getAssoc', $sql, $bindings );
	}

	function getAssocRow( $sql, $bindings = [] )
	{
		return $this->query( 'getAssocRow', $sql, $bindings );
	}

	function duplicate( $bean, $filters = array() ){
		return $this->dup( $bean, array(), FALSE, $filters );
	}
	
	function exportAll( $beans, $parents = FALSE, $filters = [])
	{
		return $this->duplicationManager->exportAll( $beans, $parents, $filters, $this->exportCaseStyle );
	}

	function useExportCase( $caseStyle = 'default' )
	{
		if ( !in_array( $caseStyle, [ 'default', 'camel', 'dolphin' ] ) ) throw new RedException( 'Invalid case selected.' );
		$this->exportCaseStyle = $caseStyle;
	}

	function convertToBeans( $type, $rows )
	{
		return $this->redbean->convertToBeans( $type, $rows );
	}

	function hasTag( $bean, $tags, $all = FALSE )
	{
		return $this->tagManager->hasTag( $bean, $tags, $all );
	}

	function untag( $bean, $tagList )
	{
		$this->tagManager->untag( $bean, $tagList );
	}

	function tag( OODBBean $bean, $tagList = NULL )
	{
		return $this->tagManager->tag( $bean, $tagList );
	}

	function addTags( OODBBean $bean, $tagList )
	{
		$this->tagManager->addTags( $bean, $tagList );
	}

	function tagged( $beanType, $tagList, $sql = '', $bindings = [] )
	{
		return $this->tagManager->tagged( $beanType, $tagList, $sql, $bindings );
	}

	function taggedAll( $beanType, $tagList, $sql = '', $bindings = [] )
	{
		return $this->tagManager->taggedAll( $beanType, $tagList, $sql, $bindings );
	}

	function wipe( $beanType )
	{
		return $this->redbean->wipe( $beanType );
	}

	function count( $type, $addSQL = '', $bindings = [] )
	{
		return $this->redbean->count( $type, $addSQL, $bindings );
	}
	
	function begin(){
		if ( !$this->redbean->isFrozen() ) return FALSE;
		$this->adapter->startTransaction();
		return TRUE;
	}

	function commit(){
		if ( !$this->redbean->isFrozen() ) return FALSE;
		$this->adapter->commit();
		return TRUE;
	}

	function rollback(){
		if ( !$this->redbean->isFrozen() ) return FALSE;
		$this->adapter->rollback();
		return TRUE;
	}

	function getColumns( $table ){
		return $this->writer->getColumns( $table );
	}

	function genSlots( $array ){
		return ( count( $array ) ) ? implode( ',', array_fill( 0, count( $array ), '?' ) ) : '';
	}

	function nuke(){
		if ( !$this->redbean->isFrozen() ) {
			$this->writer->wipeAll();
		}
	}

	function storeAll( $beans )
	{
		$ids = [];
		foreach ( $beans as $bean ) {
			$ids[] = $this->store( $bean );
		}

		return $ids;
	}

	function trashAll( $beans )
	{
		foreach ( $beans as $bean ) {
			$this->trash( $bean );
		}
	}

	function useWriterCache( $yesNo )
	{
		$this->getWriter()->setUseCache( $yesNo );
	}

	function dispenseLabels( $type, $labels )
	{
		return $this->labelMaker->dispenseLabels( $type, $labels );
	}

	function enum( $enum )
	{
		return $this->labelMaker->enum( $enum );
	}

	function gatherLabels( $beans )
	{
		return $this->labelMaker->gatherLabels( $beans );
	}

	function close()
	{
		if ( isset( $this->adapter ) ) {
			$this->adapter->close();
		}
	}

	function isoDate( $time = NULL )
	{
		if ( !$time ) {
			$time = time();
		}

		return @date( 'Y-m-d', $time );
	}

	function isoDateTime( $time = NULL )
	{
		if ( !$time ) $time = time();

		return @date( 'Y-m-d H:i:s', $time );
	}

	function setDatabaseAdapter( Adapter $adapter )
	{
		$this->adapter = $adapter;
	}

	function setWriter( QueryWriter $writer )
	{
		$this->writer = $writer;
	}

	function setRedBean( OODB $redbean )
	{
		$this->redbean = $redbean;
	}

	function getDatabaseAdapter()
	{
		return $this->adapter;
	}

	function getDuplicationManager()
	{
		return $this->duplicationManager;
	}

	function getWriter()
	{
		return $this->writer;
	}

	function getRedBean(){
		return $this->redbean;
	}

	function getToolBox(){
		return $this->toolbox;
	}

	function getExtractedToolbox(){
		return [
			$this->redbean,
			$this->adapter,
			$this->writer,
			$this->toolbox
		];
	}

	function renameAssociation( $from, $to = NULL )
	{
		AQueryWriter::renameAssociation( $from, $to );
	}

	function beansToArray( $beans )
	{
		$list = [];
		foreach( $beans as $bean ) {
			$list[] = $bean->export();
		}
		return $list;
	}
	function dup( $bean, $trail = [], $pid = FALSE, $filters = [] )
	{
		$this->duplicationManager->setFilters( $filters );
		return $this->duplicationManager->dup( $bean, $trail, $pid );
	}
	function dump( $data )
	{
		$array = [];

		if ( $data instanceof OODBBean ) {
			$str = strval( $data );
			if (strlen($str) > 35) {
				$beanStr = substr( $str, 0, 35 ).'... ';
			} else {
				$beanStr = $str;
			}
			return $beanStr;
		}

		if ( is_array( $data ) ) {
			foreach( $data as $key => $item ) {
				$array[$key] = $this->dump( $item );
			}
		}
		return $array;
	}

	function bindFunc( $mode, $field, $function ) {
		$this->redbean->bindFunc( $mode, $field, $function );
	}

	function ext( $pluginName, $callable ){
		if ( !ctype_alnum( $pluginName ) ) {
			throw new RedException( 'Plugin name may only contain alphanumeric characters.' );
		}
		$this->plugins[$pluginName] = $callable;
	}
	function __call( $pluginName, $params ){
		if ( !ctype_alnum( $pluginName) ) {
			throw new RedException( 'Plugin name may only contain alphanumeric characters.' );
		}
		if ( !isset( $this->plugins[$pluginName] ) ) {
			throw new RedException( 'Plugin \''.$pluginName.'\' does not exist, add this plugin using: R::ext(\''.$pluginName.'\')' );
		}
		return call_user_func_array( $this->plugins[$pluginName], $params );
	}	
}