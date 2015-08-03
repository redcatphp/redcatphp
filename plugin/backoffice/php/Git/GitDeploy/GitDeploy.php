<?php namespace Git\GitDeploy;
class GitDeploy{
	protected $origin;
	protected $repoPath;
	protected $config = [
		'target_commit' => 'HEAD',
		'list_only' => false,
	];
	protected $iniServers;
	protected $maintenance;
	protected $servers = [];
	protected $options = [
		'skip' => false,
		'scheme' => 'ftp',
		'host' => '',
		'user' => '',
		'branch' => null,
		'pass' => '',
		'port' => 21,
		'path' => '/',
		'passive' => true,
		'clean_directories' => [],
		'ignore_files' => [],
		'upload_untracked' => []
	];
	function __construct($repoPath=null,$config=null){
		ini_set('memory_limit', '256M');
        $this->repoPath = $repoPath;
		$this->iniServers = $config;
		
		if(!isset($config)){
			$config = [];
			foreach(['surikat/',''] as $d){
				if(is_file($f=$d.'config/deploy.ini')){
					$config = self::array_merge_recursive($config,parse_ini_file($f,true));
				}
			}
		}
		if(empty($config))
			throw new \Exception('Missing deploy config');

        if(!$this->iniServers)
			$this->iniServers = $config;
        if(!$this->iniServers)
            return self::error("Invalid deploy configuration");
		foreach($this->iniServers as $uri=>$options){
			if(!is_array($options))
				continue;
			if(strpos($uri, ":/")!==false)
				$options = parse_url($uri);
			$options = array_merge($this->options, $options);
			if(!$options['skip']){
				unset($options['skip']);
				$type = __NAMESPACE__.'\\'.strtoupper($options['scheme']);
				$this->servers[$uri] = new $type($options);
			}
		}
	}
	function maintenanceOn(){
		$this->maintenance = true;
		return $this;
	}
	function maintenanceOff(){
		$this->maintenance = false;
		return $this;
	}
	function __clone(){
		foreach(array_keys($this->servers) as $k)
			$this->servers[$k] = clone $this->servers[$k];
	}
	function getOrigin(){
		return $this->origin?$this->origin:$this;
	}
	function getParent($path=null){
		$c = clone $this;
		$c->origin = $this;
		if(!isset($path))
			$path = $c->repoPath;
		else
			$c->repoPath = $path;
		$path = pathinfo($path,PATHINFO_FILENAME);
		foreach($c->servers as $server){
			$server->setPath(dirname(rtrim($server->server['path'],'/')).'/'.$path);
			$server->clean_directories = [];
		}
		return $c;
	}
	function getChild($path=null){
		$c = clone $this;
		$c->origin = $this;
		if(!isset($path))
			$path = $c->repoPath;
		else
			$c->repoPath = $path;
		$path = pathinfo($path,PATHINFO_FILENAME);
		foreach($c->servers as $server){
			$server->setPath(rtrim($server->server['path'],'/').'/'.$path);
			$server->clean_directories = [];
		}
		return $c;
	}
	function revert($path=null){
		if(!isset($path))
			$path = $this->repoPath;
		$git = new Git($path);
		foreach($this->servers as $server)
			$server->revert($git, $this->config['list_only'],$this->maintenance);
		return $this;
	}
	function deploy($path=null){
		if(!isset($path))
			$path = $this->repoPath;
		$git = new Git($path);
		foreach($this->servers as $server)
			$server->deploy($git, $git->interpret_target_commit($this->config['target_commit'], $server->server['branch']), false, $this->config['list_only'], $this->maintenance);
		return $this;
	}
	function autocommit($path=null){ //need the .git have recursively full permission (www-data have to be able to write)
		if(!isset($path))
			$path = $this->repoPath;
		$git = new Git($path);
		$git->autocommit();
		return $this;
	}
	
	static function logmessage($message) {
		echo '['.@date("Y-m-d H:i:s O")."] $message\n";
	}
	static function error($message){
		self::logmessage("ERROR: $message");
		exit;
	}
	static function get_recursive_file_list($folder, $prefix = '') {
		$folder = (substr($folder, strlen($folder) - 1, 1) == '/') ? $folder : $folder . '/';
		$return = [];
		foreach (clean_scandir($folder) as $file)
			if (is_dir($folder . $file))
				$return = array_merge($return, self::get_recursive_file_list($folder . $file, $prefix . $file . '/'));
			else
				$return[] = $prefix . $file;
		return $return;
	}
	static function clean_scandir($folder, $ignore = []) {
		$ignore[] = '.';
		$ignore[] = '..';
		$ignore[] = '.DS_Store';
		$return = [];
		foreach (scandir($folder) as $file)
			if (!in_array($file, $ignore))
				$return[] = $file;
		return $return;
	}
	static function array_merge_recursive(){
		$args = func_get_args();
		$merged = array_shift($args);
		foreach($args as $array2){
			if(!is_array($array2)){
				continue;
			}
			foreach($array2 as $key => &$value){
				if(is_array($value)&&isset($merged [$key])&&is_array($merged[$key])){
					$merged[$key] = self::array_merge_recursive($merged[$key],$value);
				}
				else{
					$merged[$key] = $value;
				}
			}
		}
		return $merged;
	}
}