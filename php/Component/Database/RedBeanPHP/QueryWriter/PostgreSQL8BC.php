<?php namespace Surikat\Component\Database\RedBeanPHP\QueryWriter;
use Surikat\Component\Database\RedBeanPHP\QueryWriter\PostgreSQL;
class PostgreSQL8BC extends PostgreSQL{ //Backward Compatibility for v8
	protected $agg = 'array_to_string(array_agg';
	protected $separator = '),';
}