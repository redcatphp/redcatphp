<?php namespace FileSystem;
use ObjexLoader\MutatorMagicTrait;
class Synaptic {
	use MutatorMagicTrait;
	protected $expires = 2592000;
	protected $allowedExtensions = ['css','js','jpg','jpeg','png','gif'];
	protected $dirs = [''];
	function setDirs($d){
		$this->dirs = (array)$d;
		foreach($this->dirs as $d){
			if($d)
				$this->dirs[$k] = rtrim($d,'/').'/';
		}
	}
	function prependDir($d){
		array_unshift($this->dirs,$d?rtrim($d,'/').'/':'');
	}
	function appendDir($d){
		$this->dirs[] = $d?rtrim($d,'/').'/':'';
	}
	function load($k){
		$extension = strtolower(pathinfo($k,PATHINFO_EXTENSION));
		if(!in_array($extension,$this->allowedExtensions)){
			$this->Http_Request->code(403);
			exit;
		}
		switch($extension){
			case 'js':
				foreach($this->dirs as $d){
					if(is_file($f=$d.$k)){
						header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->expires).'GMT');
						header('Content-Type: application/javascript; charset:utf-8');
						$this->Http_Request->fileCache($f);
						readfile($f);
						return;
					}
				}
				if(substr($k,-7,-3)=='.min'){
					$kv = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'?'https':'http').'://'.$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT']&&(int)$_SERVER['SERVER_PORT']!=80?':'.$_SERVER['SERVER_PORT']:'').'/'.substr($k,0,-7).'.js';
					$this->minifyJs($kv,$k);
					return;
				}				
				$this->Http_Request->code(404);
			break;
			case 'css':
				foreach($this->dirs as $d){
					if(is_file($f=$d.$k)){
						header('Expires: '.gmdate('D, d M Y H:i:s', time()+$this->expires).'GMT');
						header('Content-Type: text/css; charset:utf-8');
						$this->Http_Request->fileCache($f);
						readfile($f);
						return;
					}
				}
				if(substr($k,-8,-4)=='.min'){
					$this->minifyCSS(substr($k,0,-8).'.css');
					return;
				}
				foreach($this->dirs as $d){
					$file = $d.dirname($k).'/'.pathinfo($k,PATHINFO_FILENAME).'.scss';
					if(is_file($file)){
						if($this->scss($k)===false){
							$this->Http_Request->code(404);
						}
						return;
					}
				}
				$this->Http_Request->code(404);
			break;
			case 'png':
			case 'jpg':
			case 'jpeg':
			case 'gif':
				header('Content-Type:image/'.$extension.'; charset=utf-8');
				foreach($this->dirs as $d){
					if(is_file($f=$d.$k)){
						$this->Http_Request->fileCache($f);
						readfile($f);
						return;
					}
				}
				foreach($this->dirs as $d){
					if(is_file($f=$d.'img/404.png')){
						$this->Http_Request->code(404);
						$this->Http_Request->fileCache($f);
						readfile($f);
						return;
					}
				}
				$this->Http_Request->code(404);
			break;
		}
	}
	function cleanMini($ext=null){
		$f = '.tmp/synaptic/min-registry.txt';
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
		$f = '.tmp/synaptic/min-registry.txt';
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
	protected function minifyCSS($file){
		foreach($this->dirs as $d){
			if(is_file($f=$d.$file)||is_file($f=$d.dirname($file).'/'.pathinfo($file,PATHINFO_FILENAME).'.scss')){
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
					$dir = dirname($file);
					$min = $dir.'/'.pathinfo($file,PATHINFO_FILENAME).'.min.css';
					if(!is_dir($dir))
						@mkdir($dir,0777,true);
					$this->registerMini($min);
					file_put_contents($min,$c,LOCK_EX);
				}
				if(!headers_sent())
					header('Content-Type:text/css; charset=utf-8');
				echo $c;
				return;
			}
		}
		return false;
	}
	protected function scss($path) {
		$from = [];
		foreach($this->dirs as $d){
			if(is_dir($dir=$d.'css'))
				$from[] = $dir;
			if(is_dir($dir=$d.dirname($path)))
				$from[] = $dir;
		}
		$this->Stylix_Server->serveFrom(pathinfo($path,PATHINFO_FILENAME).'.scss',$from);
	}
}