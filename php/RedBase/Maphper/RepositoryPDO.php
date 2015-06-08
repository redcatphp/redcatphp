<?php
namespace RedBase\Maphper;
class RepositoryPDO extends Repository{
	private $pdo;
	private $options;
	protected $factory;
	function __construct($dsn,$user=null,$password=null,$options=null){
		$this->pdo = new \PDO($dsn,$user,$password);
		$this->factory = [$this,'dbFactory'];
		$this->options = $options;
	}
	function dbFactory($table,$primary){
		return new DataSource\Database($this->pdo, $table, $primary, $this->options);
	}
}