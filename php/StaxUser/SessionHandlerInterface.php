<?php namespace StaxUser;
interface SessionHandlerInterface extends \SessionHandlerInterface{
	function touch($id);
}