<?php namespace Surikat\Component\User;
interface SessionHandlerInterface extends \SessionHandlerInterface{
	function touch($id);
}