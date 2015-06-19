<?php
namespace RedBase\DataSource\Relational\Mysql;
use RedBase\DataSource\Relational\AbstractPDO;
class PDO extends AbstractPDO{
	protected $encoding = '';
	function getEncoding(){
		return $this->encoding;
	}
	function connect(){
		if($this->isConnected)
			return;
		parent::connect();
		$driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME );
		$version = floatval( $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION ) );
		if ($driver === 'mysql') {
			$encoding = ($version >= 5.5) ? 'utf8mb4' : 'utf8';
			$this->pdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '.$encoding ); //on every re-connect
			$this->pdo->exec(' SET NAMES '. $encoding); //also for current connection
			$this->encoding = $encoding;
		}
	}
}