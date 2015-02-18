<?php namespace Surikat\Git\GitDeploy;
use Surikat\Crypto\PhpSecLib\Net_SFTP;
use Surikat\Crypto\PhpSecLib\Crypt_RSA;
class SFTP extends Server {
    function connect($server) {
        $this->connection = new Net_SFTP($server['host'], $server['port'], 10);
        $logged_in = false;
        if(isset($server['sftp_key'])){
            $key = new Crypt_RSA();
            $key->loadKey(file_get_contents($server['sftp_key']));
            $logged_in = $this->connection->login($server['user'], $key);
        }
        else{
            $logged_in = $this->connection->login($server['user'], $server['pass']);
        }
        if(!$logged_in){
            GitDeploy::error("Could not login to {$this->host}");
        }
        $this->chdir($server['path']);
        GitDeploy::logmessage("Connected to: {$this->host}");
    }
    function chdir($path, $ignore_if_error = false) {
        if(!$this->connection->chdir($path)&&!$this->connection->mkdir($path)&&!$ignore_if_error) {
            GitDeploy::error("Could not change the directory to {$path} on {$this->host}");
        }
	}
    function get_file($file, $ignore_if_error = false) {
        $contents = $this->connection->get($file);
        if($contents){
            return $contents;
        }
        else{
            # Couldn't get the file. I assume it's because the file didn't exist.
            if($ignore_if_error){
                return false;
            }
            else{
                GitDeploy::error("Failed to retrieve '$file'.");
            }
        }
    }
    function set_file($file, $contents){
        $file = $file;
        $dir = explode("/", dirname($file));
        $path = "";
        for ($i = 0; $i < count($dir); $i++) {
            $path.= $dir[$i] . '/';
            if (!isset($this->existing_paths_cache[$path])) {
                $origin = $this->connection->pwd();
                if (!$this->connection->chdir($path)) {
                    if (!$this->connection->mkdir($path)) {
                        GitDeploy::error("Failed to create the directory '$path'. Upload to this server cannot continue.");
                    }
                    else{
                        GitDeploy::logmessage("Created directory: $path");
                        $this->existing_paths_cache[$path] = true;
                    }
                }
                else{
                    $this->existing_paths_cache[$path] = true;
                }
                $this->connection->chdir($origin);
            }
        }
        if($this->connection->put($file, $contents)){
            return true;
        }
        else{
            GitDeploy::error("Failed to upload {$file}. Deployment will stop to allow you to check what went wrong.");
        }
    }
    protected function recursive_remove($file_or_directory, $if_dir = false) {
        $parent = dirname($file_or_directory);
        if($this->connection->delete($file_or_directory, $if_dir)){
            $filelist = $this->connection->nlist($parent);
            foreach($filelist as $file){
                if($file != '.' and $file != '..'){
                    return false;
                }
            }
            $this->recursive_remove($parent, true);
        }
    }
    function mkdir($file) {
        $this->connection->mkdir($file);
        GitDeploy::logmessage("Created directory: $file");
    }
    function unset_file($file) {
        $this->recursive_remove($file, false);
        GitDeploy::logmessage("Deleted: $file");
    }
}