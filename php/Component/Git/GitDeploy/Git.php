<?php namespace Surikat\Git\GitDeploy;
class Git {
    static $git_executable_path = "git";
    var $repo_path;
    function __construct($repo_path) {
        $this->repo_path = rtrim($repo_path, '/').'/';
        if (stristr($this->exec("--version"), "git version") === false) # Test if has Git in cmd.
            GitDeploy::error("The command '" . self::$git_executable_path . "' was not found.");
    }
    function interpret_target_commit($target_commit, $branch = null) {
        if ($branch !== null) {
            if ($target_commit == "HEAD") {
                # Get the HEAD commit of the branch specified in the deploy.ini
                $target_commit = $branch;
            }
        }
        return $this->exec("rev-parse $target_commit");
    }
    function get_changes($target_commit, $current_commit) {
        if (file_exists(".gitmodules"))
            $submodules = parse_ini_file(".gitmodules", true);
        else
            $submodules = [];
        $submodule_paths = [];
        foreach ($submodules as $submodule)
            $submodule_paths[] = $submodule['path'];
        if (!empty($current_commit))
            $command = "diff --name-status {$current_commit} {$target_commit}";
        else
            $command = "ls-files";
        $return = [
            'upload' => [],
            'delete' => [],
            'submodules' => $submodule_paths
        ];
        $command = str_replace(["\n","\r\n"], '', $command);
        $result = $this->exec($command);
        if (empty($result))
            return $return; # Nothing has changed.
        $result = explode("\n", $result);
        if (!empty($current_commit)) {
            foreach ($result as $line) {
                if ($line[0] == 'A' or $line[0] == 'C' or $line[0] == 'M') {
                    $path = trim(substr($line, 1, strlen($line)));
					$path = $this->fixPath($path);
                    $return['upload'][$path] = $this->get_file_contents("$target_commit:\"$path\"");
                }
                elseif ($line[0] == 'D') {
                    $return['delete'][] = trim(substr($line, 1, strlen($line)));
                }
                else {
                    GitDeploy::error("Unknown git-diff status: {$line}");
                }
            }
        }
        else{
            foreach ($result as $file) {
				$file = $this->fixPath($file);
                if (!in_array($file, $submodule_paths))
                    $return['upload'][$file] = $this->get_file_contents("$target_commit:$file");
            }
        }
        return $return;
    }
    protected function fixPath($file) {
		if(substr($file,0,1)=='"'&&substr($file,-1)=='"')
			$file = preg_replace_callback('/\\\\[0-7]{3}/', function($v){
				return chr(octdec($v[0]));
			},substr($file,1,-1));
		return $file;
	}
    function get_file_contents($path) {
    	$temp = tempnam(sys_get_temp_dir(), "git-deploy-");
    	$this->exec("show $path", "> \"$temp\"");
    	return file_get_contents($temp);
    }
    protected function exec($command, $suffix = "") {
        if(chdir($this->repo_path))
            return trim(shell_exec(self::$git_executable_path . " " . $command . " 2>&1 " . $suffix));
        else
            GitDeploy::error("Unable to access the git repository's folder.");
    }
	protected function _exec($cmd){
		echo $cmd."\r\n";
		echo shell_exec($cmd.' 2>&1');
	}
    function autocommit(){
		$ini = parse_ini_file($this->repo_path.'.git/config',true);
		$email = $ini['user']['email'];
		$name = $ini['user']['name'];
		$this->_exec('cd '.$this->repo_path);
		$this->_exec('git config --local user.email "'.$email.'"');
		$this->_exec('git config --local user.name "'.$name.'"');
		$this->_exec('git add --all .');
		$this->_exec("git commit -m \"auto commit by service deploy - ".@strftime('%A %e %B %G - %k:%M:%S',time()).'"');
		shell_exec('chmod -R 777 '.$this->repo_path.'.git 2>&1');
	}
}