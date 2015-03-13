<?php namespace Surikat\SyntaxedCSS;
use Surikat\FileSystem\FS;
use Surikat\DependencyInjection\MutatorMagic;
use Exception;
class SCSSCServer{
	use MutatorMagic;
	static function joinPath($left, $right) {
		return rtrim($left, '/\\') . DIRECTORY_SEPARATOR . ltrim($right, '/\\');
	}
	function cacheName($fname) {
		return self::joinPath($this->cacheDir, md5($fname) . '.css');
	}
	function importsCacheName($out) {
		return $out . '.imports';
	}
	function needsCompile($in, $out) {
		if (!is_file($out)) return true;
		$mtime = filemtime($out);
		if (filemtime($in) > $mtime) return true;
		$icache = $this->importsCacheName($out);
		if (is_readable($icache)) {
			$imports = unserialize(file_get_contents($icache));
			foreach ($imports as $import)
				if (($mt=@filemtime($import)) > $mtime||!$mt) return true;
		}
		return false;
	}
	function compile($in, $out,$input=null) {
		$start = microtime(true);
		if($input)
			$css = $this->scss->compile('@import "globals";@import "'.$input.'";'); //surikat addon
		else
			$css = $this->scss->compile(file_get_contents($in), $in);
		$elapsed = round((microtime(true) - $start), 4);
		$v = $this->scss->VERSION;
		$t = @date('r');
		$css = "/* compiled by scssphp $v on $t (${elapsed}s) */\n\n" . $css;
		file_put_contents($out, $css, LOCK_EX);
		file_put_contents($this->importsCacheName($out),
			serialize($this->scss->getParsedFiles()),LOCK_EX);
		return $css;
	}
	function serve($in,$salt = '') {
		if(strpos($in, '..')!==false)
			return;
		$input = self::joinPath($this->dir, $in);
		if (is_file($input)&&is_readable($input)){
			$output = $this->cacheName($salt . $input);
			header('Content-Type:text/css; charset=utf-8');
			if ($this->needsCompile($input, $output)) {
				try {
					$this->cachingHeader($output);
					echo $this->compile($input, $output, $in);
				}
				catch (Exception $e) {
					header('HTTP/1.1 500 Internal Server Error');
					echo 'Parse error: ' . $e->getMessage() . "\n";
					if($e=error_get_last())
						printf("%s in eval php: %s in %s:%s",$this->Dev_Debug->errorType($e['type']),$e['message'],$e['file'],$e['line']);
				}
			} else {
				header('X-SCSS-Cache: true');
				$this->cachingHeader($output);
				echo file_get_contents($output);
			}
			return;
		}
		header('HTTP/1.0 404 Not Found');
		echo "/* CSS NOT FOUND */\n";
	}
	function cachingHeader($output){
		if(!is_file($output))
			return;
		$this->HTTP_Request->fileCache($output);
	}
	function __construct(){
		$this->scss = $this->SyntaxedCSS_SCSSC;
	}
	function setPath($dir, $cacheDir=null){
		$this->dir = $dir;
		$this->cacheDir = $cacheDir?$cacheDir:SURIKAT_TMP.'scss/';
		FS::mkdir($this->cacheDir);
		$this->scss->setImportPaths($this->dir);
		if(is_dir('css'))
			$this->scss->addImportPath('css');
		if(is_dir(basename(SURIKAT_SPATH).'/css'))
			$this->scss->addImportPath(basename(SURIKAT_SPATH).'/css');
	}
}
