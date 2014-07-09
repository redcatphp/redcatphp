<?php namespace surikat\model\QueryWriter;
class PostgreSQL extends \surikat\model\RedBeanPHP\QueryWriter\PostgreSQL {
	protected $separator = ',';
	protected $agg = 'string_agg';
	protected $aggCaster = '::text';
	protected $sumCaster = '::int';
	protected $concatenator = 'chr(29)';
	use AHelper;
}