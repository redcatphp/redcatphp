<?php namespace Surikat\Service;
use Suriakt\Tool\Auth;
class ServicePhpinfo{
	static function method(){
		Auth::lockServer(Auth::RIGHT_MANAGE);
		phpinfo();
	}
}