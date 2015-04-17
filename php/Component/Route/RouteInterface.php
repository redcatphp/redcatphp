<?php namespace Surikat\Component\Route;
use Surikat\Component\DependencyInjection\MutatorTrait;
interface RouteInterface extends \ArrayAccess,\Countable{
	function getPath();
	function getParams();
	function count();
	function __set($k,$v);
	function __get($k);
	function __isset($k);
	function __unset($k);
	static function __set_state($a);
}