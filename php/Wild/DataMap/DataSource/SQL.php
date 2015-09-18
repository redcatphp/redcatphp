<?php
namespace Wild\DataMap\DataSource;
use Wild\DataMap\DataSource;
use Wild\DataMap\Wild\DataMap;
use Wild\DataMap\Helper\SqlLogger;
use Wild\DataMap\Exception;
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
	protected $transactionCount = 0;
	
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
	
	protected $separator = ',';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $sumCaster = '';
	protected $concatenator;
	
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
		$createDb = isset($config[5])?$config[5]:(isset($config['createDb'])?$config['createDb']:true);

		$tablePrefix = isset($config['tablePrefix'])?$config['tablePrefix']:null;
		
		$this->connectUser = $user;
		$this->connectPass = $password;
		$this->options = $options;
		$this->createDb = $createDb;
		
		$this->frozen = $frozen;
		$this->tablePrefix = $tablePrefix;
		
		if(defined('HHVM_VERSION')||$this->dsn==='test-sqlite-53')
			$this->max = 2147483647;
	}
	function readId($type,$id,$primaryKey=null,$uniqTextKey=null){
		if(is_null($primaryKey))
			$primaryKey = $this[$type]->getPrimaryKey();
		if(is_null($uniqTextKey))
			$uniqTextKey = $this[$type]->getUniqTextKey();
		if(!$this->tableExists($type)||!in_array($uniqTextKey,array_keys($this->getColumns($type))))
			return;
		$table = $this->escTable($type);
		return $this->getCell('SELECT '.$primaryKey.' FROM '.$table.' WHERE '.$uniqTextKey.'=?',[$id]);
	}
	function createQuery($type,$properties,$primaryKey='id',$uniqTextKey='uniq',$cast=[],$func=[]){
		$insertcolumns = array_keys($properties);
		$insertvalues = array_values($properties);
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix($primaryKey);
		$table   = $this->escTable($type);
		$this->adaptStructure($type,$properties,$primaryKey,$uniqTextKey,$cast);
		$pk = $this->esc($primaryKey);
		if(!empty($insertcolumns)||!empty($func)){
			$insertSlots = [];
			foreach($insertcolumns as $k=>$v){
				$insertcolumns[$k] = $this->esc($v);
				$insertSlots[] = $this->getWriteSnippet($type,$v);
			}
			foreach($func as $k=>$v){
				$insertcolumns[] = $this->esc($k);
				$insertSlots[] = $v;
			}
			$result = $this->getCell('INSERT INTO '.$table.' ( '.$pk.', '.implode(',',$insertcolumns).' ) VALUES ( '.$default.', '. implode(',',$insertSlots).' ) '.$suffix,$insertvalues);
		}
		else{
			$result = $this->getCell('INSERT INTO '.$table.' ('.$pk.') VALUES('.$default.') '.$suffix);
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
	function updateQuery($type,$properties,$id=null,$primaryKey='id',$uniqTextKey='uniq',$cast=[],$func=[]){
		if(!$this->tableExists($type))
			return;
		$this->adaptStructure($type,$properties,$primaryKey,$uniqTextKey,$cast);
		$fields = [];
		$binds = [];
		foreach($properties as $k=>$v){
			if($k==$primaryKey)
				continue;
			if(isset($this->sqlFiltersWrite[$type][$k])){
				$fields[] = ' '.$this->esc($k).' = '.$this->sqlFiltersWrite[$type][$k];
				$binds[] = $v;
			}
			else{
				$fields[] = ' '.$this->esc($k).' = ?';
				$binds[] = $v;
			}
		}
		foreach($func as $k=>$v){
			$fields[] = ' '.$this->esc($k).' = '.$v;
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
					if($this->loggingEnabled)
						$this->logger->log('result via iterator cursor');
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
	protected function setPDO($dsn){
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
				$this->isConnected = false;
				throw $exception;
			}
		}
	}
	function getAll( $sql, $bindings = [] ){
		$this->runQuery( $sql, $bindings );
		return $this->resultArray;
	}
	function getRow( $sql, $bindings = [] ){
		$arr = $this->getAll( $sql, $bindings );
		return array_shift( $arr );
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
	
	function begin(){
		$this->connect();
		if(!$this->transactionCount++){
			if($this->loggingEnabled)
				$this->logger->log('TRANSACTION BEGIN');
			return $this->pdo->beginTransaction();
		}
		$this->exec('SAVEPOINT trans'.$this->transactionCount);
		if($this->loggingEnabled)
			$this->logger->log('TRANSACTION SAVEPOINT trans'.$this->transactionCount);
		return $this->transactionCount >= 0;
	}

	function commit(){
		$this->connect();
		if(!--$this->transactionCount){
			if($this->loggingEnabled)
				$this->logger->log('TRANSACTION COMMIT');
			return $this->pdo->commit();
		}
		return $this->transactionCount >= 0;
	}

	function rollback(){
		$this->connect();
		if(--$this->transactionCount){
			if($this->loggingEnabled)
				$this->logger->log('TRANSACTION ROLLBACK TO trans'.$this->transactionCount+1);
			$this->exec('ROLLBACK TO trans'.$this->transactionCount+1);
			return true;
		}
		$this->logger->log('TRANSACTION ROLLBACK');
		return $this->pdo->rollback();
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
		$this->isConnected = null;
	}
	function isConnected(){
		return $this->isConnected;
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
		if(($c=substr_count($sql,'?'))!=($c2=count($binds)))
			throw new Exception('ERROR: Query "'.$sql.'" need '.$c.' parameters, but request give '.$c2);
		return [$sql,$binds];
	}
	
	//QueryWriter
	function adaptStructure($type,$properties,$primaryKey='id',$uniqTextKey=null,$cast=[]){
		if($this->frozen)
			return;
		if(!$this->tableExists($type))
			$this->createTable($type,$primaryKey);
		$columns = $this->getColumns($type);
		foreach($properties as $column=>$value){
			if(!isset($columns[$column])){
				if(isset($cast[$column])){
					$colType = $cast[$column];
					unset($cast[$column]);
				}
				else{
					$colType = $this->scanType($value,true);
				}
				$this->addColumn($type,$column,$colType);
			}
			else{
				$typedesc = $columns[$column];
				$typenoOld = $this->columnCode($typedesc);
				if(isset($cast[$column])){
					$snip = explode(' ',$cast[$column]);
					$snip = $snip[0];
					$typeno = $this->columnCode($snip);
					$colType = $cast[$column];
					unset($cast[$column]);
				}
				else{
					$typeno = $this->scanType($value,false);
					$colType = $typeno;
				}
				if($typenoOld<self::C_DATATYPE_RANGE_SPECIAL&&$typenoOld<$typeno)
					$this->changeColumn($type,$column,$colType);
			}
			if(isset($uniqTextKey)&&$uniqTextKey==$column){
				$this->addUniqueConstraint($type,$column);
			}
		}
		foreach($cast as $column=>$value){
			if(!isset($columns[$column])){
				$this->addColumn($type,$column,$cast[$column]);
			}
			else{
				$typedesc = $columns[$column];
				$typenoOld = $this->columnCode($typedesc);
				$snip = explode(' ',$cast[$column]);
				$snip = $snip[0];
				$typeno = $this->columnCode($snip);
				$colType = $cast[$column];
				if($typenoOld<self::C_DATATYPE_RANGE_SPECIAL&&$typenoOld<$typeno)
					$this->changeColumn($type,$column,$colType);
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
	protected function getReadSnippet($type,$aliasMap=[]){
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
					if(strpos(strtolower($func),' as ')===false){
						$func .= ' AS ';
						if(isset($aliasMap[$property]))
							$func .= $aliasMap[$property];
						else
							$func .= $property;
					}
					$sqlFilters[] = $func;
				}
			}
		}
		return !empty($sqlFilters)?implode(',',$sqlFilters):'';
	}
	protected function getWriteSnippet($type,$property){
		if(isset($this->sqlFiltersWrite[$type][$property])){
			$slot = $this->sqlFiltersWrite[$type][$property];
			if(strpos($slot,'(')===false)
				$slot = $slot.'(?)';
		}
		else{
			$slot = '?';
		}
		return $slot;
	}
	function getReadSnippetCol($type,$col,$s=null){
		if(!$s)
			$s = $this->escTable($type).'.'.$this->esc($col);
		if(isset($this->sqlFiltersRead[$type][$col][0])){
			$func = $this->sqlFiltersRead[$type][$col][0];
			if(strpos($func,'(')===false)
				$s = $func.'('.$s.')';
			else
				$s = str_replace('?',$s,$func);
		}
		return $s;
	}
	
	function check($struct){
		if(!preg_match('/^[a-zA-Z0-9_-]+$/',$struct))
			throw new \InvalidArgumentException('Table or Column name "'.$struct.'" does not conform to DataMap security policies' );
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
	function columnExists($table,$column){
		return in_array($column,array_keys($this->getColumns($table)));
	}
	
	function getColumns($type){
		if(!isset($this->cacheColumns[$type]))
			$this->cacheColumns[$type] = $this->getColumnsQuery($type);
		return $this->cacheColumns[$type];
	}
	function addColumn($type,$column,$field){
		if(isset($this->cacheColumns[$type])){
			if(is_integer($field)){
				$this->cacheColumns[$type][$column] = (false!==$i=array_search($field,$this->sqltype_typeno))?$i:'';
			}
			else{
				$snip = explode(' ',$field);
				$this->cacheColumns[$type][$column] = $snip;
			}
		}
		return $this->addColumnQuery($type,$column,$field);
	}
	function changeColumn($type,$column,$field){
		if(isset($this->cacheColumns[$type])){
			if(is_integer($field)){
				$this->cacheColumns[$type][$column] = (false!==$i=array_search($field,$this->sqltype_typeno))?$i:'';
			}
			else{
				$snip = explode(' ',$field);
				$this->cacheColumns[$type][$column] = $snip;
			}
		}
		return $this->changeColumnQuery($type,$column,$field);
	}
	
	function createTable($type,$pk='id'){
		$table = $this->prefixTable($type);
		if(!in_array($table,$this->cacheTables))
			$this->cacheTables[] = $table;
		return $this->createTableQuery($type,$pk);
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
		$column1 = $this->esc($type.'_'.$pk);
		$column2 = $this->esc($tb.'_'.$pko);
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
		$table->join($tbj.' ON '.$tbj.'.'.$column1.' = '.$typeE.'.'.$pke);
		$table->join($tb.' ON '.$tb.'.'.$pkoe.' = '.$tbj.'.'.$column2
					.' AND '.$tb.'.'.$pkoe.' =  ?',[$obj->$pko]);
		$table->select($typeE.'.*');
		if($sqlFilterStr = $this->getReadSnippet($type))
			$table->select($sqlFilterStr);
		return $table;
	}
	function many2manyLink($obj,$type,$via=null,$viaFk=null){
		$tb = $this->findEntityTable($obj);
		if($via){
			$tbj = $via;
		}
		else{
			$tbj = [$type,$tb];
			sort($tbj);
			$tbj = implode('_',$tbj);
		}
		$table = clone $this[$tbj];
		$typeE = $this->escTable($type);
		$pk = $table->getPrimaryKey();
		$pko = $this[$tb]->getPrimaryKey();
		$column1 = $viaFk?$this->esc($viaFk):$this->esc($type.'_'.$pk);
		$column2 = $this->esc($tb.'_'.$pko);
		$tb = $this->escTable($tb);
		$tbj = $this->escTable($tbj);
		$pke = $this->esc($pk);
		$pkoe = $this->esc($pko);
		$table->join($typeE.' ON '.$tbj.'.'.$column1.' = '.$typeE.'.'.$pke);
		$table->join($tb.' ON '.$tb.'.'.$pkoe.' = '.$tbj.'.'.$column2
					.' AND '.$tb.'.'.$pkoe.' =  ?',[$obj->$pko]);
		$table->select($tbj.'.*');
		return $table;
	}
	
	function one2manyDelete($obj,$type,$except=[]){
		if(!$this->tableExists($type))
			return;
		$typeE = $this->escTable($type);
		$pk = $this[$type]->getPrimaryKey();
		$tb = $this->findEntityTable($obj);
		$pko = $this[$tb]->getPrimaryKey();
		$column = $this->esc($tb.'_'.$pko);
		$notIn = '';
		$params = [$obj->$pko];
		if(!empty($except)){
			$notIn = ' AND '.$pko.' NOT IN ?';
			$params[] = $except;
		}
		$this->execute('DELETE FROM '.$typeE.' WHERE '.$column.' = ?'.$notIn,$params);
	}
	function many2manyDelete($obj,$type,$via=null,$viaFk=null,$except=[]){
		//work in pgsql,sqlite,cubrid but not in mysql (overloaded)
		$tb = $this->findEntityTable($obj);
		if($via){
			$tbj = $via;
		}
		else{
			$tbj = [$type,$tb];
			sort($tbj);
			$tbj = implode('_',$tbj);
		}
		if(!$this->tableExists($tbj))
			return;
		$typeE = $this->escTable($type);
		$pk = $this[$tbj]->getPrimaryKey();
		$pko = $this[$tb]->getPrimaryKey();
		$column1 = $viaFk?$this->esc($viaFk):$this->esc($type.'_'.$pk);
		$column2 = $this->esc($tb.'_'.$pko);
		$tb = $this->escTable($tb);
		$tbj = $this->escTable($tbj);
		$pke = $this->esc($pk);
		$pkoe = $this->esc($pko);
		$notIn = '';
		$params = [$obj->$pko];
		if(!empty($except)){
			$notIn = ' AND '.$tbj.'.'.$pke.' NOT IN ?';
			$params[] = $except;
		}
		$this->execute('DELETE FROM '.$tbj.' WHERE '.$tbj.'.'.$pke.' IN(
			SELECT '.$tbj.'.'.$pke.' FROM '.$tbj.'
			JOIN '.$tb.' ON '.$tb.'.'.$pkoe.' = '.$tbj.'.'.$column2.'
			JOIN '.$typeE.' ON '.$tbj.'.'.$column1.' = '.$typeE.'.'.$pke.'
			AND '.$tb.'.'.$pkoe.' = ? '.$notIn.'
		)',$params);
	}
	
	function getFtsTableSuffix(){
		return $this->ftsTableSuffix;
	}
	
	function getAgg(){
		return $this->agg;
	}
	function getAggCaster(){
		return $this->aggCaster;
	}
	function getSeparator(){
		return $this->separator;
	}
	function getConcatenator(){
		return $this->concatenator;
	}
	
	function explodeAgg($data,$type=null){
		$_gs = chr(0x1D);
		$row = [];
		foreach(array_keys($data) as $col){
			$multi = stripos($col,'>');
			$sep = stripos($col,'<>')?'<>':(stripos($col,'<')?'<':($multi?'>':false));
			if($sep){
				$x = explode($sep,$col);
				$tb = &$x[0];
				$_col = &$x[1];
				if(!isset($row[$tb]))
					$row[$tb] = [];
				if(empty($data[$col])){
					if(!isset($row[$tb]))
						$row[$tb] = $this->entityFactory($tb);
				}
				elseif($multi){
					$_x = explode($_gs,$data[$col]);
					if(isset($data[$tb.$sep.'id'])){
						$_idx = explode($_gs,$data[$tb.$sep.'id']);
						foreach($_idx as $_i=>$_id){
							if(!isset($row[$tb][$_id]))
								$row[$tb][$_id] = $this->entityFactory($tb);
							$row[$tb][$_id]->$_col = $_x[$_i];
						}
					}
					else{
						foreach($_x as $_i=>$v){
							if(!isset($row[$tb][$_i]))
								$row[$tb][$_i] = $this->entityFactory($tb);
							$row[$tb][$_i]->$_col = $v;
						}
					}
				}
				else
					$row[$tb]->$_col = $data[$col];
			}
			else
				$row[$col] = $data[$col];
		}
		if($type)
			$row = $this->arrayToEntity($row,$type);
		return $row;
	}
	function explodeAggTable($data,$type=null){
		$table = [];
		if(is_array($data)||$data instanceof \ArrayAccess)
			foreach($data as $i=>$d){
				$id = isset($d['id'])?$d['id']:$i;
				$table[$id] = $this->explodeAgg($d,$type);
			}
		return $table;
	}
	
	function findRow($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;
		$table = $this->escTable($type);
		if($sqlFilterStr = $this->getReadSnippet($type))
			$sqlFilterStr .= ',';
		$sql = "SELECT {$table}.* {$sqlFilterStr} FROM {$table} {$snip} LIMIT 1";
		return $this->getRow($sql,$bindings);
	}
	function findOne($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;

		$obj = $this->entityFactory($type);
		$this->trigger($type,'beforeRead',$obj);
		
		$snip = 'WHERE '.$snip;
		$row = $this->findRow($type,$snip,$bindings);
		
		if($row){
			foreach($row as $k=>$v)
				$obj->$k = $v;
		}
		$this->trigger($type,'afterRead',$obj);
		if($row)
			return $obj;
	}
	
	function findRows($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;
		$table = $this->escTable($type);
		if($sqlFilterStr = $this->getReadSnippet($type))
			$sqlFilterStr .= ',';
		$sql = "SELECT {$table}.* {$sqlFilterStr} FROM {$table} {$snip}";
		return $this->getAll($sql,$bindings);
	}
	function findAll($type,$snip,$bindings=[]){
		if(!$this->tableExists($type))
			return;
		$rows = $this->findRows($type,$snip,$bindings);
		$all = [];
		foreach($rows as $row){
			$obj = $this->entityFactory($type);
			$this->trigger($type,'beforeRead',$obj);
			foreach($row as $k=>$v){
				$obj->$k = $v;
			}
			$this->trigger($type,'afterRead',$obj);
			$all[] = $obj;
		}
		return $all;
	}
	function find($type,$snip,$bindings=[]){
		return $this->findAll($type,'WHERE '.$snip,$bindings);
	}
	
	function execMultiline($sql,$bindings=[]){
		$this->connect();
		$this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
		$r = $this->execute($sql, $bindings);
		$this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
		return $r;
	}
	
	function findOrNewOne($type,$params=[]){
		$query = [];
		$bind = [];
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
			if($row = $this->findOne($t,$query,$bind))
				break;
		}
		if(!$row){
			$row = $this->arrayToEntity($params,array_pop($type));
		}
		return $row;
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