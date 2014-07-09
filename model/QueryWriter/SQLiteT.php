<?php namespace surikat\model\QueryWriter;
class SQLiteT extends \surikat\model\RedBeanPHP\QueryWriter\SQLiteT {
	protected $separator = ',';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $sumCaster = '';
	protected $concatenator = "cast(X'1D' as text)";
	use AHelper;
}