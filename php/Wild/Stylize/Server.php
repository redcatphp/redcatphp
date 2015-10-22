<?php
namespace Wild\Stylize;
/*
 * Css Preprocessor using Scss syntax ( SASS 3.2 ) ported to PHP with added supports: php imbrication, php mixin, mixin autoload, extend autoload, font autoload - derived from leafo-scssphp
 *
 * @package Stylize
 * @version 1.2
 * @link http://github.com/surikat/Stylize/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://wildsurikat.com
 */
class Server{
	protected $cacheDir = '.tmp/stylize/';
	protected $enableCache;
	protected $compiler;
	protected $extraImports = [];
	function __construct($from=null,$cache=true){
		$this->setCache($cache);
		$this->compiler = new Compiler();
		if(isset($from))
			$this->setImportPaths($from);
	}
	function setCache($enable){
		$this->enableCache = (bool)$enable;
	}
	function serveFrom($file,$from=null,$extraImports=null,$salt = ''){
		if(isset($from))
			$this->setImportPaths($from);
		if($extraImports)
			$this->addExtraImports($extraImports);
		$this->serve($file,$salt);
	}
	function addExtraImports($extraImports){
		foreach((array)$extraImports as $ei){
			if(!in_array($ei,$this->extraImports))
				$this->extraImports[] = $ei;
		}
	}
	function serve($in,$salt = '') {
		if(strpos($in, '..')!==false)
			return;
		set_time_limit(0);
		foreach($this->compiler->getImportPaths() as $dir){
			$input = self::joinPath($dir, $in);
			if(is_file($input)&&is_readable($input)){
				$output = $this->cacheName($salt . $input);
				header('Content-Type:text/css; charset=utf-8');
				if (!$this->enableCache||$this->needsCompile($input, $output)) {
					try {
						if($this->enableCache)
							$this->cachingHeader($output);
						echo $this->compile($input, $output, $in);
					}
					catch (\Exception $e) {
						header('HTTP/1.1 500 Internal Server Error');
						echo 'Parse error: ' . $e->getMessage() . "\n";
						if($e=error_get_last())
							printf("%s in eval php: %s in %s:%s",self::errorType($e['type']),$e['message'],$e['file'],$e['line']);
					}
				} else {
					header('X-SCSS-Cache: true');
					$this->cachingHeader($output);
					echo file_get_contents($output);
				}
				return;
			}
		}
		header('HTTP/1.0 404 Not Found');
		echo "/* CSS NOT FOUND */\n";
	}
	function cacheName($fname){
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
		if($input){
			$compile = '';
			foreach($this->extraImports as $ei){
				$compile .= '@import "'.$ei.'";';
			}
			$compile .= '@import "'.$input.'";';
			$css = $this->compiler->compile($compile);
		}
		else{
			$css = $this->compiler->compile(file_get_contents($in), $in);
		}
		$elapsed = round((microtime(true) - $start), 4);
		$v = Compiler::Scss_VERSION;
		$v2 = Compiler::Stylize_VERSION;
		$t = @date('r');
		$css = "/* compiled by Stylize $v2 ( based on Leafo/ScssPhp $v - Sass 3.2 implementation in PHP ) on $t (${elapsed}s) */\n\n" . $css;
		if(!is_dir($this->cacheDir))
			@mkdir($this->cacheDir,0777,true);
		file_put_contents($out, $css, LOCK_EX);
		file_put_contents($this->importsCacheName($out),
			serialize($this->compiler->getParsedFiles()),LOCK_EX);
		return $css;
	}
	function cachingHeader($output){
		if(!is_file($output))
			return;
		$this->fileCache($output);
	}
	function fileCache($output){
		$mtime = filemtime($output);
		$etag = $this->fileEtag($output);
		header('Last-Modified: '.gmdate('D, d M Y H:i:s',$mtime).' GMT');
		header('Etag: '.$etag);
		if(!$this->isModified($mtime,$etag)){
			header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified');
			exit;
		}
	}
	function fileEtag($file){
		$s = stat($file);
		return sprintf('%x-%s', $s['size'], base_convert(str_pad($s['mtime'], 16, "0"),10,16));
	}
	function isModified($mtime,$etag){
		return !((isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])&&@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])>=$mtime)
			||(isset($_SERVER['HTTP_IF_NONE_MATCH'])&&$_SERVER['HTTP_IF_NONE_MATCH'] == $etag));
	}
	function addImportPath($dir){
		$this->compiler->addImportPath($dir);
	}
	function setImportPaths($dir){
		$this->compiler->setImportPaths($dir);
	}
	function setCacheDir($dir){
		$this->cacheDir = $dir;
	}
	static function joinPath($left, $right) {
		return rtrim($left, '/\\') . DIRECTORY_SEPARATOR . ltrim($right, '/\\');
	}
	static function errorType($code){
		static $errorType = [
			E_ERROR           => 'error',
			E_WARNING         => 'warning',
			E_PARSE           => 'parsing error',
			E_NOTICE          => 'notice',
			E_CORE_ERROR      => 'core error',
			E_CORE_WARNING    => 'core warning',
			E_COMPILE_ERROR   => 'compile error',
			E_COMPILE_WARNING => 'compile warning',
			E_USER_ERROR      => 'user error',
			E_USER_WARNING    => 'user warning',
			E_USER_NOTICE     => 'user notice',
			E_STRICT          => 'strict standard error',
			E_RECOVERABLE_ERROR => 'recoverable error',
			E_DEPRECATED      => 'deprecated error',
			E_USER_DEPRECATED => 'user deprecated error',
		];
		return isset($errorType[$code])?$errorType[$code]:null;
	}
}