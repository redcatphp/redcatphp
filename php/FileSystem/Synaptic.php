<?php namespace FileSystem;
use DependencyInjection\MutatorMagicTrait;
class Synaptic {
	use MutatorMagicTrait;
	protected $expires = 2592000;
	protected $allowedExtensions = ['css','js','jpg','jpeg','png','gif'];
	function load($k){
		$extension = strtolower(pathinfo($k,PATHINFO_EXTENSION));
		if(!in_array($extension,$this->allowedExtensions)){
			$this->Http_Request->code(403);
			exit;
		}
		switch($extension){
			case 'js':
				if(is_file($f=SURIKAT_PATH.$k)||is_file($f=SURIKAT_SPATH.$k)){
					header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->expires).'GMT');
					header('Content-Type: application/javascript; charset:utf-8');
					$this->Http_Request->fileCache($f);
					readfile($f);
				}
				elseif(substr($k,-7,-3)=='.min'){
					$kv = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'?'https':'http').'://'.$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']&&(int)$_SERVER['SERVER_PORT']!=80?':'.$_SERVER['SERVER_PORT']:'').'/'.substr($k,0,-7).'.js';
					$this->minifyJs($kv,$k);
				}
				else{
					$this->Http_Request->code(404);
				}
			break;
			case 'css':
				if(is_file($f=SURIKAT_PATH.$k)||is_file($f=SURIKAT_SPATH.$k)){
					header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->expires).'GMT');
					header('Content-Type: text/css; charset:utf-8');
					$this->Http_Request->fileCache($f);
					readfile($f);
				}
				elseif(substr($k,-8,-4)=='.min')
					$this->minifyCSS(substr($k,0,-8).'.css');
				elseif(
					is_file(dirname($key=$k).'/'.pathinfo($key,PATHINFO_FILENAME).'.scss')
					||(($key=basename(SURIKAT_SPATH).'/'.$key)&&is_file(dirname($key).'/'.pathinfo($key,PATHINFO_FILENAME).'.scss'))
				){
					if($this->scss($key)===false){
						$this->Http_Request->code(404);
					}
				}
				else{
					$this->Http_Request->code(404);
				}
			break;
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'gif':
				header('Content-Type:image/'.$extension.'; charset=utf-8');
				if(is_file($f=SURIKAT_PATH.$k)||is_file($f=SURIKAT_SPATH.$k)){
					$this->Http_Request->fileCache($f);
					readfile($f);
				}
				elseif(is_file($f=SURIKAT_PATH.'img/404.png')||is_file($f=SURIKAT_SPATH.'img/404.png')){
					$this->Http_Request->code(404);
					$this->Http_Request->fileCache($f);
					readfile($f);
				}
				else{
					$this->Http_Request->code(404);
				}
			break;
		}
	}
	function cleanMini($ext=null){
		$f = SURIKAT_PATH.'.tmp/synaptic/min-registry.txt';
		if(!is_file($f))
			return;
		foreach(file($f) as $file){
			$file = trim($file);
			if(empty($file))
				continue;
			if($ext&&$ext!=pathinfo($file,PATHINFO_EXTENSION))
				continue;
			$file = realpath($file);
			if($file)
				unlink($file);
		}
		unlink($f);
	}
	protected function registerMini($min){
		$f = SURIKAT_PATH.'.tmp/synaptic/min-registry.txt';
		@mkdir(dirname($f),0777,true);
		file_put_contents($f,$min."\n",FILE_APPEND|LOCK_EX);
	}
	protected function minifyJS($f,$min){
		if(strpos($f,'://')===false&&!is_file($f))
			return false;
		set_time_limit(0);
		$c = $this->Minify_Js->process(file_get_contents($f));
		if(!$this->Dev_Level->JS){
			@mkdir(dirname($min),0777,true);
			$this->registerMini($min);
			file_put_contents($min,$c,LOCK_EX);
		}
		if(!headers_sent())
			header('Content-Type:application/javascript; charset=utf-8');
		echo $c;
	}
	protected function minifyCSS($f){
		if(!is_file($f)
			&&!is_file($f=dirname($f).'/'.pathinfo($f,PATHINFO_FILENAME).'.scss')
			&&!is_file($f=SURIKAT_SPATH.dirname($f).'/'.pathinfo($f,PATHINFO_FILENAME).'.scss')
			&&!is_file($f=SURIKAT_SPATH.dirname($f).'/'.basename($f))
		)
			return false;
		$e = pathinfo($f,PATHINFO_EXTENSION);
		if($e=='scss'){
			ob_start();
			$this->scss($f);
			$c = ob_get_clean();
		}
		else
			$c = file_get_contents($f);
		$c = $this->Minify_Css->process($c);
		if(!$this->Dev_Level->CSS){
			$min = dirname($f).'/'.pathinfo($f,PATHINFO_FILENAME).'.min.css';
			@mkdir(dirname($min),0777,true);
			$this->registerMini($min);
			file_put_contents($min,$c,LOCK_EX);
		}
		if(!headers_sent())
			header('Content-Type:text/css; charset=utf-8');
		echo $c;
	}
	protected function scss($path) {
		$from = [];
		$from[] = dirname($path);
		if(is_dir('css'))
			$from[] = 'css';
		if(is_dir(basename(SURIKAT_SPATH).'/css'))
			$from[] = basename(SURIKAT_SPATH).'/css';
		$this->Stylix_Server->serveFrom(pathinfo($path,PATHINFO_FILENAME).'.scss',$from);
	}
}