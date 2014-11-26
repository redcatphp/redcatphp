<?php namespace Surikat\Model\RedBeanPHP\QueryWriter;
use Surikat\Model\RedBeanPHP\QueryWriter\PostgreSQL;
class PostgreSQL8BC extends PostgreSQL{ //Backward Compatibility for v8
	protected $agg = 'array_to_string(array_agg';
	protected $separator = '),';
}