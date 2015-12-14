<?php namespace RedCat\Identify;
interface SessionHandlerInterface extends \SessionHandlerInterface{
	function touch($id);
}