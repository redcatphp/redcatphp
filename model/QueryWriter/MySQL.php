<?php namespace surikat\model\QueryWriter;
class MySQL extends \surikat\model\RedBeanPHP\QueryWriter\MySQL {
	protected $separator = 'SEPARATOR';
	protected $agg = 'GROUP_CONCAT';
	protected $aggCaster = '';
	protected $sumCaster = '';
	protected $concatenator = '0x1D';
	use AHelper;
}