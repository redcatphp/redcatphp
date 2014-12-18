<?php namespace Surikat\Tool\GitDeploy;
use Surikat\Core\ConfigINI;
abstract class GitDeploy{
	static $writeToLog;
	static function main($config=null,$parent=null,$child=null){
		ini_set('memory_limit', '256M');
		$argv = (array)@$_SERVER['argv'];
        $commands = ['-l', '-r', '-c', '-d', '--revert', '--repo'];
        $opts = getopt("lr:d:c:", ["revert", "log::", "repo:"]);
        if (isset($opts['c']))
            $opts['r'] = $opts['c'];
        if (isset($opts['repo']))
            $repo_path = $opts['repo'];
        else
            $repo_path = SURIKAT_PATH;
        $args = [
            'target_commit' => isset($opts['r']) ? $opts['r'] : 'HEAD',
            'list_only' => isset($opts['l']),
            'revert' => isset($opts['revert']),
            'repo_path' => $repo_path
        ];
		if(isset($config))
			$args = array_merge($args,$config);
		if($parent)
			$parent = pathinfo($args['repo_path'],PATHINFO_FILENAME);
		if($child)
			$child = pathinfo($args['repo_path'],PATHINFO_FILENAME);
		$iniServers = ConfigINI::deploy();
        if(!$iniServers)
            return GitDeploy::error("Invalid deploy configuration");
        $servers = [];
		foreach ($iniServers as $uri => $options){
			if(!is_array($options))
				continue;
			if (stristr($uri, "://") !== false)
				$options = array_merge($options, parse_url($uri));
			$options = array_merge([
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
					], $options);
			if($parent){
				$options['path'] = dirname(rtrim($options['path'],'/')).'/'.$parent;
				$options['clean_directories'] = [];
			}
			if($child){
				$options['path'] = rtrim($options['path'],'/').'/'.$child;
				$options['clean_directories'] = [];
			}
			if (!$options['skip']) {
				unset($options['skip']);
				$type = __NAMESPACE__.'\\'.strtoupper($options['scheme']);
				$servers[$uri] = new $type($options);
			}
		}
		
		$git = new Git($args['repo_path']);
		foreach ($servers as $server) {
			if ($args['revert'])
				$server->revert($git, $args['list_only']);
			else
				$server->deploy($git, $git->interpret_target_commit($args['target_commit'], $server->server['branch']), false, $args['list_only']);
		}
	}
	static function logmessage($message) {
		static $log_handle = null;
		$log = "[" . @date("Y-m-d H:i:s O") . "] " . $message . PHP_EOL;
		if (self::$writeToLog){
			if ($log_handle===null) 
				$log_handle = fopen(self::$writeToLog, 'a');
			fwrite($log_handle, $log);
		}
		echo $log;
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
}