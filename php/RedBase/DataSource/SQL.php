<?php
namespace RedBase\DataSource;
use RedBase\DataSource;
use RedBase\RedBase;
use RedBase\Helper\SqlLogger;
abstract class SQL extends DataSource{
	protected $dsn;
	protected $pdo;
	protected $affectedRows;
	protected $resultArray;
	protected $connectUser;
	protected $connectPass;
	protected $isConnected;
	protected $loggingEnabled;
	protected $loggingResult = true;
	protected $logger;
	protected $options;
	protected $max = PHP_INT_MAX;
	protected $createDb;
	protected $unknownDatabaseCode;
	protected $encoding = 'utf8';
	protected $flagUseStringOnlyBinding = false;
	
	//QueryWriter
	const C_DATATYPE_RANGE_SPECIAL   = 80;
	protected $primaryKey;
	protected $uniqTextKey;
	protected $frozen;
	protected $typeno_sqltype = [];
	protected $sqltype_typeno = [];
	protected $quoteCharacter = '"';
	protected $defaultValue = 'NULL';
	protected $tablePrefix;
	protected $sqlFiltersWrite = [];
	protected $sqlFiltersRead = [];
	
	private $cacheTables;
	private $cacheColumns = [];
	
	function construct(array $config=[]){		
		if(isset($config[0]))
			$this->dsn = $config[0];
		else
			$this->dsn = isset($config['dsn'])?$config['dsn']:$this->buildDsnFromArray($config);
		
		if(isset($config[1]))
			$user = $config[1];
		else
			$user = isset($config['user'])?$config['user']:null;
		if(isset($config[2]))
			$password = $config[2];
		else
			$password = isset($config['password'])?$config['password']:null;
		if(isset($config[3]))
			$options = $config[3];
		else
			$options = isset($config['options'])?$config['options']:[];
		
		$frozen = isset($config[4])?$config[4]:(isset($config['frozen'])?$config['frozen']:null);
		$createDb = isset($config[5])?$config[5]:(isset($config['createDb'])?$config['createDb']:null);

		$tablePrefix = isset($config['tablePrefix'])?$config['tablePrefix']:null;
		
		$this->connectUser = $user;
		$this->connectPass = $password;
		$this->options = $options;
		$this->createDb = $createDb;
		
		$this->frozen = $frozen;
		$this->tablePrefix = $tablePrefix;
	}
	
	function createRow($type,$obj,$primaryKey='id',$uniqTextKey='uniq'){
		$properties = [];
		$postInsert = [];
		foreach($obj as $k=>$v){
			if(strpos($k,'_')===0)
				continue;
			if(is_object($v)||is_array($v)){
				if(is_object($v)){
					$t = $this->findEntityTable($v,$k);
					$pk = $this[$t]->getPrimaryKey();
					if(isset($v->$pk))
						$this[$t][$v->$pk] = $v;
					else
						$this[$t][] = $v;
					$properties[$k.'_'.$primaryKey] = $obj->{$k.'_'.$primaryKey} = $v->$pk;
				}
				elseif(is_array($v)){
					foreach($v as $val){
						$t = $this->findEntityTable($val,$k);
						$pk = $this[$t]->getPrimaryKey();
						$val->{$type.'_'.$pk} = &$obj->$primaryKey;
						$postInsert[$t][] = $val;
					}
				}
				else{
					throw new \InvalidArgumentException('createRow doesn\'t accepts ressources, type: "'.get_resource_type($v).'"');
				}
			}
			else{
				$properties[$k] = $v;
			}
		}
		$r = $this->create($type,$properties,$primaryKey,$uniqTextKey);
		$obj->{$primaryKey} = $r;
		foreach($postInsert as $k=>$v){
			foreach($v as $val){
				$this[$k][] = $val;
			}
		}
		return $r;
	}
	function readRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return false;
		return $this->read($type,$id,$primaryKey,$uniqTextKey);
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$properties = [];
		$postUpdate = [];
		foreach($obj as $k=>$v){
			if(strpos($k,'_')===0)
				continue;
			if(is_object($v)||is_array($v)){
				if(is_object($v)){
					$t = $this->findEntityTable($v,$k);
					$pk = $this[$t]->getPrimaryKey();
					if(isset($v->{$pk}))
						$this[$t][$v->{$pk}] = $v;
					else
						$this[$t][] = $v;
					$obj->{$k.'_'.$primaryKey} = &$v->{$pk};
				}
				elseif(is_array($v)){
					foreach($v as $val){
						$t = $this->findEntityTable($val,$k);
						$pk = $this[$t]->getPrimaryKey();
						$val->{$type.'_'.$pk} = &$obj->{$primaryKey};
						$postUpdate[$t][] = $val;
					}
				}
				else{
					throw new \InvalidArgumentException('updateRow doesn\'t accepts ressources, type: "'.get_resource_type($v).'"');
				}
			}
			else{
				$properties[$k] = $v;
			}
		}
		$r = $this->update($type,$properties,$id,$primaryKey,$uniqTextKey);		
		foreach($postUpdate as $k=>$v){
			foreach($v as $val){
				$this[$k][$val->{$this[$k]->getPrimaryKey()}] = $val;
			}
		}
		return $r;
	}
	function deleteRow($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return false;
		return $this->delete($type,$id,$primaryKey,$uniqTextKey);
	}
	function readId($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type)||!in_array($uniqTextKey,array_keys($this->getColumns($type))))
			return false;
		$table = $this->escTable($type);
		return $this->getCell("SELECT {$primaryKey} FROM {$table} WHERE {$uniqTextKey}=?",[$id]);
	}
	function create($type,$properties,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&(
			(isset($properties[$uniqTextKey])&&$idKey=$uniqTextKey)
			||(isset($properties[$primaryKey])&&$idKey=$primaryKey)
		))
			return $this->update($type,$properties,$properties[$idKey],$primaryKey,$uniqTextKey);
		if(array_key_exists($primaryKey,$properties))
			unset($properties[$primaryKey]);
		$insertcolumns = array_keys($properties);
		$insertvalues = array_values($properties);
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix($type);
		$table   = $this->escTable($type);
		$this->adaptStructure($type,$properties);
		if(!empty($insertvalues)){
			$insertSlots = [];
			foreach($insertcolumns as $k=>$v){
				$insertcolumns[$k] = $this->esc($v);
				if (isset($this->sqlFiltersWrite[$type][$v]))
					$insertSlots[] = $this->sqlFiltersWrite[$type][$v];
				else
					$insertSlots[] = '?';
			}
			$result = $this->getCell('INSERT INTO '.$table.' ( '.$primaryKey.', '.implode(',',$insertcolumns).' ) VALUES ( '.$default.', '. implode(',',$insertSlots).' ) '.$suffix,$insertvalues);
		}
		else{
			$result = $this->getCell('INSERT INTO '.$table.' ('.$primaryKey.') VALUES('.$default.') '.$suffix);
		}
		if($suffix)
			return $result;
		return $this->getInsertID();
	}
	function read($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&!self::canBeTreatedAsInt($id))
			$primaryKey = $uniqTextKey;
		$table = $this->escTable($type);
		$sqlFilterStr = $this->getSQLFilterSnippet($type);
		$sql = "SELECT {$table}.* {$sqlFilterStr} FROM {$table} WHERE {$primaryKey}=? LIMIT 1";
		$row = $this->getRow($sql,[$id]);
		if($row){
			$c = $this->findEntityClass($type);
			$obj = new $c();
			foreach($row as $k=>$v)
				$obj->$k = $v;
			return $obj;
		}
	}
	function update($type,$properties,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		$uniqTexting = false;
		if($uniqTextKey&&!self::canBeTreatedAsInt($id)){
			$uniqTexting = true;
			$properties[$uniqTextKey] = $id;
			$id = $this->readId($type,$id,$primaryKey,$uniqTextKey);
		}
		if(!$id)
			return $this->create($type,$properties,$primaryKey);
		if(!$this->tableExists($type))
			return false;
		$this->adaptStructure($type,$properties);
		$fields = [];
		$binds = [];
		foreach($properties as $k=>$v){
			if($k==$primaryKey||($uniqTexting&&$k==$uniqTextKey))
				continue;
			if(isset($this->sqlFiltersWrite[$type][$k]))
				$fields[] = ' '.$this->esc($k).' = '.$this->sqlFiltersWrite[$type][$k];
			else
				$fields[] = ' '.$this->esc($k).' = ? ';
			$binds[] = $v;
		}
		if(empty($fields))
			return $id;
		$binds[] = $id;
		$table = $this->escTable($type);
		$this->execute('UPDATE '.$table.' SET '.implode(',',$fields).' WHERE '.$primaryKey.' = ? ', $binds);
		return $id;
	}
	function delete($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&!self::canBeTreatedAsInt($id))
			$primaryKey = $uniqTextKey;
		$this->execute('DELETE FROM '.$this->escTable($type).' WHERE '.$primaryKey.' = ?', [$id]);
	}
	
	
	private function buildDsnFromArray($config){
		$type = $config['type'].':';
		$host = isset($config['host'])&&$config['host']?'host='.$config['host']:'';
		$file = isset($config['file'])&&$config['file']?$config['file']:'';
		$port = isset($config['port'])&&$config['port']?';port='.$config['port']:null;
		$name = isset($config['name'])&&$config['name']?';dbname='.$config['name']:null;
		return $type.$host.$file.$port.$name;
	}
	
	
	//PDO
	function getEncoding(){
		return $this->encoding;
	}
	protected function bindParams( $statement, $bindings ){
		foreach ( $bindings as $key => &$value ) {
			if(is_integer($key)){
				if(is_null($value))
					$statement->bindValue( $key + 1, NULL, \PDO::PARAM_NULL );
				elseif(!$this->flagUseStringOnlyBinding && self::canBeTreatedAsInt( $value ) && abs( $value ) <= $this->max)
					$statement->bindParam($key+1,$value,\PDO::PARAM_INT);
				else
					$statement->bindParam($key+1,$value,\PDO::PARAM_STR);
			}
			else{
				if(is_null($value))
					$statement->bindValue( $key, NULL, \PDO::PARAM_NULL );
				elseif( !$this->flagUseStringOnlyBinding && self::canBeTreatedAsInt( $value ) && abs( $value ) <= $this->max )
					$statement->bindParam( $key, $value, \PDO::PARAM_INT );
				else
					$statement->bindParam( $key, $value, \PDO::PARAM_STR );
			}
		}
	}
	protected function runQuery( $sql, $bindings, $options = [] ){
		$this->connect();
		if($this->loggingEnabled)
			$this->logger->logSql( $sql, $bindings );
		try {
			list($sql,$bindings) = self::nestBinding($sql,$bindings);
			$statement = $this->pdo->prepare( $sql );
			$this->bindParams( $statement, $bindings );
			$statement->execute();
			$this->affectedRows = $statement->rowCount();
			if($statement->columnCount()){
				$fetchStyle = ( isset( $options['fetchStyle'] ) ) ? $options['fetchStyle'] : NULL;
				if ( isset( $options['noFetch'] ) && $options['noFetch'] ) {
					$this->resultArray = [];
					return $statement;
				}
				$this->resultArray = $statement->fetchAll( $fetchStyle );
				if($this->loggingEnabled){
					if($this->loggingResult)
						$this->logger->logResult($this->resultArray);
					else
						$this->logger->log('resultset: '.count($this->resultArray).' rows');
				}
			}
			else{
				$this->resultArray = [];
			}
		}
		catch(\PDOException $e){
			if ( $this->loggingEnabled ){
				$this->logger->log('An error occurred: '.$e->getMessage());
				$this->logger->logSql( $sql, $bindings );
			}
			throw $e;
		}
	}
	function setUseStringOnlyBinding( $yesNo ){
		$this->flagUseStringOnlyBinding = (boolean) $yesNo;
	}
	function setMaxIntBind( $max ){
		if ( !is_integer( $max ) )
			throw new \InvalidArgumentException( 'Parameter has to be integer.' );
		$oldMax = $this->max;
		$this->max = $max;
		return $oldMax;
	}
	private function setPDO($dsn){
		$this->pdo = new \PDO($dsn,$this->connectUser,$this->connectPass);
		$this->pdo->setAttribute( \PDO::ATTR_STRINGIFY_FETCHES, TRUE );
		$this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		$this->pdo->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
		if(!empty($this->options)) foreach($this->options as $opt=>$attr) $this->pdo->setAttribute($opt,$attr);
	}
	function connect(){
		if($this->isConnected)
			return;
		try {
			$this->setPDO($this->dsn);
			$this->isConnected = true;
		}
		catch ( \PDOException $exception ) {
			if($this->createDb&&(!$this->unknownDatabaseCode||$this->unknownDatabaseCode==$exception->getCode())){				
				$dsn = $this->dsn;
				$p = strpos($this->dsn,'dbname=')+7;
				$p2 = strpos($dsn,';',$p);
				if($p2===false){
					$dbname = substr($dsn,$p);
					$dsn = substr($dsn,0,$p-8);
				}
				else{
					$dbname = substr($dsn,$p,$p2-$p);
					$dsn = substr($dsn,0,$p-8).substr($dsn,$p2);
				}
				$this->setPDO($dsn);
				$this->createDatabase($dbname);
				$this->execute('use '.$dbname);
				$this->isConnected = true;
			}
			else{
				throw $exception;
			}
		}
	}
	function getAll( $sql, $bindings = [] ){
		$this->runQuery( $sql, $bindings );
		return $this->resultArray;
	}
	function getAssocRow( $sql, $bindings = [] ){
		$this->runQuery($sql,$bindings,['fetchStyle' => \PDO::FETCH_ASSOC]);
		return $this->resultArray;
	}
	function getCol( $sql, $bindings = [] ){
		$rows = $this->getAll( $sql, $bindings );
		$cols = [];
		if ( $rows && is_array( $rows ) && count( $rows ) > 0 )
			foreach ( $rows as $row )
				$cols[] = array_shift( $row );
		return $cols;
	}
	function getCell( $sql, $bindings = [] ){
		$arr = $this->getAll( $sql, $bindings );
		$res = NULL;
		if ( !is_array( $arr ) ) return NULL;
		if ( count( $arr ) === 0 ) return NULL;
		$row1 = array_shift( $arr );
		if ( !is_array( $row1 ) ) return NULL;
		if ( count( $row1 ) === 0 ) return NULL;
		$col1 = array_shift( $row1 );
		return $col1;
	}
	function getRow( $sql, $bindings = [] ){
		$arr = $this->getAll( $sql, $bindings );
		return array_shift( $arr );
	}
	function execute( $sql, $bindings = [] ){
		$this->runQuery( $sql, $bindings );
		return $this->affectedRows;
	}
	function getInsertID(){
		$this->connect();
		return (int) $this->pdo->lastInsertId();
	}
	function fetch( $sql, $bindings = [] ){
		return $this->runQuery( $sql, $bindings, [ 'noFetch' => true ] );
	}
	function affectedRows(){
		$this->connect();
		return (int) $this->affectedRows;
	}
	function getLogger(){
		return $this->logger;
	}
	function beginTransaction(){
		$this->connect();
		$this->pdo->beginTransaction();
	}
	function commit(){
		$this->connect();
		$this->pdo->commit();
	}
	function rollback(){
		$this->connect();
		$this->pdo->rollback();
	}
	function getDatabaseType(){
		$this->connect();
		return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME );
	}
	function getDatabaseVersion(){
		$this->connect();
		return $this->pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION );
	}
	function getPDO(){
		$this->connect();
		return $this->pdo;
	}
	function close(){
		$this->pdo         = null;
		$this->isConnected = false;
	}
	function isConnected(){
		return $this->isConnected && $this->pdo;
	}
	function debug($enable=true,$loggingResult=true){
		$this->loggingEnabled = (bool)$enable;
		$this->loggingResult = (bool)$loggingResult;
		if($this->loggingEnabled && !$this->logger)
			$this->logger = new SqlLogger(true);
	}
	function getIntegerBindingMax(){
		return $this->max;
	}
	abstract function createDatabase($dbname);
	
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
					$p = self::posnth($sql,'?',$k);
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
					$p = self::posnth($sql,'?',$k);
				$ln = $p+1;
				$nBinds[] = $v;
			}
		}
		return [$sql,$nBinds];
	}
	static function posnth($haystack,$needle,$n,$offset=0){
		$l = strlen($needle);
		for($i=0;$i<=$n;$i++){
			$indx = strpos($haystack, $needle, $offset);
			if($i==$n||$indx===false)
				return $indx;
			else
				$offset = $indx+$l;
		}
		return false;
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
	
	//QueryWriter
	function adaptStructure($type,$properties){
		if($this->frozen)
			return;
		if(!$this->tableExists($type))
			$this->createTable($type);
		$columns = $this->getColumns($type);
		foreach($properties as $column=>$value){
			if(!isset($columns[$column])){
				$this->addColumn($type,$column,$this->scanType($value,true));
			}
			else{
				$typeno = $this->scanType($value,false);
				$typedesc = $columns[$column];
				if(isset($this->sqltype_typeno[$typedesc])
					&&$this->sqltype_typeno[$typedesc]<self::C_DATATYPE_RANGE_SPECIAL
					&&$this->sqltype_typeno[$typedesc]<$typeno
				)
					$this->changeColumn($type,$column,$typeno);
			}
		}
	}
	
	protected function getInsertSuffix($primaryKey){
		return '';
	}
	function setSQLFiltersRead($sqlFilters){
		$this->sqlFiltersRead = $sqlFilters;
	}
	function getSQLFiltersRead(){
		return $this->sqlFiltersRead;
	}
	function setSQLFiltersWrite($sqlFilters){
		$this->sqlFiltersWrite = $sqlFilters;
	}
	function getSQLFiltersWrite(){
		return $this->sqlFiltersWrite;
	}
	protected function getSQLFilterSnippet($type){
		$sqlFilters = [];
		if(isset($this->sqlFiltersRead[$type])){
			foreach($this->sqlFiltersRead[$type] as $property=>$sqlFilter)
				$sqlFilters[] = $sqlFilter.' AS '.$property.' ';
		}
		return !empty($sqlFilters)?','.implode(',',$sqlFilters):'';
	}
	
	function check($struct){
		if(!preg_match('/^[a-zA-Z0-9_-]+$/',$struct))
			throw new \InvalidArgumentException('Table or Column name does not conform to RedBase security policies' );
		return $struct;
	}
	function esc($esc){
		$this->check($esc);
		return $this->quoteCharacter.$esc.$this->quoteCharacter;
	}
	function escTable($table){
		$this->check($table);
		return $this->quoteCharacter.$this->tablePrefix.$table.$this->quoteCharacter;
	}
	function prefixTable($table){
		$this->check($table);
		return $this->tablePrefix.$table;
	}
	function unEsc($esc){
		return trim($esc,$this->quoteCharacter);
	}
	function getQuoteCharacter(){
		return $this->quoteCharacter;
	}
	function getTablePrefix(){
		return $this->tablePrefix;
	}
	function tableExists($table,$prefix=false){
		if($prefix)
			$table = $this->prefixTable($table);
		return in_array($table, $this->getTables());
	}
	static function startsWithZeros($value){
		$value = strval($value);
		return strlen( $value ) > 1 && strpos( $value, '0' ) === 0 && strpos( $value, '0.' ) !== 0;
	}
	
	static function canBeTreatedAsInt( $value ){
		return (bool) ( strval( $value ) === strval( intval( $value ) ) );
	}
	
	protected static function makeFKLabel($from, $type, $to){
		return 'from_'.$from.'_to_table_'.$type.'_col_'.$to;
	}
	
	/**
	 * Given a type and a property name this method
	 * returns the foreign key map section associated with this pair.
	 *
	 * @param string $type     name of the type
	 * @param string $property name of the property
	 *
	 * @return array|NULL
	 */
	protected function getForeignKeyForTypeProperty( $type, $property ){
		$property = $this->check( $property );
		try {
			$map = $this->getKeyMapForType( $type );
		}
		catch ( \PDOException $e ) {
			return NULL;
		}

		foreach( $map as $key ) {
			if ( $key['from'] === $property ) return $key;
		}
		return NULL;
	}

	function getTables(){
		if(!isset($this->cacheTables))
			$this->cacheTables = $this->getTablesQuery();
		return $this->cacheTables;
	}
	function getColumns($table){
		if(!isset($this->cacheColumns[$table]))
			$this->cacheColumns[$table] = $this->getColumnsQuery($table);
		return $this->cacheColumns[$table];
	}
	function createTable($table){
		if(!in_array($table,$this->cacheTables))
			$this->cacheTables[] = $table;
		return $this->createTableQuery($table);
	}
	function addColumn($type,$column,$field){
		if(isset($this->cacheColumns[$type])&&!isset($this->cacheColumns[$type][$column]))
			$this->cacheColumns[$type][$column] = isset($this->typeno_sqltype[$field])?$this->typeno_sqltype[$field]:'';
		return $this->addColumnQuery($type,$column,$field);
	}
	function changeColumn($type,$property,$dataType){
		if(isset($this->cacheColumns[$type]))
			$this->cacheColumns[$type][$property] = isset($this->typeno_sqltype[$dataType])?$this->typeno_sqltype[$dataType]:'';
		return $this->changeColumnQuery($type,$property,$dataType);
	}
	
	abstract function scanType($value,$flagSpecial=false);
	
	abstract function getTablesQuery();
	abstract function getColumnsQuery($table);
	abstract function createTableQuery($table);
	abstract function addColumnQuery($type,$column,$field);
	abstract function changeColumnQuery($type,$property,$dataType);
	
	abstract function addFK($type,$targetType,$property,$targetProperty,$isDep);
	abstract protected function getKeyMapForType( $type );
	abstract function columnCode($typedescription, $includeSpecials);
	abstract function getTypeForID();
	abstract function addUniqueConstraint($type,$properties);
	abstract function addIndex( $type, $name, $property );
}