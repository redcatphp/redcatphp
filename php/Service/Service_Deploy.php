<?php namespace Surikat\Service;
use Surikat\Tool\GitDeploy\GitDeploy;
class Service_Deploy{
	protected static function directOutput(){
		set_time_limit(0);
		ob_implicit_flush(true);
		@ob_end_flush();
		echo '<pre>';
	}
	static function method(){
		self::directOutput();
		GitDeploy::factory(SURIKAT_PATH)
			->maintenanceOn()
			->autocommit()
			->deploy()
			->getChild(SURIKAT_SPATH)
				->deploy()
			->getOrigin()
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