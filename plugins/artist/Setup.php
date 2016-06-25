<?php
namespace MyApp\Artist;
use RedCat\Artist\ArtistPlugin;
use RedCat\Framework\PHPConfig\TokenTree;
class Setup extends ArtistPlugin{
	protected $description = "Finalize installation";
	protected $args = [];
	protected $opts = [];
	
	protected $mainDbnameDefault = "redcat-db";
	protected $gitEmailDefault = "";
	protected $gitNameDefault = "";
	
	protected function exec(){
		$this->runCmd('asset:jsalias');
		if(is_file($f=$this->cwd.'packages/.redcat-installed')) return;
		$this->runCmd('install:redcatphp');
		$this->runCmd('install:end');
		$this->setDbConfig();
		$this->runGitConfig();
		touch($f);
	}
	protected function runGitConfig(){
		if(!is_dir($this->cwd.'.git')) return;
		$ini = parse_ini_file($this->cwd.'.git/config',true);
		$defaultEmail = $this->gitEmailDefault;
		$defaultName = $this->gitNameDefault;
		if(strtoupper(substr(PHP_OS, 0, 3))!='WIN'){
			$defaultUser = exec('grep 1000 /etc/passwd | cut -f1 -d:');
			$iniGlobalFile = '/home/'.$defaultUser.'/.gitconfig';
			if(is_file($iniGlobalFile)){
				$iniGlobal = parse_ini_file($iniGlobalFile,true);
				if(isset($iniGlobal['user'])){
					if(isset($iniGlobal['user']['email']))
						$defaultEmail = $iniGlobal['user']['email'];
					if(isset($iniGlobal['user']['name']))
						$defaultName = $iniGlobal['user']['name'];
				}
			}
		}
		$email = $this->askQuestion("Email for git commit ($defaultEmail): ",$defaultEmail);
		$name = $this->askQuestion("Name for git commit ($defaultName): ",$defaultName);
		passthru("git config user.email $email");
		passthru("git config user.name $name");
	}
	protected function setDbConfig(){
		$modified = false;
		$path = $this->cwd.'.config.env.php';
		$config = new TokenTree($path);
		$configDb = &$config['$']['db'];
		$configDb['host'] = '"'.$this->askQuestion("Main database host (localhost): ","localhost").'"';
		$configDb['name'] = '"'.$this->askQuestion("Main database name ({$this->mainDbnameDefault}): ",$this->mainDbnameDefault).'"';
		$configDb['user'] = '"'.$this->askQuestion("Main database user (root): ","root").'"';
		$configDb['password'] = '"'.$this->askQuestion("Main database password (root): ","root").'"';
		file_put_contents($path,(string)$config);
	}
}