<?php namespace surikat\model\RedBeanPHP\QueryWriter;
use surikat\model\RedBeanPHP\QueryWriter\PostgreSQL;
class PostgreSQL8BC extends PostgreSQL{ //Backward Compatibility for v8
	protected $agg = 'array_to_string(array_agg';
	protected $separator = '),';
}