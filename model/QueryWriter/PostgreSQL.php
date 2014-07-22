<?php namespace surikat\model\QueryWriter;
use surikat\model\RedBeanPHP\Adapter as Adapter;
class PostgreSQL extends \surikat\model\RedBeanPHP\QueryWriter\PostgreSQL {
	use AQueryWriter;
	protected $separator = ',';
	protected $agg = 'string_agg';
	protected $aggCaster = '::text';
	protected $sumCaster = '::int';
	protected $concatenator = 'chr(29)';
	protected $addTypes = array(
		'tsvector',
	);
	function __construct(Adapter $adapter){
		parent::__construct($adapter);
		$this->addTypes();
	}
}