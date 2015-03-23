<?php namespace Surikat\Component\User;
use SessionHandlerInterface;
interface SessionHandler extends SessionHandlerInterface{
	function touch($id);
}