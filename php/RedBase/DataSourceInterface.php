<?php
namespace RedBase;
interface DataSourceInterface extends \ArrayAccess{
	function findEntityClass($name);
}