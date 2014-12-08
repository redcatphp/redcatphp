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
use Surikat\Model\RedBeanPHP\BeanHelper\SimpleBeanHelper as SimpleBeanHelper;
use Surikat\Model\RedBeanPHP\Driver\RPDO as RPDO;

use Surikat\Model\Table;
use Surikat\Model\Query;

use Surikat\Model\RedBeanPHP\Plugin\Preloader;
use Surikat\Model\RedBeanPHP\Plugin\Cooker;

class Database{
	private $name;
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
	
	function __construct( $name = 'default', $dsn = null, $username = null, $password = null, $frozen = false, $prefix = '', $case = true ){
		
		$this->name = $name;
		
		$this->setup($dsn, $username, $password, $frozen, $prefix, $case);
		
		$this->finder             = new Finder( $this->toolbox );
		$this->associationManager = new AssociationManager( $this->toolbox );
		$this->redbean->setAssociationManager( $this->associationManager );
		$this->labelMaker         = new LabelMaker( $this->toolbox );
		$helper                   = new SimpleModelHelper();
		$helper->attachEventListeners( $this->redbean );
		$this->redbean->setBeanHelper( new SimpleBeanHelper($this) );
		$this->duplicationManager = new DuplicationManager( $this->toolbox );
		$this->tagManager         = new TagManager( $this->toolbox );
		
	}
	function getName(){
		return $this->name;
	}
	function setup( $dsn = NULL, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '', $case = true ){
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
			'pgsql9' => 'PostgreSQL',
			'pgsql8' => 'PostgreSQL8BC',
			'sqlsrv' => 'SQLServer',
			'cubrid' => 'CUBRID',
		];
		$wkey = trim( strtolower( $dbType ) );
		if ( !isset( $writers[$wkey] ) ) trigger_error( 'Unsupported DSN: '.$wkey );
		$writerClass = '\\Surikat\\Model\\RedBeanPHP\\QueryWriter\\'.$writers[$wkey];
		$this->writer      = new $writerClass( $this->adapter, $this, $prefix, $case );
		$this->redbean     = new OODB( $this->writer );
		$this->redbean->freeze( ( $frozen === TRUE ) );
		$this->toolbox = new ToolBox( $this->redbean, $this->adapter, $this->writer, $this );
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
		return ($type === NULL) ? $this->writer->_getTables() : $this->writer->getColumns( $this->writer->adaptCase($type) );
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
			$types[$k] = $this->load( $typeItem, $id );
		}

		return $types;
	}

	function trash( $bean ){
		$this->redbean->trash( $bean );
	}

	function dispense($typeOrBeanArray,$num=1,$alwaysReturnArray=false){
		if(is_array($typeOrBeanArray)){
			if (!isset( $typeOrBeanArray['_type'] ) )
				throw new RedException('Missing _type field.');
			$import = $typeOrBeanArray;
			$type = $import['_type'];
			unset( $import['_type'] );
		}else
			$type = $typeOrBeanArray;
		if(!ctype_alnum($type))
			throw new RedException('Invalid type: '.$type);
		$type = $this->writer->adaptCase($type);
		$beanOrBeans = $this->redbean->dispense( $type, $num, $alwaysReturnArray );
		if (isset($import))
			$beanOrBeans->import( $import );
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
		return $this->finder->findOrDispense( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function batch( $type, $ids )
	{
		return $this->redbean->batch( $this->writer->adaptCase($type), $ids );
	}

	function loadAll( $type, $ids )
	{
		return $this->redbean->batch( $this->writer->adaptCase($type), $ids );
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
		$sql = (string)$sql;
		if(stripos($sql,'LIMIT')===false)
			$sql .= 'LIMIT 1';
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
		return $this->redbean->convertToBeans( $this->writer->adaptCase($type), $rows );
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
		return $this->tagManager->taggedAll( $this->writer->adaptCase($beanType), $tagList, $sql, $bindings );
	}

	function wipe( $beanType )
	{
		return $this->redbean->wipe( $this->writer->adaptCase($beanType) );
	}

	function count( $type, $addSQL = '', $bindings = [] )
	{
		return $this->redbean->count( $this->writer->adaptCase($type), $addSQL, $bindings );
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
	
	
	/* Added APIs */
	function create($type,$values=[]){
		return $this->newOne($type,$this->_uniqSetter($type,$values))->box();
	}
	function read($mix){
		if(func_num_args()>1){
			$type = $mix;
			$id = func_get_arg(1);
		}
		else
			list($type,$id) = explode(':',$mix);
		return $this->load($type,$id)->box();
	}
	function update($mix,$values){
		$model = $this->read($mix);
		foreach($values as $k=>$v)
			$model->$k = $v;
		return $model;
	}
	function delete($mix){
		return $this->trash($this->read($mix));
	}
	function drop($type){
		return $this->getWriter()->drop($this->writer->adaptCase($type));
	}
	function execMulti($sql,$bindings=[]){
		$pdo = $this->getDatabaseAdapter()->getDatabase()->getPDO();
		$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
		$r = $this->exec($sql, $bindings);
		$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		return $r;
	}
	function execFile($file,$bindings=[]){
		return $this->execMulti(file_get_contents($file),$bindings);
	}
	
	function getModelClass($type){
		static $cache = [];
		$k = $this->name.'.'.$type;
		if(!isset($cache[$k])){
			$type = $this->writer->reverseCase($type);
			$name = $this->name=='default'?'':ucfirst($this->name).'\\';
			$c = '\\Model\\Table';
			$cl = '\\Model\\'.$name.'Table';
			$cla = $cl.ucfirst($type);
			if(class_exists($cla))
				$cache[$k] = $cla;
			elseif($name&&class_exists($cl))
				$cache[$k] = $cl;
			else
				$cache[$k] = $c;
		}
		return $cache[$k];
	}
	function getClassModel($c){
		return lcfirst(ltrim(substr(ltrim($c,'\\'),11),'_'));
	}
	function getTableColumnDef($t,$col,$key=null){
		$c = $this->getModelClass($t);
		return $c::getColumnDef($col,$key);
	}
	function loadRow($type,$sql,$binds=[]){
		$b = $this->convertToBeans($type,[$this->getRow($type,$sql,$binds)]);
		return $b[0];
	}
	function findOrNewOne($type,$params=[],$insert=null){
		$query = [];
		$bind = [];
		$params = $this->_uniqSetter($type,$params);
		foreach($params as $k=>$v){
			$query[] = $k.'=?';
			$bind[] = $v;
		}
		$query = implode(' AND ',$query);
		$type = (array)$type;
		foreach($type as $t){
			if($bean = $this->findOne($t,$query,$bind))
				break;
		}
		if(!$bean){
			if(is_array($insert))
				$params = array_merge($params,$insert);
			$bean = $this->newOne(array_pop($type),$params);
		}
		return $bean->box();
	}
	function newOne($type,$params=[]){
		$bean = $this->dispense($type);
		if(is_string($params))
			$params = ['label'=>$params];
		foreach((array)$params as $k=>$v)
			$bean->$k = $v;
		return $bean;
	}
	function storeMultiArray( array $a){
		foreach($a as $v)
			$this->storeArray($v);
	}
	function storeArray( array $a){
		$dataO = $this->dispense($a['type']);
		foreach($a as $k=>$v){
			if($k=='type')
				continue;
			if(stripos($k,'own')===0){
				foreach((array)$v as $v2){
					$type = lcfirst(substr($k,3));
					$own = $this->dispense($type);
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
						$v2 = $this->getCell($type,'SELECT id where label=?',[$v2]);
					if($v2)
						$dataO->{$k}[] = $this->load($type,$v2);
				}
			}
			else
				$dataO->$k = $v;
		}
		return $this->store($dataO);
	}
	function getAll4D(){
		return Query::explodeAggTable(call_user_func_array(['self','getAll'],func_get_args()));
	}
	function getRow4D(){
		return Query::explodeAgg(call_user_func_array(['self','getRow'],func_get_args()));
	}
	function loadUniq($table,$id,$column=null){
		if(is_array($table)){
			foreach($table as $tb)
				if($r = $this->loadUniq($tb,$id,$column))
					return $r;
		}
		else{
			$table = $this->writer->adaptCase($table);
			$c = $this->getModelClass($table);
			if(!$column)
				$column = $c::getLoaderUniq($column);
			if(is_array($column)){
				foreach($column as $col)
					if($r = $this->loadUniq($table,$id,$col))
						return $r;
			}
			else{
				return $this->findOne($table,'WHERE '.$column.'=?',[$c::loadUniqFilter($id)]);
			}
		}
	}
	function load($type,$id,$column=null){
		if(is_array($type)){
			foreach($type as $tb)
				if($r = $this->load($tb,$id,$column))
					return $r;
		}
		else{
			if(is_string($id)||$column)
				return $this->loadUniq($type,$id,$column);
			return $this->redbean->load($this->writer->adaptCase($type),$id);
		}
	}

	function find( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->find( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function findAll( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->find( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function findAndExport( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findAndExport( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function findOne( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findOne( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function findLast( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findLast( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function _uniqSetter($type,$values){
		if(is_string($values)){
			$c = $this->getModelClass($type);
			$values = [$c::getLoadUniq()=>$values];
		}
		return $values;
	}

	function setUniqCheck($b=null){
		Table::_checkUniq($b);
	}
	
	function preload($beans, $preload, $closure = NULL){
		$preloader = new Preloader( R::getToolBox() );
		return $preloader->load($beans, $preload, $closure);
	}
	function each($beans, $preload, $closure = NULL){
		$preloader = new Preloader( R::getToolBox() );
		return $preloader->load($beans, $preload, $closure);
	}
	function graph($array, $fe = FALSE){
		$cooker = new Cooker;
		$cooker->setToolbox(R::getToolBox());
		return $cooker->graph($array, $fe);
	}
}