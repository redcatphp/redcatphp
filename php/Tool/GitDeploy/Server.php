<?php namespace Surikat\Tool\GitDeploy;
use Surikat\Core\FS;
abstract class Server {
    public $connection;
    public $current_commit;
    public $host;
    public $existing_paths_cache;
    public $clean_directories;
    public $ignore_files = ['.gitignore', 'config/deploy.ini'];
    public $upload_untracked;
    public $server;

    public function __construct($server) {
        $this->server = $server;
        $this->clean_directories = $server['clean_directories'];
        $this->ignore_files = array_merge($this->ignore_files, $server['ignore_files']);
        $this->upload_untracked = $server['upload_untracked'];
        $this->host = "{$server['scheme']}://{$server['user']}@{$server['host']}:{$server['port']}{$server['path']}";
        $this->connect($server);
        $this->current_commit = $this->get_file('REVISION', true);
    }

    public function deploy(Git $git, $target_commit, $is_revert = false, $list_only = false) {

        if ($target_commit == $this->current_commit) {
            GitDeploy::logmessage("Nothing to update on: $this->host");
            return;
        }

        if ($list_only) {
            GitDeploy::logmessage("DETECTED '-l'. NO FILES ARE BEING UPLOADED / DELETED, THEY ARE ONLY BEING LISTED.");
        }

        GitDeploy::logmessage("Started working on: {$this->host}");

        if ($is_revert) {
            GitDeploy::logmessage("Reverting server from " . substr($this->current_commit, 0, 6) . " to " . substr($target_commit, 0, 6) . "...");
        } elseif (empty($this->current_commit)) {
            GitDeploy::logmessage("Deploying to server for the first time...");
        } else {
            GitDeploy::logmessage("Updating server from " . substr($this->current_commit, 0, 6) . " to " . substr($target_commit, 0, 6) . "...");
        }

        # Get files between $commit and REVISION
        $changes = $git->get_changes($target_commit, $this->current_commit);

        foreach ($changes['upload'] as $file => $contents) {
            if (in_array($file, $this->ignore_files)) {
                unset($changes['upload'][$file]);
            }
        }

        foreach ($this->upload_untracked as $file) {
            if (file_exists($git->repo_path . $file)) {
            	if (is_dir($git->repo_path . $file)) {
            		foreach (GitDeploy::get_recursive_file_list($git->repo_path . $file, $file."/") as $buffer) {
            			$changes['upload'][$buffer] = file_get_contents($git->repo_path . $buffer);
            		}
            	} else {
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

            foreach ($subchanges['upload'] as $file => $contents) {
                $changes['upload'][$submodule . "/" . $file] = $contents;
            }

            foreach ($subchanges['delete'] as $file => $contents) {
                $changes['delete'][$submodule . "/" . $file] = $contents;
            }
        }

        $count_upload = count($changes['upload']);
        $count_delete = count($changes['delete']);

        if ($count_upload == 0 and $count_delete == 0) {
            GitDeploy::logmessage("Nothing to update on: $this->host");
            return;
        }

        if ($count_upload > 0) {
            $count_upload = $count_upload + 2;
        }

        GitDeploy::logmessage("Will upload $count_upload file" . ($count_upload == 1 ? '' : 's') . ".");
        GitDeploy::logmessage("Will delete $count_delete file" . ($count_delete == 1 ? '' : 's') . ".");
        
        if (isset($this->server['maintenance_file'])) {
            $this->set_file($this->server['maintenance_file'], $this->server['maintenance_on_value']);
            GitDeploy::logmessage("Turned maintenance mode on.");
        }

		$totalSize = 0;
        foreach ($changes['upload'] as $file => $contents) {
			$totalSize += strlen($contents);
		}
		$humanTotalSize = FS::humanSize($totalSize);
		$uploadedSize = 0;
        foreach ($changes['upload'] as $file => $contents) {
			$uploadedSize += strlen($contents);
            if($this->set_file($file, $contents))
				GitDeploy::logmessage("Uploaded: $file \t\t\t".FS::humanSize(strlen($contents)).'  ('.round(($uploadedSize/$totalSize)*100).'% '.FS::humanSize($uploadedSize).'/'.$humanTotalSize.')');
        }

        foreach ($changes['delete'] as $file) {
            if ($list_only) {
                GitDeploy::logmessage("Deleted: $file");
            } else {
                $this->unset_file($file);
            }
        }

        foreach ($this->clean_directories as $directory) {
            $this->unset_file($directory);
            $this->mkdir($directory);
        }

        foreach ($changes['submodules'] as $submodule) {
            $this->set_file('REVISION', $submodule_meta[$submodule]['target_subcommit']);
            $this->set_file('PREVIOUS_REVISION', (empty($submodule_meta[$submodule]['current_subcommit']) ? $submodule_meta[$submodule]['target_subcommit'] : $submodule_meta[$submodule]['current_subcommit']));
        }

        $this->set_current_commit($target_commit, $list_only);
    }

    public function revert($git, $list_only = false) {
        $target_commit = $this->get_file('PREVIOUS_REVISION', true);
        if (empty($target_commit)) {
            GitDeploy::error("Cannot revert: {$this->host} server has no PREVIOUS_REVISION file.");
        } else {
            $this->deploy($git, $target_commit, true, $list_only);
        }
    }

    protected function set_current_commit($target_commit, $list_only = false) {
        if (!$list_only) {
            $this->set_file('REVISION', $target_commit);
            $this->set_file('PREVIOUS_REVISION', (empty($this->current_commit) ? $target_commit : $this->current_commit));
        }
        
        if (isset($this->server['maintenance_file'])) {
            $this->set_file($this->server['maintenance_file'], $this->server['maintenance_off_value']);
            GitDeploy::logmessage("Turned maintenance mode off.");
        }
        
        GitDeploy::logmessage("Finished working on: {$this->host}");
    }
}