<?php namespace surikat\service;
use surikat\control;
use surikat\control\GitDeploy\GitDeploy;
class Service_Deploy{
	static function method(){
		GitDeploy::main();
	}
	static function autocommit(){ //need the .git have recursively full permission (www-data have to be able to write)
		$message = "auto commit by service deploy - ".strftime('%A %e %B %G - %k:%M:%S',time());
		$cmd = 'cd '.control::$CWD.' && git add . && git commit -m "$message" ';
		echo $cmd."\r\n";
		echo shell_exec($cmd);
		GitDeploy::main();
	}
}