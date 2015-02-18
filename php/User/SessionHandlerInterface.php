<?php namespace Surikat\User;
interface SessionHandlerInterface extends \SessionHandlerInterface{
	function setKey($key);
	function destroyKey($key);
	function regenerateId();
	function generateId();
}