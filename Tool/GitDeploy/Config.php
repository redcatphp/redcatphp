<?php namespace Surikat\Tool\GitDeploy;
use Surikat\Tool;
class Config {

    public static function getArgs() {
        $argv = (array)@$_SERVER['argv'];

        $deploy = SURIKAT_PATH.'deploy.ini';
        $commands = ['-l', '-r', '-c', '-d', '--revert', '--log', '--repo'];

        $deploy_file = isset($argv[1]) ? end($argv) : "deploy.ini";

        if (!in_array($deploy_file, $commands)) {
            $deploy = $deploy_file . (substr($deploy_file, -4) === '.ini' ? '' : '.ini');
        }

        $opts = getopt("lr:d:c:", ["revert", "log::", "repo:"]);

        if (isset($opts['log'])) {
            define('WRITE_TO_LOG', $opts['revert'] ? $opts['revert'] : 'git_deploy_php_log.txt');
        }

        if (isset($opts['d'])) {
            $deploy = $opts['d'];
        }

        if (isset($opts['c'])) {
            $opts['r'] = $opts['c'];
        }

        if (isset($opts['repo'])) {
            $repo_path = $opts['repo'];
        } else {
            $repo_path = SURIKAT_PATH;
        }

        return [
            'config_file' => $deploy,
            'target_commit' => isset($opts['r']) ? $opts['r'] : 'HEAD',
            'list_only' => isset($opts['l']),
            'revert' => isset($opts['revert']),
            'repo_path' => $repo_path
        ];
    }

    public static function getServers($config_file,$parent=null,$child=null) {
        $servers = @parse_ini_file($config_file, true);
        $return = [];

        if (!$servers) {
            GitDeploy::error("File '$config_file' is not a valid .ini file.");
        } else {
            foreach ($servers as $uri => $options) {
				if(!is_array($options))
					continue;
                if (stristr($uri, "://") !== false) {
                    $options = array_merge($options, parse_url($uri));
                }

                # Throw in some default values, in case they're not set.
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
                if ($options['skip']) {
                    continue;
                } else {
                    unset($options['skip']);
                    $type = __NAMESPACE__.'\\'.strtoupper($options['scheme']);
                    $return[$uri] = new $type($options, $config_file);
                }
            }
        }
        return $return;
    }

}