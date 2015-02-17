<?php namespace Surikat\Service;
use Suriakt\User\Auth;
class ServicePhpinfo{
	static function method(){
		Auth::lockServer(Auth::RIGHT_MANAGE);
		phpinfo();
	}
}