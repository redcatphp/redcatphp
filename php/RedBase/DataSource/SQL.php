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
	protected $loggingResult;
	protected $loggingExplain;
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
	protected $ftsTableSuffix = '_fulltext_';
	
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
	function readId($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type)||!in_array($uniqTextKey,array_keys($this->getColumns($type))))
			return;
		$table = $this->escTable($type);
		return $this->getCell('SELECT '.$primaryKey.' FROM '.$table.' WHERE '.$uniqTextKey.'=?',[$id]);
	}
	function createQuery($type,$properties,$primaryKey='id',$uniqTextKey='uniq'){
		$insertcolumns = array_keys($properties);
		$insertvalues = array_values($properties);
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix($primaryKey);
		$table   = $this->escTable($type);
		$this->adaptStructure($type,$properties,$primaryKey,$uniqTextKey);
		if(!empty($insertvalues)){
			$insertSlots = [];
			foreach($insertcolumns as $k=>$v){
				$insertcolumns[$k] = $this->esc($v);
				$insertSlots[] = $this->getWriteSnippet($type,$v);
			}
			$result = $this->getCell('INSERT INTO '.$table.' ( '.$primaryKey.', '.implode(',',$insertcolumns).' ) VALUES ( '.$default.', '. implode(',',$insertSlots).' ) '.$suffix,$insertvalues);
		}
		else{
			$result = $this->getCell('INSERT INTO '.$table.' ('.$primaryKey.') VALUES('.$default.') '.$suffix);
		}
		if($suffix)
			$id = $result;
		else
			$id = (int)$this->pdo->lastInsertId();
		if(!$this->frozen&&method_exists($this,'adaptPrimaryKey'))
			$this->adaptPrimaryKey($type,$id,$primaryKey);
		return $id;
	}
	function readQuery($type,$id,$primaryKey='id',$uniqTextKey='uniq',$obj){
		if($uniqTextKey&&!self::canBeTreatedAsInt($id))
			$primaryKey = $uniqTextKey;
		$table = $this->escTable($type);
		if($sqlFilterStr = $this->getReadSnippet($type))
			$sqlFilterStr .= ',';
		$sql = "SELECT {$table}.* {$sqlFilterStr} FROM {$table} WHERE {$primaryKey}=? LIMIT 1";
		$row = $this->getRow($sql,[$id]);
		if($row){
			foreach($row as $k=>$v)
				$obj->$k = $v;
			return $obj;
		}
	}
	function updateQuery($type,$properties,$id=null,$primaryKey='id',$uniqTextKey='uniq'){
		if(!$this->tableExists($type))
			return;
		$this->adaptStructure($type,$properties,$primaryKey,$uniqTextKey);
		$fields = [];
		$binds = [];
		foreach($properties as $k=>$v){
			//if($k==$primaryKey||($uniqTexting&&$k==$uniqTextKey))
			if($k==$primaryKey)
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
	function deleteQuery($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		if($uniqTextKey&&!self::canBeTreatedAsInt($id))
			$primaryKey = $uniqTextKey;
		$this->execute('DELETE FROM '.$this->escTable($type).' WHERE '.$primaryKey.' = ?', [$id]);
		return $this->affectedRows;
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
		$sql = str_replace('{#prefix}',$this->tablePrefix,$sql);
		if($this->loggingEnabled)
			$this->logger->logSql( $sql, $bindings );
		try {
			list($sql,$bindings) = self::nestBinding($sql,$bindings);
			$statement = $this->pdo->prepare( $sql );
			$this->bindParams( $statement, $bindings );
			if($this->loggingEnabled)
				$start = microtime(true);
			$statement->execute();
			if($this->loggingEnabled){
				$chrono = microtime(true)-$start;
				if($chrono>=1){
					$u = 's';
				}
				else{
					$chrono = $chrono*(float)1000;
					$u = 'ms';
				}
				$this->logger->logChrono(sprintf("%.2f", $chrono).' '.$u);
				if($this->loggingExplain){
					try{
						$explain = $this->explain($sql,$bindings);
						if($explain)
							$this->logger->logExplain($explain);
					}
					catch(\PDOException $e){
						$this->logger->log($e->getMessage());
					}
				}
			}
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
	function debug($enable=true,$loggingResult=true,$loggingExplain=true){
		$this->loggingEnabled = (bool)$enable;
		$this->loggingResult = (bool)$loggingResult;
		$this->loggingExplain = (bool)$loggingExplain;
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
	function adaptStructure($type,$properties,$primaryKey='id',$uniqTextKey=null){
		if($this->frozen)
			return;
		if(!$this->tableExists($type))
			$this->createTable($type,$primaryKey);
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
			if(isset($uniqTextKey)&&$uniqTextKey==$column){
				$this->addUniqueConstraint($type,$column);
			}
		}
	}
	
	protected function getInsertSuffix($primaryKey){
		return '';
	}
	function unbindRead($type,$property=null,$func=null){
		if(!isset($property)){
			if(isset($this->sqlFiltersRead[$type])){
				unset($this->sqlFiltersRead[$type]);
				return true;
			}
		}
		elseif(!isset($func)){
			if(isset($this->sqlFiltersRead[$type][$property])){
				unset($this->sqlFiltersRead[$type][$property]);
				return true;
			}
		}
		elseif(false!==$i=array_search($func,$this->sqlFiltersRead[$type][$property])){
			unset($this->sqlFiltersRead[$type][$property][$i]);
			return true;
		}
	}
	function bindRead($type,$property,$func){
		$this->sqlFiltersRead[$type][$property][] = $func;
	}
	function unbindWrite($type,$property=null){
		if(!isset($property)){
			if(isset($this->sqlFiltersWrite[$type])){
				unset($this->sqlFiltersWrite[$type]);
				return true;
			}
		}
		elseif(isset($this->sqlFiltersWrite[$type][$property])){
			unset($this->sqlFiltersWrite[$type][$property]);
			return true;
		}
	}
	function bindWrite($type,$property,$func){
		$this->sqlFiltersWrite[$type][$property] = $func;
	}
	function setSQLFiltersRead(array $sqlFilters){
		$this->sqlFiltersRead = $sqlFilters;
	}
	function getSQLFiltersRead(){
		return $this->sqlFiltersRead;
	}
	function setSQLFiltersWrite(array $sqlFilters){
		$this->sqlFiltersWrite = $sqlFilters;
	}
	function getSQLFiltersWrite(){
		return $this->sqlFiltersWrite;
	}
	protected function getReadSnippet($type){
		$sqlFilters = [];
		$table = $this->escTable($type);
		if(isset($this->sqlFiltersRead[$type])){
			foreach($this->sqlFiltersRead[$type] as $property=>$funcs){
				$property = $this->esc($property);
				foreach($funcs as $func){
					$select = $table.'.'.$property;
					if(strpos($func,'(')===false)
						$func = $func.'('.$select.')';
					else
						$func = str_replace('?',$select,$func);
					if(strpos(strtolower($func),' as ')===false)
						$func .= ' AS '.$property;
					$sqlFilters[] = $func;
				}
			}
		}
		return !empty($sqlFilters)?implode(',',$sqlFilters):'';
	}
	protected function getWriteSnippet($type,$property){
		if (isset($this->sqlFiltersWrite[$type][$property])){
			$slot = $this->sqlFiltersWrite[$type][$property];
			if(strpos($slot,'(')===false)
				$slot = $slot.'(?)';
		}
		else{
			$slot = '?';
		}
		return $slot;
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
	function unprefixTable($table){
		if($this->tablePrefix&&substr($table,0,$l=strlen($this->tablePrefix))==$this->tablePrefix){
			$table = substr($table,$l);
		}
		return $table;
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
	function tableExists($table,$prefix=true){
		if($prefix)
			$table = $this->prefixTable($table);
		return in_array($table, $this->getTables());
	}
	static function startsWithZeros($value){
		$value = strval($value);
		return strlen($value)>1&&strpos($value,'0')===0&&strpos($value,'0.')!==0;
	}
	
	protected static function makeFKLabel($from, $type, $to){
		return 'from_'.$from.'_to_table_'.$type.'_col_'.$to;
	}
	
	protected function getForeignKeyForTypeProperty( $type, $property ){
		$property = $this->check($property);
		try{
			$map = $this->getKeyMapForType($type);
		}
		catch(\PDOException $e){
			return null;
		}
		foreach($map as $key){
			if($key['from']===$property)
				return $key;
		}
		return null;
	}

	function getTables(){
		if(!isset($this->cacheTables))
			$this->cacheTables = $this->getTablesQuery();
		return $this->cacheTables;
	}
	function getColumns($type){
		if(!isset($this->cacheColumns[$type]))
			$this->cacheColumns[$type] = $this->getColumnsQuery($type);
		return $this->cacheColumns[$type];
	}
	function createTable($type,$pk='id'){
		$table = $this->prefixTable($type);
		if(!in_array($table,$this->cacheTables))
			$this->cacheTables[] = $table;
		return $this->createTableQuery($type,$pk);
	}
	function addColumn($type,$column,$field){
		if(isset($this->cacheColumns[$type]))
			$this->cacheColumns[$type][$column] = (false!==$i=array_search($field,$this->sqltype_typeno))?$i:'';
		return $this->addColumnQuery($type,$column,$field);
	}
	function changeColumn($type,$column,$field){
		if(isset($this->cacheColumns[$type]))
			$this->cacheColumns[$type][$column] = $this->cacheColumns[$type][$column] = (false!==$i=array_search($field,$this->sqltype_typeno))?$i:'';
		return $this->changeColumnQuery($type,$column,$field);
	}
	
	function drops(){
		foreach(func_get_args() as $drop){
			if(is_array($drop)){
				foreach($drop as $d){
					$this->drop($d);
				}
			}
			else{
				$this->drop($drop);
			}
		}
	}
	function drop($t){
		if(isset($this->cacheTables)&&($i=array_search($t,$this->cacheTables))!==false)
			unset($this->cacheTables[$i]);
		if(isset($this->cacheColumns[$t]))
			unset($this->cacheColumns[$t]);
		$this->_drop($t);
	}
	function dropAll(){
		$this->_dropAll();
		$this->cacheTables = [];
		$this->cacheColumns = [];
	}
	
	function many2one($obj,$type){
		$table = clone $this[$type];
		$typeE = $this->escTable($type);
		$pk = $table->getPrimaryKey();
		$tb = $this->findEntityTable($obj);
		$pko = $this[$tb]->getPrimaryKey();
		$column = $this->esc($pk);
		$table->where($typeE.'.'.$column.' = ?',[$obj->$pko]);
		$table->select($typeE.'.*');
		if($sqlFilterStr = $this->getReadSnippet($type))
			$table->select($sqlFilterStr);
		return $table;
	}
	function one2many($obj,$type){
		$table = clone $this[$type];
		$typeE = $this->escTable($type);
		$pk = $table->getPrimaryKey();
		$tb = $this->findEntityTable($obj);
		$pko = $this[$tb]->getPrimaryKey();
		$column = $this->esc($tb.'_'.$pko);
		$table->where($typeE.'.'.$column.' = ?',[$obj->$pko]);
		$table->select($typeE.'.*');
		if($sqlFilterStr = $this->getReadSnippet($type))
			$table->select($sqlFilterStr);
		return $table;
	}
	function many2many($obj,$type,$via=null){
		$table = clone $this[$type];
		$typeE = $this->escTable($type);
		$pk = $table->getPrimaryKey();
		$tb = $this->findEntityTable($obj);
		$pko = $this[$tb]->getPrimaryKey();
		$colmun1 = $this->esc($type.'_'.$pk);
		$colmun2 = $this->esc($tb.'_'.$pko);
		if($via){
			$tbj = $via;
		}
		else{
			$tbj = [$type,$tb];
			sort($tbj);
			$tbj = implode('_',$tbj);
		}
		$tb = $this->escTable($tb);
		$tbj = $this->escTable($tbj);
		$pke = $this->esc($pk);
		$pkoe = $this->esc($pko);
		$table->join($tbj.' ON '.$tbj.'.'.$colmun1.' = '.$typeE.'.'.$pke);
		$table->join($tb.' ON '.$tb.'.'.$pkoe.' = '.$tbj.'.'.$colmun2
					.' AND '.$tb.'.'.$pkoe.' =  ?',[$obj->$pko]);
		$table->select($typeE.'.*');
		if($sqlFilterStr = $this->getReadSnippet($type))
			$table->select($sqlFilterStr);
		return $table;
	}
	
	function getFtsTableSuffix(){
		return $this->ftsTableSuffix;
	}
	
	abstract function scanType($value,$flagSpecial=false);
	
	abstract function getTablesQuery();
	abstract function getColumnsQuery($table);
	abstract function createTableQuery($table,$pk='id');
	abstract function addColumnQuery($type,$column,$field);
	abstract function changeColumnQuery($type,$property,$dataType);
	
	abstract function addFK($type,$targetType,$property,$targetProperty,$isDep);
	abstract function getKeyMapForType( $type );
	abstract function columnCode($typedescription, $includeSpecials);
	abstract function getTypeForID();
	abstract function addUniqueConstraint($type,$properties);
	abstract function addIndex( $type, $name, $property );
	
	abstract function clear($type);
	abstract protected function _drop($type);
	abstract protected function _dropAll();
	
	abstract protected function explain($sql,$bindings=[]);
}