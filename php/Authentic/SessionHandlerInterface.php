<?php namespace Authentic;
interface SessionHandlerInterface extends \SessionHandlerInterface{
	function touch($id);
}