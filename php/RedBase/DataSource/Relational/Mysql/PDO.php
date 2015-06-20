<?php
namespace RedBase\DataSource\Relational\Mysql;
use RedBase\DataSource\Relational\AbstractPDO;
class PDO extends AbstractPDO{
	protected $unknownDatabaseCode = 1049;
	function connect(){
		if($this->isConnected)
			return;
		parent::connect();
		$version = floatval( $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION ) );
		if($version >= 5.5)
			$this->encoding =  'utf8mb4';
		$this->pdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '.$encoding ); //on every re-connect
		$this->pdo->exec(' SET NAMES '. $encoding); //also for current connection
	}
}