<?php namespace Surikat\Service;
use Surikat\DependencyInjection\MutatorProperty;
class ServicePhpinfo{
	use MutatorProperty;
	function __invoke(){
		$this->User_Auth->lockServer($this->User_Auth->constant('RIGHT_MANAGE'));
		phpinfo();
	}
}