<?php
namespace Database\RedBeanPHP;

use Database\RedBeanPHP\ToolBox as ToolBox;
use Database\RedBeanPHP\OODB as OODB;
use Database\RedBeanPHP\QueryWriter as QueryWriter;
use Database\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use Database\RedBeanPHP\AssociationManager as AssociationManager;
use Database\RedBeanPHP\TagManager as TagManager;
use Database\RedBeanPHP\DuplicationManager as DuplicationManager;
use Database\RedBeanPHP\LabelMaker as LabelMaker;
use Database\RedBeanPHP\Finder as Finder;
use Database\RedBeanPHP\RedException\SQL as SQLException;
use Database\RedBeanPHP\RedException\Security as Security;
use Database\RedBeanPHP\Logger as Logger;
use Database\RedBeanPHP\Logger\RDefault as RDefault;
use Database\RedBeanPHP\Logger\RDefault\Debug as Debug;
use Database\RedBeanPHP\OODBBean as OODBBean;
use Database\RedBeanPHP\SimpleModel as SimpleModel;
use Database\RedBeanPHP\SimpleModelHelper as SimpleModelHelper;
use Database\RedBeanPHP\Adapter as Adapter;
use Database\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use Database\RedBeanPHP\RedException as RedException;
use Database\RedBeanPHP\BeanHelper\SimpleBeanHelper as SimpleBeanHelper;
use Database\RedBeanPHP\Driver\RPDO as RPDO;

use Database\Model;
use Database\Query;

use ObjexLoader\MutatorFacadeTrait;

use Vars\STR;
use Vars\ArrayObject;

class Database{
	use MutatorFacadeTrait;
	
	const C_REDBEANPHP_VERSION = '4.2-Surikat-Forked';
	private static $plugins = [];
	
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
	private $logger;
	private $exportCaseStyle = 'default';
	
	private $dbType;
	private $dsn;
	private $prefix;
	private $dbgroup;
	private $DatabaseGroup;
	
	function __construct($name='',DatabaseGroup $DatabaseGroup=null){
		$this->name = $name;
		if(false!==$p=strpos($this->name,'.'))
			$this->dbgroup = substr($this->name,0,$p);
		if(isset($DatabaseGroup))
			$this->DatabaseGroup = $DatabaseGroup;
		else
			$this->DatabaseGroup = $this->Database_RedBeanPHP_DatabaseGroup($this->dbgroup);
	}
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
		if(!isset($config)){
			$config = [
				'type'=>'sqlite',
				'file'=>'.data/db.'.$this->name.'.sqlite'
			];
		}
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
			$frozen = !$this->Dev_Level->DB;
		if(!isset($case))
			$case = true;
		$dsn = $type.':'.$host.$port.$name;
		$this->construct($dsn, $user, $password, $frozen, $prefix, $case);
	}
	function construct( $dsn = null, $username = null, $password = null, $frozen = false, $prefix = '', $case = true ){
		$this->prefix = $prefix;
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
	function _getType(){
		return $this->dbType;
	}
	function _getName(){
		return $this->name;
	}
	function _getPrefix(){
		return $this->prefix;
	}
	function _getDatabaseGroup(){
		return $this->DatabaseGroup;
	}
	function _setup( $dsn = NULL, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '', $case = true ){
		$this->dsn = $dsn;
		if ( is_object($dsn) ) {
			$db  = new RPDO( $dsn );
			$this->dbType = $db->getDatabaseType();
		} else {
			$this->dbType = substr( $dsn, 0, strpos( $dsn, ':' ) );
			if(is_numeric(substr($this->dbType,-1)))
				$dsn = substr($dsn,0,($l=strlen($this->dbType))-1).substr($dsn,$l);
			$db = new RPDO( $dsn, $user, $pass, TRUE );
		}
		$db->setDB($this);
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
		$wkey = trim( strtolower( $this->dbType ) );
		if ( !isset( $writers[$wkey] ) ) trigger_error( 'Unsupported DSN: '.$wkey );
		$writerClass = '\\Database\\RedBeanPHP\\QueryWriter\\'.$writers[$wkey];
		$this->writer      = new $writerClass( $this->adapter, $this, $prefix, $case );
		$this->redbean     = new OODB( $this->writer, $frozen );
		$this->redbean->freeze( ( $frozen === TRUE ) );
		$this->toolbox = new ToolBox( $this->redbean, $this->adapter, $this->writer, $this );
		return $this->toolbox;
	}
	
	private function query( $method, $sql, $bindings ){
		if ( !$this->redbean->isFrozen() ) {
			try {
				$rs = $this->adapter->$method( $sql, $bindings );
			} catch ( SQLException $exception ) {
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
	
	function _exists(){
		if($this->dbType=='sqlite')
			return is_file(substr($this->dsn,7));
		else
			return $this->testConnection();
	}
	function _testConnection(){
		if ( !isset( $this->adapter ) ) return FALSE;

		$database = $this->adapter->getDatabase();
		try {
			@$database->connect();
		} catch ( \Exception $e ) {}
		return $database->isConnected();
	}
	
	function _setNarrowFieldMode( $mode ){
		AQueryWriter::setNarrowFieldMode( $mode );
	}
	
	function _transaction( $callback )
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

	function _debug( $tf = TRUE, $mode = 0 ){
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

	function _inspect( $type = NULL ){
		return ($type === NULL) ? $this->writer->getTables() : $this->writer->getColumns( $this->writer->adaptCase($type) );
	}

	function _store( $bean ){
		if($bean instanceof SimpleModel)
			$bean = $bean->unbox();
		foreach(array_keys($bean->getProperties()) as $k){
			if(is_array($bean[$k])){
				foreach(array_keys($bean[$k]) as $i){
					if($bean[$k][$i] instanceof SimpleModel){
						$bean[$k][$i] = $bean[$k][$i]->unbox();
					}
				}
			}
			elseif($bean[$k] instanceof SimpleModel){
				$bean[$k] = $bean[$k]->unbox();
			}
		}
		if($bean->storing())
			return $this->redbean->store( $bean );
	}

	function _freeze( $tf = TRUE ){
		$this->redbean->freeze( $tf );
	}

	function _loadMulti( $types, $id ){
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

	function _trash($beanOrType,$id=null){
		if(is_string($beanOrType))
			return $this->trash( $this->load( $beanOrType, $id ) );
		return $this->redbean->trash( $beanOrType );
	}

	function _dispense($typeOrBeanArray,$num=1,$alwaysReturnArray=false){
		if(is_array($typeOrBeanArray)){
			if ( !isset( $typeOrBeanArray['_type'] ) ) {
				$list = array();
				foreach( $typeOrBeanArray as $beanArray )
					if ( !( is_array( $beanArray ) && isset( $beanArray['_type'] ) ) )
						throw new RedException( 'Invalid Array Bean' );
				foreach( $typeOrBeanArray as $beanArray )
					$list[] = $this->dispense( $beanArray );
				return $list;
			}
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

	function _dispenseAll( $order, $onlyArrays = FALSE )
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

	function _findOrDispense( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findOrDispense( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function _batch( $type, $ids )
	{
		return $this->redbean->batch( $this->writer->adaptCase($type), $ids );
	}

	function _loadAll( $type, $ids )
	{
		return $this->redbean->batch( $this->writer->adaptCase($type), $ids );
	}

	function _exec( $sql, $bindings = [] )
	{
		return $this->query( 'exec', $sql, $bindings );
	}

	function _getAll( $sql, $bindings = [] )
	{
		return $this->query( 'get', $sql, $bindings );
	}

	function _getCell( $sql, $bindings = [] )
	{
		$sql = (string)$sql;
		if(stripos($sql,'LIMIT')===false)
			$sql .= ' LIMIT 1';
		return $this->query( 'getCell', $sql, $bindings );
	}

	function _getRow( $sql, $bindings = [] )
	{
		return $this->query( 'getRow', $sql, $bindings );
	}

	function _getCol( $sql, $bindings = [] )
	{
		return $this->query( 'getCol', $sql, $bindings );
	}

	function _getAssoc( $sql, $bindings = [] )
	{
		return $this->query( 'getAssoc', $sql, $bindings );
	}

	function _getAssocRow( $sql, $bindings = [] )
	{
		return $this->query( 'getAssocRow', $sql, $bindings );
	}
	
	function _fetch($sql, $bindings = []){
		return $this->adapter->fetch( $sql, $bindings );
	}

	function _duplicate( $bean, $filters = array() ){
		return $this->dup( $bean, array(), FALSE, $filters );
	}
	
	function _exportAll( $beans, $parents = FALSE, $filters = [])
	{
		return $this->duplicationManager->exportAll( $beans, $parents, $filters, $this->exportCaseStyle );
	}

	function _useExportCase( $caseStyle = 'default' )
	{
		if ( !in_array( $caseStyle, [ 'default', 'camel', 'dolphin' ] ) ) throw new RedException( 'Invalid case selected.' );
		$this->exportCaseStyle = $caseStyle;
	}

	function _convertToBeans( $type, $rows )
	{
		return $this->redbean->convertToBeans( $this->writer->adaptCase($type), $rows );
	}

	function _hasTag( $bean, $tags, $all = FALSE )
	{
		return $this->tagManager->hasTag( $bean, $tags, $all );
	}

	function _untag( $bean, $tagList )
	{
		$this->tagManager->untag( $bean, $tagList );
	}

	function _tag( OODBBean $bean, $tagList = NULL )
	{
		return $this->tagManager->tag( $bean, $tagList );
	}

	function _addTags( OODBBean $bean, $tagList )
	{
		$this->tagManager->addTags( $bean, $tagList );
	}

	function _tagged( $beanType, $tagList, $sql = '', $bindings = [] )
	{
		return $this->tagManager->tagged( $beanType, $tagList, $sql, $bindings );
	}

	function _taggedAll( $beanType, $tagList, $sql = '', $bindings = [] )
	{
		return $this->tagManager->taggedAll( $this->writer->adaptCase($beanType), $tagList, $sql, $bindings );
	}

	function _wipe( $beanType )
	{
		return $this->redbean->wipe( $this->writer->adaptCase($beanType) );
	}

	function _count( $type, $addSQL = '', $bindings = [] )
	{
		return $this->redbean->count( $this->writer->adaptCase($type), $addSQL, $bindings );
	}
	
	function _begin(){
		if ( !$this->redbean->isFrozen() ) return FALSE;
		$this->adapter->startTransaction();
		return TRUE;
	}

	function _commit(){
		if ( !$this->redbean->isFrozen() ) return FALSE;
		$this->adapter->commit();
		return TRUE;
	}

	function _rollback(){
		if ( !$this->redbean->isFrozen() ) return FALSE;
		$this->adapter->rollback();
		return TRUE;
	}

	function _getColumns( $table ){
		return $this->writer->getColumns( $table );
	}

	function _nuke(){
		if ( !$this->redbean->isFrozen() ) {
			$this->writer->wipeAll();
		}
	}

	function _storeAll( $beans )
	{
		$ids = [];
		foreach ( $beans as $bean ) {
			$ids[] = $this->store( $bean );
		}

		return $ids;
	}

	function _trashAll( $beans )
	{
		foreach ( $beans as $bean ) {
			$this->trash( $bean );
		}
	}

	function _useWriterCache( $yesNo )
	{
		$this->getWriter()->setUseCache( $yesNo );
	}

	function _dispenseLabels( $type, $labels )
	{
		return $this->labelMaker->dispenseLabels( $type, $labels );
	}

	function _enum( $enum )
	{
		return $this->labelMaker->enum( $enum );
	}

	function _gatherLabels( $beans )
	{
		return $this->labelMaker->gatherLabels( $beans );
	}

	function _close()
	{
		if ( isset( $this->adapter ) ) {
			$this->adapter->close();
		}
	}

	function _isoDate( $time = NULL )
	{
		if ( !$time ) {
			$time = time();
		}

		return @date( 'Y-m-d', $time );
	}

	function _isoDateTime( $time = NULL )
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

	function _getDatabaseAdapter()
	{
		return $this->adapter;
	}

	function _getDuplicationManager()
	{
		return $this->duplicationManager;
	}

	function _getWriter()
	{
		return $this->writer;
	}

	function _getRedBean(){
		return $this->redbean;
	}

	function _getToolBox(){
		return $this->toolbox;
	}

	function _getExtractedToolbox(){
		return [
			$this->redbean,
			$this->adapter,
			$this->writer,
			$this->toolbox
		];
	}

	function _renameAssociation( $from, $to = NULL )
	{
		AQueryWriter::renameAssociation( $from, $to );
	}

	function _beansToArray( $beans )
	{
		$list = [];
		foreach( $beans as $bean ) {
			$list[] = $bean->export();
		}
		return $list;
	}
	function _dup( $bean, $trail = [], $pid = FALSE, $filters = [] )
	{
		$this->duplicationManager->setFilters( $filters );
		return $this->duplicationManager->dup( $bean, $trail, $pid );
	}
	function _dump( $data )
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

	function _bindFunc( $mode, $field, $function ) {
		$this->redbean->bindFunc( $mode, $field, $function );
	}
	
	
	/* Added APIs */
	function _create($type,$values=[]){
		return $this->newOne($type,$this->_uniqSetter($type,$values))->box();
	}
	function _read($mix){
		if(func_num_args()>1){
			$type = $mix;
			$id = func_get_arg(1);
		}
		else
			list($type,$id) = explode(':',$mix);
		return $this->load($type,$id)->box();
	}
	function _update($mix,$values){
		$model = $this->read($mix);
		foreach($values as $k=>$v)
			$model->$k = $v;
		return $model;
	}
	function _removeTable($type){
		$type = $this->writer->adaptCase($type);
		$this->exec('DELETE FROM '.$type);
		$tables = $this->writer->getTables();
		foreach($tables as $table){
			if(strpos($table,'_')!==false){
				$x = explode('_',$table);
				if($x[0]==$type||$x[1]==$type){
					$this->wipe($table);
					$this->dropTable($table);
					continue;
				}
			}
			$columns = array_keys($this->writer->getColumns($table));
			$col = $type.'_id';
			if(in_array($col,$columns)){
				$this->exec('ALTER TABLE '.$table.' DROP COLUMN '.$col);
			}
		}
		$this->dropTable($type);
		
	}
	function _delete($mix){
		return $this->trash($this->read($mix));
	}
	function _dropTable($type){
		return $this->getWriter()->drop($this->writer->adaptCase($type));
	}
	function _execMulti($sql,$bindings=[]){
		$pdo = $this->getDatabaseAdapter()->getDatabase()->getPDO();
		$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
		$r = $this->exec($sql, $bindings);
		$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		return $r;
	}
	function _execFile($file,$bindings=[]){
		return $this->execMulti(file_get_contents($file),$bindings);
	}
	function _getModelClass($type){
		$type = ucfirst($this->writer->reverseCase($type));
		if($this->name==''){
			$name = '';
		}
		else{
			$name = ucfirst(str_replace(' ', '\\', ucwords(str_replace('.', ' ', $this->name)))).'\\';
		}
		foreach($this->DatabaseGroup->getModelNamespace() as $ns){
			if(class_exists($c=$ns.$name.'Model'.$type))
				return $c;
			if(class_exists($c=$ns.'Model'.$type))
				return $c;
			if(class_exists($c=$ns.$name.'Model'))
				return $c;
			if(class_exists($c=$ns.'Model'))
				return $c;
		}
		return 'Database\\Model';
	}
	function _getClassModel($c){
		return lcfirst(ltrim(substr(ltrim($c,'\\'),11),'_'));
	}
	function _getTableColumnDef($t,$col,$key=null){
		$c = $this->getModelClass($t);
		return $c::getColumnDef($col,$key);
	}
	function _loadRow($type,$sql,$binds=[]){
		$b = $this->convertToBeans($type,[$this->getRow($type,$sql,$binds)]);
		return $b[0];
	}
	function _findOrNewOne($type,$params=[],$insert=null){
		$query = [];
		$bind = [];
		$params = $this->_uniqSetter($type,$params);
		foreach($params as $k=>$v){
			if($v===null)
				$query[] = $k.' IS ?';
			else
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
	function _newOne($type,$params=[]){
		$bean = $this->dispense($type);
		if(is_string($params))
			$params = ['label'=>$params];
		foreach((array)$params as $k=>$v)
			$bean->$k = $v;
		return $bean;
	}
	function _storeMultiArray( array $a){
		foreach($a as $v)
			$this->storeArray($v);
	}
	function _storeArray( array $a){
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
	function _loadUniq($table,$id,$column=null){
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
	
	function _load($type,$id,$column=null){
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

	function _find( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->find( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function _findAll( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->find( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function _findAndExport( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findAndExport( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function _findOne( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findOne( $this->writer->adaptCase($type), $sql, $bindings );
	}

	function _findLast( $type, $sql = NULL, $bindings = [] )
	{
		return $this->finder->findLast( $this->writer->adaptCase($type), $sql, $bindings );
	}
	
	/**
	 * Finds a bean collection.
	 * Use this for large datasets.
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return BeanCollection
	 */
	public function _findCollection( $type, $sql = NULL, $bindings = array() )
	{
		return $this->finder->findCollection( $type, $sql, $bindings );
	}
	
	function _safeTable($t){
		return $this->writer->safeTable($t);
	}
	function _safeColumn($t){
		return $this->writer->safeColumn($t);
	}
	
	/**
	* Sets global aliases.
	*
	* @param array $list
	*
	* @return void
	*/
	public function _aliases( $list ){
		OODBBean::aliases( $list );
	}
	
	/**
	 * Tries to find a bean matching a certain type and
	 * criteria set. If no beans are found a new bean
	 * will be created, the criteria will be imported into this
	 * bean and the bean will be stored and returned.
	 * If multiple beans match the criteria only the first one
	 * will be returned.
	 *
	 * @param string $type type of bean to search for
	 * @param array  $like criteria set describing the bean to search for
	 *
	 * @return OODBBean
	 */
	public function _findOrCreate( $type, $like = array() )
	{
		return $this->finder->findOrCreate( $type, $like );
	}

	/**
	 * Tries to find beans matching the specified type and
	 * criteria set.
	 *
	 * @param string $type type of bean to search for
	 * @param array  $like criteria set describing the bean to search for
	 *
	 * @return array
	 */
	public function _findLike( $type, $like = array(), $sql = '' )
	{
		return $this->finder->findLike( $type, $like, $sql );
	}
	
	/**
	 * Starts logging queries.
	 * Use this method to start logging SQL queries being
	 * executed by the adapter.
	 *
	 * @note you cannot use R::debug and R::startLogging
	 * at the same time because R::debug is essentially a
	 * special kind of logging.
	 *
	 * @return void
	 */
	public function _startLogging()
	{
		$this->debug( TRUE, RDefault::C_LOGGER_ARRAY );
	}

	/**
	 * Stops logging, comfortable method to stop logging of queries.
	 *
	 * @return void
	 */
	public function _stopLogging()
	{
		$this->debug( FALSE );
	}

	/**
	 * Returns the log entries written after the startLogging.
	 *
	 * @return array
	 */
	public function _getLogs()
	{
		return $this->getLogger()->getLogs();
	}

	/**
	 * Resets the Query counter.
	 *
	 * @return integer
	 */
	public function _resetQueryCount()
	{
		$this->adapter->getDatabase()->resetCounter();
	}

	/**
	 * Returns the number of SQL queries processed.
	 *
	 * @return integer
	 */
	public function _getQueryCount()
	{
		return $this->adapter->getDatabase()->getQueryCount();
	}

	/**
	 * Returns the current logger instance being used by the
	 * database object.
	 *
	 * @return Logger
	 */
	public function _getLogger()
	{
		return $this->adapter->getDatabase()->getLogger();
	}
	
	/**
	 * Turns on the fancy debugger.
	 */
	public function _fancyDebug( $toggle )
	{
		$this->debug( $toggle, 2 );
	}
	
	/**
	* Flattens a multi dimensional bindings array for use with genSlots().
	*
	* @param array $array array to flatten
	*
	* @return array
	*/
	public function _flat( $array, $result = array() )
	{
		foreach( $array as $value ) {
			if ( is_array( $value ) ) $result = $this->flat( $value, $result );
			else $result[] = $value;
		}
		return $result;
	}

	/**
	 * Generates question mark slots for an array of values.
	 *
	 * @param array  $array    array to generate question mark slots for
	 *
	 * @return string
	 */
	public function _genSlots( $array, $template = NULL )
	{
		$str = count( $array ) ? implode( ',', array_fill( 0, count( $array ), '?' ) ) : '';
		return ( is_null( $template ) ||  $str === '' ) ? $str : sprintf( $template, $str );
	}
	
	/**
	 * Alias for setAutoResolve() method on OODBBean.
	 * Enables or disables auto-resolving fetch types.
	 * Auto-resolving aliased parent beans is convenient but can
	 * be slower and can create infinite recursion if you
	 * used aliases to break cyclic relations in your domain.
	 *
	 * @param boolean $automatic TRUE to enable automatic resolving aliased parents
	 *
	 * @return void
	 */
	public function _setAutoResolve( $automatic = TRUE )
	{
		OODBBean::setAutoResolve( (boolean) $automatic );
	}
	
	/**
	 * Returns the insert ID for databases that support/require this
	 * functionality. Alias for R::getAdapter()->getInsertID().
	 *
	 * @return mixed
	 */
	public function _getInsertID()
	{
		return $this->adapter->getInsertID();
	}
	
	
	function _uniqSetter($type,$values){
		if(is_string($values)){
			$c = $this->getModelClass($type);
			$values = [$c::getLoadUniq()=>$values];
		}
		return $values;
	}

	function _setUniqCheck($b=null){
		Model::_checkUniq($b);
	}
	
	function getModelNamespace(){
		return $this->DatabaseGroup->getModelNamespace();
	}
	function setModelNamespace($namespace){
		return $this->DatabaseGroup->setModelNamespace($namespace);
	}
	function addModelNamespace($namespace,$prepend=null){
		return $this->DatabaseGroup->addModelNamespace($namespace,$prepend);
	}
	function appendModelNamespace($namespace){
		return $this->DatabaseGroup->appendModelNamespace($namespace);
	}
	function prependModelNamespace($namespace){
		return $this->DatabaseGroup->prependModelNamespace($namespace);
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
	static function getVersion(){
		return self::C_REDBEANPHP_VERSION;
	}
	static function setErrorHandlingFUSE( $mode, $func = NULL ){
		return OODBBean::setErrorHandlingFUSE( $mode, $func );
	}
	static function ext( $pluginName, $callable ){
		if ( !ctype_alnum( $pluginName ) ) {
			throw new RedException( 'Plugin name may only contain alphanumeric characters.' );
		}
		self::$plugins[$pluginName] = $callable;
	}
	static function ___callStatic( $pluginName, $params ){
		if ( !ctype_alnum( $pluginName) ) {
			throw new RedException( 'Plugin name may only contain alphanumeric characters.' );
		}
		if ( !isset( self::$plugins[$pluginName] ) ) {
			throw new RedException( 'Plugin \''.$pluginName.'\' does not exist, add this plugin using: R::ext(\''.$pluginName.'\')' );
		}
		return call_user_func_array( self::$plugins[$pluginName], $params );
	}
	static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE, $prefix = '', $case = true ){
		$db = self::getStatic($key);
		$db->construct($dsn, $user, $pass, $frozen, $prefix, $case);
		return $db;
	}
	static function selectDatabase($key){
		return self::setStatic($key);
	}
}