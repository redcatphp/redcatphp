<?php namespace Surikat\Git\GitDeploy;
class FTP extends Server{
    function getConnection($server){
		return @ftp_connect($server['host'], $server['port'], 30);
	}
    function connect($server){
        if (!extension_loaded('ftp'))
            GitDeploy::error("You need the FTP extension to be enabled if you want to deploy via FTP.");
		$this->connection = $this->getConnection($server);
        if (!$this->connection) {
            GitDeploy::error("Could not connect to {$this->host}");
        }
        else{
            if (!ftp_login($this->connection, $server['user'], $server['pass']))
                GitDeploy::error("Could not login to {$this->host}");
            ftp_pasv($this->connection, $server['passive']);
            $this->chdir($server['path']);
        }
        GitDeploy::logmessage("Connected to: {$this->host}");
    }
    function chdir($path, $ignore_if_error = false) {
        if(!@ftp_chdir($this->connection, $path)&&!@ftp_mkdir($this->connection, $path)&&!$ignore_if_error) {
            GitDeploy::error("Could not change the directory to {$path} on {$this->host}");
        }
	}
    function get_file($file, $ignore_if_error = false){
        $tmpFile = tempnam(sys_get_temp_dir(), 'GITDEPLOYPHP');
        if($ignore_if_error)
            $result = @ftp_get($this->connection, $tmpFile, $file, FTP_BINARY);
        else
            $result = ftp_get($this->connection, $tmpFile, $file, FTP_BINARY); # Display whatever error PHP throws.
        if($result)
            return file_get_contents($tmpFile);
		# Couldn't get the file. I assume it's because the file didn't exist.
		if ($ignore_if_error)
			return false;
		else
			GitDeploy::error("Failed to retrieve '$file'.");
    }
    function set_file($file, $contents, $die_if_fail = false) {        
        # Make sure the folder exists in the FTP server.
        $dir = explode("/", dirname($file));
        $path = "";
        for ($i = 0; $i < count($dir); $i++) {
            $path.= $dir[$i] . '/';
            if (!isset($this->existing_paths_cache[$path])) {
                $origin = ftp_pwd($this->connection);
                if (!@ftp_chdir($this->connection, $path)) {
                    if (!@ftp_mkdir($this->connection, $path)) {
                        GitDeploy::error("Failed to create the directory '$path'. Upload to this server cannot continue.");
                    }
                    else{
                        GitDeploy::logmessage("Created directory: $path");
                        $this->existing_paths_cache[$path] = true;
                    }
                }
                else {
                    $this->existing_paths_cache[$path] = true;
                }
                ftp_chdir($this->connection, $origin);
            }
        }
        $tmpFile = tempnam(sys_get_temp_dir(), 'GITDEPLOYPHP');
        file_put_contents($tmpFile, $contents);
        $uploaded = ftp_put($this->connection, $file, $tmpFile, FTP_BINARY);
        if ($uploaded)
            return true;
		if ($die_if_fail) {
			GitDeploy::error("Failed to upload {$file}. Deployment will stop to allow you to check what went wrong.");
		}
		else{
			# Try deleting the file and reuploading. - This resolves a CHMOD issue with some FTP servers.
			$this->unset_file($file);
			return $this->set_file($file, $contents, true);
		}
    }
    protected function recursive_remove($file_or_directory, $die_if_fail = false) {
        if (!(@ftp_rmdir($this->connection, $file_or_directory) || @ftp_delete($this->connection, $file_or_directory))) {
            if ($die_if_fail)
                return false;
            $filelist = ftp_nlist($this->connection, $file_or_directory);
            foreach ($filelist as $file) 
                if ($file != '.' and $file != '..')
                    $this->recursive_remove($file_or_directory.'/'.$file);
            $this->recursive_remove($file_or_directory, true);
        }
    }
    function mkdir($file) {
        if(@ftp_mkdir($this->connection, $file))
			GitDeploy::logmessage("Created directory: $file");
    }
    function unset_file($file) {
        $this->recursive_remove($this->server['path'].'/'.$file);
        GitDeploy::logmessage("Deleted: $file");
    }
}