<?php
run();

function run(){
	init();
	installComposer();
	
	$composer = getComposerBin();
	passthru("$composer install 2>&1");
	if(!unlink(__FILE__))
		echo 'Unable to remove installation script, you should remove it manually';
}

function init(){
	ignore_user_abort(false);
	set_time_limit(0);
	if(php_sapi_name()!='cli'){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
		header("Last-Modified: " . gmdate("D, d M Y H:i:s" ) . " GMT" );
		header("Pragma: no-cache");
		header("Cache-Control: no-cache");
		header("Expires: -1");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Cache-Control: no-store, no-cache, must-revalidate");
		ob_implicit_flush(true);
		@ob_end_flush();
		echo str_repeat(" ",1024);
		echo '<pre>';
	}
	if(!is_dir($d=getcwd().'/.tmp/composer'))
		mkdir($d,0777,true);
	putenv("COMPOSER_HOME=".$d);
}

function getComposerBin(){
	if(strtoupper(substr(PHP_OS, 0, 3))==='WIN'){
		if(file_exists($b='C:\\bin\\composer.bat'))
			return $b;
		if(file_exists($b='C:\\bin\\composer.phar'))
			return $b;
	}
	else{
		if(file_exists($b='/usr/local/bin/composer'))
			return $b;
		if(file_exists($b='/usr/local/bin/composer.phar'))
			return $b;
	}
	if(file_exists($b='composer'))
		return "php $b";
	if(file_exists($b='composer.phar'))
		return "php $b";
}

function installComposer(){
	if(getComposerBin()) return;
	
	$composerPhar = 'composer.phar';
	$composerSetup = 'composer-setup.php';
	echo "Downloading composer installer\n";
	$composerSetupContent = fopen('https://getcomposer.org/installer','r');
	if(!$composerSetupContent){
		echo "An error occured, unable to download composer installer\n";
		return;
	}
	file_put_contents($composerSetup,$composerSetupContent);
	if(!file_exists($composerSetup)){
		echo "An error occured, unable to write composer installer, it\'s probably a rights problem\n";
		return;
	}
	passthru('php '.$composerSetup.' 2>&1');
	
	unlink($composerSetup);
	if(!file_exists($composerPhar)){
		echo "An error occured, unable to install a local composer\n";
		return;
	}

	echo "Local composer installed, you can use it from the root path of your application\n";
	rename($composerPhar,'composer');
}