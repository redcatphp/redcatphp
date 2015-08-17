<?php namespace Wild\Identify;
interface SessionHandlerInterface extends \SessionHandlerInterface{
	function touch($id);
}