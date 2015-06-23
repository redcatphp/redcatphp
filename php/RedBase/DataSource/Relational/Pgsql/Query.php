<?php
namespace RedBase\DataSource\Relational\Pgsql;
class Query extends \RedBase\DataSource\Relational\AbstractQuery{
	protected $defaultValue = 'DEFAULT';
	protected function getInsertSuffix( $primaryKey ){
		return 'RETURNING '.$primaryKey.' ';
	}
	
}