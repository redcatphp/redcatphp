<?php namespace Surikat\Control;
use Surikat\Control;
use Exception;
class scssc_server{
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
		$v = scssc::$VERSION;
		$t = @date('r');
		$css = "/* compiled by scssphp $v on $t (${elapsed}s) */\n\n" . $css;
		file_put_contents($out, $css);
		file_put_contents($this->importsCacheName($out),
			serialize($this->scss->getParsedFiles()));
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
		if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($fn))) {
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($output)).' GMT', true, 304);
			exit;
		}
		header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($output)).' GMT', true, 200);
		header("Etag: " . HTTP::FileEtag($output));
	}
	function __construct($dir, $cacheDir=null){
		$this->dir = $dir;
		$this->cacheDir = $cacheDir?$cacheDir:Control::$TMP.'scss/';
		FS::mkdir($this->cacheDir);
		$this->scss = new scssc();
		$this->scss->setImportPaths($this->dir);
		if(is_dir('css'))
			$this->scss->addImportPath('css');
		if(is_dir(basename(Control::$SURIKAT).'/css'))
			$this->scss->addImportPath(basename(Control::$SURIKAT).'/css');
	}
}
