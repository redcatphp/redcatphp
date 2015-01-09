<?php namespace Surikat\Service;
use Surikat\Tool\GitDeploy\GitDeploy;
use Surikat\Core\HTTP;
class ServiceDeploy{
	protected static function directOutput(){
		set_time_limit(0);
		HTTP::nocacheHeaders();
		ob_implicit_flush(true);
		@ob_end_flush();
		echo '<pre>';
	}
	static function method(){
		self::directOutput();
		GitDeploy::factory(SURIKAT_PATH)
			->maintenanceOn()
			->getChild(SURIKAT_SPATH)
				->deploy()
			->getOrigin()
				->autocommit()
				->deploy()
				->maintenanceOff()
		;
	}
	static function single(){
		self::directOutput();
		GitDeploy::factory(SURIKAT_PATH)
			->maintenanceOn()
			->autocommit()
			->deploy()
			->maintenanceOff()
		;
	}
	static function version(){
		self::directOutput();
		GitDeploy::factory()
			->maintenanceOn()
			->deploy(SURIKAT_PATH)
			->maintenanceOff()
		;
	}
	static function shared(){
		self::directOutput();
		GitDeploy::factory(SURIKAT_PATH)
			->maintenanceOn()
			->autocommit()
			->deploy()
			->getParent(SURIKAT_SPATH)
				->deploy()
			->getOrigin()
				->maintenanceOff()
		;
	}
	static function surikat(){
		self::directOutput();
		GitDeploy::factory(SURIKAT_SPATH)
			->maintenanceOn()
			->getParent()
				->deploy()
			->getOrigin()
				->maintenanceOff()
		;
	}
}