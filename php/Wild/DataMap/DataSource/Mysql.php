<?php
namespace Wild\DataMap\DataSource;
use Wild\DataMap\Exception;
class Mysql extends SQL{
	const C_DATATYPE_BOOL             = 0;
	const C_DATATYPE_UINT32           = 1;
	const C_DATATYPE_UBIGINT          = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT7            = 4; //InnoDB cant index varchar(255) utf8mb4 - so keep 191 as long as possible
	const C_DATATYPE_TEXT8            = 5;
	const C_DATATYPE_TEXT16           = 6;
	const C_DATATYPE_TEXT32           = 7;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LINESTRING = 91;
	const C_DATATYPE_SPECIAL_POLYGON    = 92;
	const C_DATATYPE_SPECIFIED          = 99;
	protected $unknownDatabaseCode = 1049;
	protected $quoteCharacter = '`';
	protected $isMariaDB;
	protected $version;
	
	protected $separator = 'SEPARATOR';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $sumCaster = '';
	protected $concatenator = '0x1D';
	
	protected $fluidPDO;
	
	function construct(array $config=[]){
		parent::construct($config);
		$this->typeno_sqltype = [
			self::C_DATATYPE_BOOL             => ' TINYINT(1) UNSIGNED ',
			self::C_DATATYPE_UINT32           => ' INT(11) UNSIGNED ',
			self::C_DATATYPE_UBIGINT          => ' BIGINT(20) UNSIGNED ',
			self::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			self::C_DATATYPE_TEXT7            => ' VARCHAR(191) ',
			self::C_DATATYPE_TEXT8	           => ' VARCHAR(255) ',
			self::C_DATATYPE_TEXT16           => ' TEXT ',
			self::C_DATATYPE_TEXT32           => ' LONGTEXT ',
			self::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
			self::C_DATATYPE_SPECIAL_POINT    => ' POINT ',
			self::C_DATATYPE_SPECIAL_LINESTRING => ' LINESTRING ',
			self::C_DATATYPE_SPECIAL_POLYGON => ' POLYGON ',
		];
		foreach($this->typeno_sqltype as $k=>$v){
			$this->sqltype_typeno[trim(strtolower($v))] = $k;
		}
	}
	function connect(){
		if($this->isConnected)
			return;
		parent::connect();
		$serverVersion = $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
		$this->isMariaDB = strpos($serverVersion,'MariaDB')!==false;
		if($this->isMariaDB)
			$this->version = substr($serverVersion,0,strpos($serverVersion,'-'));
		else
			$this->version = floatval($serverVersion);
		if(!$this->isMariaDB&&$this->version>=5.5)
			$this->encoding =  'utf8mb4';
		$this->pdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '.$this->encoding); //on every re-connect
		$this->pdo->exec('SET NAMES '. $this->encoding); //also for current connection
	}
	function createDatabase($dbname){
		$this->pdo->exec('CREATE DATABASE `'.$dbname.'` COLLATE \'utf8_bin\'');
	}
	function scanType($value,$flagSpecial=false){
		if(is_null( $value ))
			return self::C_DATATYPE_BOOL;
		if($value === INF)
			return self::C_DATATYPE_TEXT7;
		if($flagSpecial){
			if(preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATE;
			if(preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) )
				return self::C_DATATYPE_SPECIAL_DATETIME;
			if(preg_match( '/^POINT\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_POINT;
			if(preg_match( '/^LINESTRING\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_LINESTRING;
			if(preg_match( '/^POLYGON\(/', $value ) )
				return self::C_DATATYPE_SPECIAL_POLYGON;
		}
		//setter turns TRUE FALSE into 0 and 1 because database has no real bools (TRUE and FALSE only for test?).
		if( $value === FALSE || $value === TRUE || $value === '0' || $value === '1' )
			return self::C_DATATYPE_BOOL;
		if( !$this->startsWithZeros( $value ) ) {
			if( is_numeric( $value ) && floor($value)==$value && $value>=0 && $value <= 4294967295 )
				return self::C_DATATYPE_UINT32;
			elseif ( is_numeric( $value ) && floor($value)==$value && $value>0 && $value <= 18446744073709551615 )
				return self::C_DATATYPE_UBIGINT;
			if( is_numeric( $value ) )
				return self::C_DATATYPE_DOUBLE;
		}
		if( is_float( $value ) )
			return self::C_DATATYPE_DOUBLE;
		if( mb_strlen( $value, 'UTF-8' ) <= 191 )
			return self::C_DATATYPE_TEXT7;
		if( mb_strlen( $value, 'UTF-8' ) <= 255 )
			return self::C_DATATYPE_TEXT8;
		if( mb_strlen( $value, 'UTF-8' ) <= 65535 )
			return self::C_DATATYPE_TEXT16;
		return self::C_DATATYPE_TEXT32;
	}
	function getTablesQuery(){
		return $this->getCol('SHOW TABLES');
	}
	function getColumnsQuery($type){
		$columns = [];
		foreach($this->getAll('DESCRIBE '.$this->escTable($type)) as $r)
			$columns[$r['Field']] = $r['Type'];
		return $columns;
	}
	
	function getFluidPDO(){
		if(!isset($this->fluidPDO)){
			$this->fluidPDO = new \PDO($this->dsn,$this->connectUser,$this->connectPass);
			$this->fluidPDO->setAttribute( \PDO::ATTR_STRINGIFY_FETCHES, TRUE );
			$this->fluidPDO->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
			$this->fluidPDO->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
			if(!empty($this->options)) foreach($this->options as $opt=>$attr) $this->fluidPDO->setAttribute($opt,$attr);
		}
		return $this->fluidPDO;
	}
	function executeFluid($sql,$bindings=[]){
		$pdo = $this->pdo;
		$this->pdo = $this->getFluidPDO();
		$this->execute($sql,$bindings);
		$this->pdo = $pdo;
	}
	
	function createTableQuery($table,$pk='id'){
		$table = $this->escTable($table);
		$pk = $this->esc($pk);
		$encoding = $this->getEncoding();
		$this->executeFluid('CREATE TABLE '.$table.' ('.$pk.' INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( '.$pk.' )) ENGINE = InnoDB DEFAULT CHARSET='.$encoding.' COLLATE='.$encoding.'_unicode_ci ');
	}
	function addColumnQuery($type,$column,$field){
		$table  = $type;
		$type   = $field;
		$table  = $this->escTable($table);
		$column = $this->esc($column);
		if(is_integer($type))
			$type = isset($this->typeno_sqltype[$type])?$this->typeno_sqltype[$type]:'';
		$this->executeFluid('ALTER TABLE '.$table.' ADD '.$column.' '.$type);
	}
	function changeColumnQuery($type,$property,$dataType ){
		$table   = $this->escTable( $type );
		$column  = $this->esc( $property );
		if(is_integer($dataType)){
			if(!isset($this->typeno_sqltype[$dataType]))
				return false;
			$dataType = $this->typeno_sqltype[$dataType];
		}
		$this->executeFluid('ALTER TABLE '.$table.' CHANGE '.$column.' '.$column.' '.$dataType);
		return true;
	}
	
	function addFK( $type, $targetType, $property, $targetProperty, $isDependent = false ){
		$table = $this->escTable( $type );
		$targetTable = $this->escTable( $targetType );
		$targetTableNoQ = $this->prefixTable( $targetType );
		$field = $this->esc( $property );
		$fieldNoQ = $this->check( $property);
		$targetField = $this->esc( $targetProperty );
		$targetFieldNoQ = $this->check( $targetProperty );
		$tableNoQ = $this->prefixTable( $type );
		$fieldNoQ = $this->check( $property);
		$casc = ( $isDependent ? 'CASCADE' : 'SET NULL' );
		$fk = $this->getForeignKeyForTypeProperty( $type, $fieldNoQ );
		if ( !is_null( $fk )
			&&($fk['on_update']==$casc||$fk['on_update']=='CASCADE')
			&&($fk['on_delete']==$casc||$fk['on_delete']=='CASCADE')
		)
			return false;

		//Widen the column if it's incapable of representing a foreign key (at least INT).
		$columns = $this->getColumns( $type );
		$idType = $this->getTypeForID();
		if ( $this->columnCode( $columns[$fieldNoQ] ) !==  $idType ) {
			$this->changeColumn( $type, $property, $idType );
		}

		$fkName = 'fk_'.$tableNoQ.'_'.$fieldNoQ;
		$cName = 'c_'.$fkName;
		try {
			$this->executeFluid( "
				ALTER TABLE {$table}
				ADD CONSTRAINT $cName
				FOREIGN KEY $fkName ( {$fieldNoQ} ) REFERENCES {$targetTableNoQ}
				({$targetFieldNoQ}) ON DELETE " . $casc . ' ON UPDATE '.$casc.';');
		} catch ( \PDOException $e ) {
			// Failure of fk-constraints is not a problem
		}
	}
	function getKeyMapForType($type){
		$table = $this->prefixTable( $type );
		$keys = $this->getAll('
			SELECT
				information_schema.key_column_usage.constraint_name AS `name`,
				information_schema.key_column_usage.referenced_table_name AS `table`,
				information_schema.key_column_usage.column_name AS `from`,
				information_schema.key_column_usage.referenced_column_name AS `to`,
				information_schema.referential_constraints.update_rule AS `on_update`,
				information_schema.referential_constraints.delete_rule AS `on_delete`
				FROM information_schema.key_column_usage
				INNER JOIN information_schema.referential_constraints
					ON (
						information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
						AND information_schema.referential_constraints.constraint_schema = information_schema.key_column_usage.constraint_schema
						AND information_schema.referential_constraints.constraint_catalog = information_schema.key_column_usage.constraint_catalog
					)
			WHERE
				information_schema.key_column_usage.table_schema IN ( SELECT DATABASE() )
				AND information_schema.key_column_usage.table_name = ?
				AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
				AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
		', [$table]);
		$keyInfoList = [];
		foreach ( $keys as $k ) {
			$label = self::makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $k['name'],
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}
	function columnCode($typedescription, $includeSpecials = FALSE ){
		$typedescription = strtolower($typedescription);
		if ( isset( $this->sqltype_typeno[$typedescription] ) )
			$r = $this->sqltype_typeno[$typedescription];
		else
			$r = self::C_DATATYPE_SPECIFIED;
		if ( $includeSpecials )
			return $r;
		if ( $r >= self::C_DATATYPE_RANGE_SPECIAL )
			return self::C_DATATYPE_SPECIFIED;
		return $r;
	}
	function getTypeForID(){
		return self::C_DATATYPE_UINT32;
	}
	function addUniqueConstraint( $type, $properties ){
		$tableNoQ = $this->prefixTable( $type );
		$columns = [];
		foreach( (array)$properties as $key => $column )
			$columns[$key] = $this->esc( $column );
		$table = $this->escTable( $type );
		sort($columns);
		$name = 'uq_' . sha1( implode( ',', $columns ) );
		$indexMap = $this->getRow('SHOW indexes FROM '.$table.' WHERE Key_name = ?',[$name]);
		if(is_null($indexMap))
			$this->executeFluid("ALTER TABLE $table ADD UNIQUE INDEX `$name` (" . implode( ',', $columns ) . ")");
	}
	function addIndex( $type, $name, $property ){
		try {
			$table  = $this->escTable( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->esc( $property );
			$this->executeFluid("CREATE INDEX $name ON $table ($column) ");
			return true;
		}
		catch( \PDOException $e ){
			return false;
		}
	}
	
	function clear($type){
		$table = $this->escTable($type);
		$this->execute('TRUNCATE '.$table);
	}
	protected function _drop($type){
		$t = $this->escTable($type);
		$this->execute('SET FOREIGN_KEY_CHECKS = 0;');
		try{
			$this->execute('DROP TABLE IF EXISTS '.$t);
		}
		catch(\PDOException $e){}
		try{
			$this->execute('DROP VIEW IF EXISTS '.$t);
		}
		catch(\PDOException $e){}
		$this->execute('SET FOREIGN_KEY_CHECKS = 1;');
	}
	protected function _dropAll(){
		$this->execute('SET FOREIGN_KEY_CHECKS = 0;');
		foreach($this->getTables() as $t){
			try{
				$this->execute("DROP TABLE IF EXISTS `$t`");
			}
			catch(\PDOException $e){}
			try{
				$this->execute("DROP VIEW IF EXISTS `$t`");
			}
			catch(\PDOException $e){}
		}
		$this->execute('SET FOREIGN_KEY_CHECKS = 1;');
	}
	
	protected function explain($sql,$bindings=[]){
		$sql = ltrim($sql);
		if(!in_array(strtoupper(substr($sql,0,6)),['SELECT','DELETE','INSERT','UPDATE'])
			&&strtoupper(substr($sql,0,7))!='REPLACE')
			return false;
		$explain = $this->pdo->prepare('EXPLAIN EXTENDED '.$sql);
		$this->bindParams($explain,$bindings);
		$explain->execute();
		$explain = $explain->fetchAll();
		$i = 0;
		return implode("\n",array_map(function($entry)use(&$i){
			$indent = str_repeat('  ',$i);
			$s = '';
			if(isset($entry['id']))
				$s .= $indent.$entry['id'].'|';
			foreach($entry as $k=>$v){
				if($k!='id'&&$k!='Extra'&&!is_null($v))
					$s .= $indent.$k.':'.$v.'|';
			}
			if(isset($entry['Extra']))
				$s .= $indent.$entry['Extra'];
			else
				$s = rtrim($s,'|');
			$i++;
			return $s;
		}, $explain));
	}
	
	function getFkMap($type,$primaryKey='id'){
		$table = $this->prefixTable($type);
		$dbname = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
		$this->pdo->exec('use INFORMATION_SCHEMA');
		$fks = $this->getAll('SELECT table_name AS "table",column_name AS "column",constraint_name AS "constraint" FROM key_column_usage WHERE table_schema = "'.$dbname.'" AND referenced_table_name = "'.$table.'" AND referenced_column_name = "'.$primaryKey.'";');
		$this->pdo->exec('use '.$dbname);
		foreach($fks as &$fk){
			$constraint = $this->getRow('
				SELECT
					information_schema.referential_constraints.update_rule AS `on_update`,
					information_schema.referential_constraints.delete_rule AS `on_delete`
					FROM information_schema.key_column_usage
					INNER JOIN information_schema.referential_constraints
						ON (
							information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
							AND information_schema.referential_constraints.constraint_schema = information_schema.key_column_usage.constraint_schema
							AND information_schema.referential_constraints.constraint_catalog = information_schema.key_column_usage.constraint_catalog
						)
				WHERE
					information_schema.key_column_usage.table_schema IN ( SELECT DATABASE() )
					AND information_schema.key_column_usage.table_name = ?
					AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
					AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
					AND information_schema.key_column_usage.constraint_name = ?
			',[$this->prefixTable($fk['table']),$fk['constraint']]);
			$fk['on_update'] = $constraint['on_update'];
			$fk['on_delete'] = $constraint['on_delete'];
		}
		return $fks;
	}
	
	function adaptPrimaryKey($type,$id,$primaryKey='id'){
		if($id!=4294967295)
			return;
		$cols = $this->getColumns($type);
		if($cols[$primaryKey]=='bigint(20) unsigned')
			return;
		$table = $this->escTable($type);
		$pk = $this->esc($primaryKey);
		$fks = $this->getFkMap($type,$primaryKey);
		$lockTables = 'LOCK TABLES '.$table.' WRITE';
		foreach($fks as $fk){
			$lockTables .= ',`'.$fk['table'].'` WRITE';
		}
		$this->execute($lockTables);
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` DROP FOREIGN KEY `'.$fk['constraint'].'`, MODIFY `'.$fk['column'].'` bigint(20) unsigned NULL');
		}
		$this->execute('ALTER TABLE '.$table.' CHANGE '.$pk.' '.$pk.' bigint(20) unsigned NOT NULL AUTO_INCREMENT');
		foreach($fks as $fk){
			$this->execute('ALTER TABLE `'.$fk['table'].'` ADD FOREIGN KEY (`'.$fk['column'].'`) REFERENCES '.$table.' ('.$pk.') ON DELETE '.$fk['on_delete'].' ON UPDATE '.$fk['on_update']);
		}
		$this->execute('UNLOCK TABLES');
		if($this->tableExists($type.$this->ftsTableSuffix))
			$this->execute('ALTER TABLE '.$this->escTable($type.$this->ftsTableSuffix).' CHANGE '.$pk.' '.$pk.' bigint(20) unsigned NOT NULL AUTO_INCREMENT');
	}
	
	function fulltextAvailableOnInnoDB(){
		return false;
		$this->connect();
		if($this->isMariaDB)
			return version_compare($this->version,'10.0.5','>=');
		else
			return $this->version>=5.6;
	}
	
	function getFtsMap($type){
		$table = $this->prefixTable($type);
		$all = $this->getAll("SELECT GROUP_CONCAT(DISTINCT column_name) AS columns, INDEX_NAME AS name FROM information_schema.STATISTICS WHERE table_schema = (SELECT DATABASE()) AND table_name = '$table' AND index_type = 'FULLTEXT'");
		$map = [];
		foreach($all as $index){
			$col = explode(',',$index['columns']);
			sort($col);
			$map[$index['name']] = $col;
		}
		return $map;
	}
	function autoFillTextColumns($type,$uniqTextKey){
		$sufxL = -1*strlen($this->ftsTableSuffix);
		$columns = [];
		foreach($this->getColumns($type) as $col=>$colType){
			if((strtolower(substr($colType,0,7))=='varchar'||strtolower($colType)=='text'||strtolower($colType=='longtext'))
				&&($col==$uniqTextKey||substr($col,$sufxL)==$this->ftsTableSuffix))
				$columns[] = $col;
		}
		return $columns;
	}
	function addFtsIndex($type,&$columns=[],$primaryKey='id',$uniqTextKey='uniq'){
		$table = $this->escTable($type);
		$ftsMap = $this->getFtsMap($type);
		if(empty($columns)){
			$columns = $this->autoFillTextColumns($type,$uniqTextKey);
			if(empty($columns))
				throw new Exception('Unable to find columns from "'.$table.'" to create FTS table "'.$ftsTable.'"');
			$indexName = '_auto';
			sort($columns);
			if(isset($ftsMap[$indexName])&&$ftsMap[$indexName]!==$columns){
				$this->execute('ALTER TABLE '.$table.' DROP INDEX `'.$indexName.'`');
				unset($ftsMap[$indexName]);
			}
		}
		else{
			sort($columns);
			$indexName = implode('_',$columns);
		}
		if(!in_array($columns,$ftsMap))
			$this->execute('ALTER TABLE '.$table.' ADD FULLTEXT `'.$indexName.'` (`'.implode('`,`',$columns).'`)');
	}
	function makeFtsTableAndIndex($type,&$columns=[],$primaryKey='id',$uniqTextKey='uniq'){
		$table = $this->escTable($type);
		$ftsType = $type.$this->ftsTableSuffix;
		$ftsTable = $this->escTable($ftsType);
		$ftsMap = $this->getFtsMap($ftsType);
		if(empty($columns)){
			$columns = $this->autoFillTextColumns($type,$uniqTextKey);
			if(empty($columns))
				throw new Exception('Unable to find columns from "'.$table.'" to create FTS table "'.$ftsTable.'"');
			$indexName = '_auto';
			sort($columns);
			if(isset($ftsMap[$indexName])&&$ftsMap[$indexName]!==$columns){
				$this->execute('ALTER TABLE '.$ftsTable.' DROP INDEX `'.$indexName.'`');
				unset($ftsMap[$indexName]);
			}
		}
		else{
			sort($columns);
			$indexName = implode('_',$columns);
		}
		$pTable = $this->prefixTable($type);
		$exist = $this->tableExists($ftsType);
		$makeColumns = $columns;
		if($exist){
			$oldColumns = array_keys($this->getColumns($ftsType));
			foreach($columns as $col){
				if(!in_array($col,$oldColumns)){
					$this->execute('DROP TABLE '.$ftsTable);
					foreach($oldColumns as $col){
						if(!in_array($col,$makeColumns))
							$makeColumns[] = $col;
					}
					$exist = false;
					break;
				}
			}
		}
		if(!$exist){
			$pk = $this->esc($primaryKey);
			$cols = '`'.implode('`,`',$columns).'`';
			$newCols = 'NEW.`'.implode('`,NEW.`',$columns).'`';
			$setCols = '';
			foreach($columns as $col){
				$setCols .= '`'.$col.'`=NEW.`'.$col.'`,';
			}
			$setCols = rtrim($setCols,',');
			$encoding = $this->getEncoding();
			$colsDef = implode(' TEXT NULL,',$columns).' TEXT NULL';
			$this->execute('CREATE TABLE '.$ftsTable.' ('.$pk.' INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, '.$colsDef.' ) ENGINE = MyISAM DEFAULT CHARSET='.$encoding.' COLLATE='.$encoding.'_unicode_ci ');
			try{
				$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_insert');
				$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_update');
				$this->execute('DROP TRIGGER IF EXISTS '.$pTable.'_delete');
				$this->execute("CREATE TRIGGER {$pTable}_insert AFTER INSERT ON {$table} FOR EACH ROW INSERT INTO {$ftsTable}({$pk}, {$cols}) VALUES(NEW.{$pk}, {$newCols})");
				$this->execute("CREATE TRIGGER {$pTable}_update AFTER UPDATE ON {$table} FOR EACH ROW UPDATE {$ftsTable} SET {$setCols} WHERE {$pk}=OLD.{$pk}");
				$this->execute("CREATE TRIGGER {$pTable}_delete AFTER DELETE ON {$table} FOR EACH ROW DELETE FROM {$ftsTable} WHERE {$pk}=OLD.{$pk};");
				$this->execute('INSERT INTO '.$ftsTable.'('.$pk.','.$cols.') SELECT '.$pk.','.$cols.' FROM '.$table);
			}
			catch(\PDOException $e){
				if($this->loggingEnabled){
					$code = $e->getCode();
					if(((string)(int)$code)!==((string)$code)&&isset($e->errorInfo)&&isset($e->errorInfo[1]))
						$code = $e->errorInfo[1];
					if((int)$code==1419){
						$this->logger->log("To fix this, in a shell, try: mysql -u USERNAME -p \nset global log_bin_trust_function_creators=1;");
					}
				}
				$this->execute('DROP TABLE '.$ftsTable);
				throw $e;
			}
		}
		if(!in_array($columns,$ftsMap))
			$this->execute('ALTER TABLE '.$ftsTable.' ADD FULLTEXT `'.$indexName.'` (`'.implode('`,`',$columns).'`)');
	}
	
	function many2manyDelete($obj,$type,$via=null,$viaFk=null,$except=[]){
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
		$colmun1 = $viaFk?$this->esc($viaFk):$this->esc($type.'_'.$pk);
		$colmun2 = $this->esc($tb.'_'.$pko);
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
		$this->execute('DELETE FROM '.$tbj.' USING('.$tbj.')
			JOIN '.$tb.' ON '.$tb.'.'.$pkoe.' = '.$tbj.'.'.$colmun2.'
			JOIN '.$typeE.' ON '.$tbj.'.'.$colmun1.' = '.$typeE.'.'.$pke.'
			AND '.$tb.'.'.$pkoe.' = ? '.$notIn.'
		',$params);
	}
}