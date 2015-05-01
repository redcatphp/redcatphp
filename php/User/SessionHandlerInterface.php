<?php namespace User;
interface SessionHandlerInterface extends \SessionHandlerInterface{
	function touch($id);
}