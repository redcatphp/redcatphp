<?php namespace Surikat\Git\GitDeploy;
use Surikat\FileSystem\FS;
abstract class Server {
    var $connection;
    var $current_commit;
    var $host;
    var $existing_paths_cache;
    var $clean_directories = ['.tmp'];
    var $ignore_files = ['.gitignore'];
    var $upload_untracked;
    var $server;
    var $htaccess;
    var $maintenance;
    function __construct($server){
        $this->server = $server;
        $this->clean_directories = array_merge($this->clean_directories,$server['clean_directories']);
        $this->ignore_files = array_merge($this->ignore_files, $server['ignore_files']);
        $this->upload_untracked = $server['upload_untracked'];
        $this->setPath($server['path'],false);
    }
    function connection(){
		if(!$this->connection)
			$this->connect($this->server);
	}
    function setPath($path,$chdir=true){
		$this->server['path'] = $path;
        $this->host = "{$this->server['scheme']}://{$this->server['user']}@{$this->server['host']}:{$this->server['port']}{$this->server['path']}";
		if($chdir){
			$this->connection();
			$this->chdir($path);
		}
	}
    function deploy(Git $git, $target_commit, $is_revert = false, $list_only = false, $maintenance=false) {
		$this->connection();
		$this->current_commit = $this->get_file('REVISION', true);
        if ($target_commit == $this->current_commit) {
            GitDeploy::logmessage("Nothing to update on: $this->host");
            return;
        }
		if($maintenance)
			$this->maintenanceOn();
        if($list_only)
            GitDeploy::logmessage("DETECTED '-l'. NO FILES ARE BEING UPLOADED / DELETED, THEY ARE ONLY BEING LISTED.");
        GitDeploy::logmessage("Started working on: {$this->host}");
        if ($is_revert)
            GitDeploy::logmessage("Reverting server from {$this->current_commit} to $target_commit...");
        elseif(empty($this->current_commit))
            GitDeploy::logmessage("Deploying to server for the first time...");
        else
            GitDeploy::logmessage("Updating server from {$this->current_commit} to $target_commit...");
        $changes = $git->get_changes($target_commit, $this->current_commit); # Get files between $commit and REVISION
        foreach($changes['upload'] as $file => $contents)
            if(in_array($file, $this->ignore_files))
                unset($changes['upload'][$file]);
        foreach ($this->upload_untracked as $file) {
            if (file_exists($git->repo_path . $file)) {
            	if (is_dir($git->repo_path . $file)) {
            		foreach (GitDeploy::get_recursive_file_list($git->repo_path . $file, $file."/") as $buffer) {
            			$changes['upload'][$buffer] = file_get_contents($git->repo_path . $buffer);
            		}
            	}
            	else{
            		$changes['upload'][$file] = file_get_contents($git->repo_path . $file);
            	}
            }
        }
        $submodule_meta = [];
        foreach ($changes['submodules'] as $submodule) {
            GitDeploy::logmessage($submodule);
            $current_subcommit = $this->get_file($submodule . '/REVISION', true);
            $subgit = new Git($git->repo_path . $submodule . "/");
            $target_subcommit = $subgit->interpret_target_commit("HEAD");
            $subchanges = $subgit->get_changes($target_subcommit, $current_subcommit);
            $submodule_meta[$submodule] = [
                'target_subcommit' => $target_subcommit,
                'current_subcommit' => $current_subcommit
            ];
            foreach ($subchanges['upload'] as $file => $contents)
                $changes['upload'][$submodule . "/" . $file] = $contents;
            foreach ($subchanges['delete'] as $file => $contents)
                $changes['delete'][$submodule . "/" . $file] = $contents;
        }
        $count_upload = count($changes['upload']);
        $count_delete = count($changes['delete']);
        if ($count_upload == 0 and $count_delete == 0) {
            GitDeploy::logmessage("Nothing to update on: $this->host");
            return;
        }
        if ($count_upload > 0)
            $count_upload = $count_upload + 2;
        GitDeploy::logmessage("Will upload $count_upload file" . ($count_upload == 1 ? '' : 's') . ".");
        GitDeploy::logmessage("Will delete $count_delete file" . ($count_delete == 1 ? '' : 's') . ".");
		$totalSize = 0;
        foreach ($changes['upload'] as $file => $contents) {
			$totalSize += strlen($contents);
		}
		$humanTotalSize = FS::humanSize($totalSize);
		$uploadedSize = 0;
        foreach ($changes['upload'] as $file => $contents) {
			if($this->maintenance&&$file=='.htaccess'){
				$this->htaccess = $contents;
				continue;
			}
			$uploadedSize += strlen($contents);
            if($this->set_file($file, $contents))
				GitDeploy::logmessage("Uploaded: $file ".FS::humanSize(strlen($contents)).'  ('.round(($uploadedSize/$totalSize)*100).'% '.FS::humanSize($uploadedSize).'/'.$humanTotalSize.')');
        }
        foreach ($changes['delete'] as $file) {
            if ($list_only)
                GitDeploy::logmessage("Deleted: $file");
            else
                $this->unset_file($file);
        }
        foreach ($this->clean_directories as $directory) {
            $this->unset_file($directory);
            $this->mkdir($directory);
        }
        foreach ($changes['submodules'] as $submodule) {
            $this->set_file('REVISION', $submodule_meta[$submodule]['target_subcommit']);
            GitDeploy::logmessage("Uploaded: REVISION");
            $this->set_file('PREVIOUS_REVISION', (empty($submodule_meta[$submodule]['current_subcommit']) ? $submodule_meta[$submodule]['target_subcommit'] : $submodule_meta[$submodule]['current_subcommit']));
            GitDeploy::logmessage("Uploaded: PREVIOUS_REVISION");
        }
        $this->set_current_commit($target_commit, $list_only);
        if($maintenance)
			$this->maintenanceOff($git);
    }
    function revert($git, $list_only = false, $maintenance=false) {
		$this->connection();
        $target_commit = $this->get_file('PREVIOUS_REVISION', true);
        if (empty($target_commit))
            GitDeploy::error("Cannot revert: {$this->host} server has no PREVIOUS_REVISION file.");
        else
            $this->deploy($git, $target_commit, true, $list_only, $maintenance);
    }
    protected function set_current_commit($target_commit, $list_only = false) {
        if (!$list_only) {
            $this->set_file('REVISION', $target_commit);
            GitDeploy::logmessage("Uploaded: REVISION");
            $this->set_file('PREVIOUS_REVISION', (empty($this->current_commit) ? $target_commit : $this->current_commit));
            GitDeploy::logmessage("Uploaded: PREVIOUS_REVISION");
        }
        GitDeploy::logmessage("Finished working on: {$this->host}");
    }
    function maintenanceOn(){
		if(!$this->maintenance){
			$this->connection();
			$this->maintenance = true;
			$this->set_file('.htaccess', file_get_contents(SURIKAT_SPATH.'htaccess_307'));
			GitDeploy::logmessage('Turned maintenance mode on.');
		}
	}
    function maintenanceOff($git=null){
        if($this->maintenance){
			$this->connection();
			$this->maintenance = false;
			if(isset($this->htaccess))
				$htaccess = $this->htaccess;
			elseif($git)
				$htaccess = $git->get_file_contents('.htaccess');
			else
				$htaccess = file_get_contents(SURIKAT_PATH.'.htaccess');
			$this->set_file('.htaccess', $htaccess);
			GitDeploy::logmessage('Turned maintenance mode off');
		}
	}
}