<?php namespace surikat\service;
use surikat\control;
use surikat\control\HTTP;
use surikat\control\JSON;
use surikat\control\FS;
use surikat\control\scssc_server;
use surikat\control\scssc;
use surikat\control\Min\JS;
use surikat\control\Min\CSS;
class Service_Synaptic {
	static $SEND_HEADERS = true;
	static function method(){
		if(isset($_GET['file'])){
			self::load($_GET['file']);
		}
		elseif(isset($_GET['json'])){
			$json = (array)(func_num_args()>0?func_get_arg(0):(isset($_GET['json'])?JSON::decode($_GET['json'],true):null));
			$exclude = (array)(func_num_args()>1?func_get_arg(1):(isset($_GET['exclude'])?JSON::decode($_GET['exclude'],true):null));
			foreach($json as $k)
				if($k)
					self::load($k,null,$exclude);

		}
	}
	private static $__loaded = array();
	private static $__synapses = array();
	protected static function get($k,$from=null){
		$tmp = self::$SEND_HEADERS;
		self::$SEND_HEADERS = false;
		ob_start();
		self::load($k,$from);
		self::$SEND_HEADERS = $tmp;
		return ob_get_clean();
	}
	protected static function load($k,$from=null,$exclude=array()){
		//var_dump($k);
		$remote = strpos($k,'://')!==false;
		$dir = '';
		if(!$remote){
			$k = FS::get_absolute_path($k);
			if(strpos($k,'/'))
				$dir = dirname($k);
		}
		if(!isset(self::$__synapses[$dir])){
			if(is_file($file=control::$CWD.$dir.'/synaptic.json')){
				$synapse = JSON::decode(file_get_contents($file));
				self::$__synapses[$dir] = $synapse;
			}
			else{
				self::$__synapses[$dir] = null;
			}
		}
		$fk = basename($k);
		$synaptic = self::$__synapses[$dir]&&isset(self::$__synapses[$dir]->$fk)?self::$__synapses[$dir]->$fk:null;
		if(is_string($synaptic))
			$synaptic = (array)$synaptic;
		if(is_array($synaptic))
			$synaptic = (object)array('dependencies'=>$synaptic);
		if($synaptic){
			if(isset($synaptic->realpath))
				return self::load($synaptic->realpath,$k,$exclude);
			if(isset($synaptic->dependencies))
				foreach((array)$synaptic->dependencies as $d)
					self::load(($dir&&strpos($d,'/')!==0&&strpos($d,'://')===false?$dir.'/':'').$d,null,$exclude);
		}
		if(in_array($k,self::$__loaded))
			return;
		self::$__loaded[] = $k;
		if($remote){
			$args = array($k);
			if($synaptic&&isset($synaptic->updateDay))
				$args[] = $synaptic->updateDay;
			if($synaptic&&isset($synaptic->cacheAlias))
				$args[] = $synaptic->cacheAlias;
			call_user_func_array(array('self','cacheRemote'),$args);
		}
		elseif(is_file($k)){
			switch($extension=strtolower(pathinfo($k,PATHINFO_EXTENSION))){
				case 'js':
					self::cacheExpires();
					self::contentType('application/javascript');
					readfile($k);
				break;
				case 'json':
					self::cacheExpires();
					self::contentType('application/json');
					readfile($k);
				break;
				case 'css':
					self::cacheExpires();
					self::contentType('text/css');
					if(!$from||strpos($k,'://')!==false||($dir1=dirname($k))==($dir2=dirname($from)))
						readfile($k);
					else{
						$xd1 = explode('/',$dir1);
						$xd2 = explode('/',$dir2);
						$c = count($xd1);
						for($i=0;$i<$c;$i++)
							if(!(isset($xd2[$i])&&$xd1[$i]==$xd2[$i]))
								break;
						$dir1 = '';
						for($i=$i;$i<$c;$i++)
							$dir1 .= $xd1[$i];
						
						
						$relativity = rtrim(str_repeat('../',substr_count($k,'/')-$i).$dir1,'/').'/';
						echo "/* SynapticURI $k => $from: $relativity\r\n */";
						echo preg_replace('#url\((?!\s*[\'"]?(?:https?:)?//)\s*([\'"])?#',"url($1{$relativity}",file_get_contents($k));
					}
				break;
				case 'tml':
					view\FILE::display($k);
				break;
				case 'php':
					include $k;
				break;
				default:
					readfile($k);
				break;
			}
		}
		else{
			if(substr($k,-3)=='.js'){
				if(substr($k,-7,-3)=='.min'){
					if(substr($k,-15,-7)=='.combine')
						$k = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'?'https':'http').'://'.$_SERVER['SERVER_NAME'].'/'.$k;
					elseif(($k = substr($k,0,-7).'.js')&&!is_file($k)&&!is_file($k=basename(control::$SURIKAT).'/'.$k)){
						HTTP::code(404);
						throw new Exception('404');
						return;
					}
					self::minifyJS($k);
				}
			}
			elseif(substr($k,-4)=='.css'){
				if(substr($k,-8,-4)=='.min')
					self::minifyCSS(substr($k,0,-8).'.css');
				elseif(
					is_file(dirname($k).'/'.pathinfo($k,PATHINFO_FILENAME).'.scss')
					||(($k=basename(control::$SURIKAT).'/'.$k)&&is_file(dirname($k).'/'.pathinfo($k,PATHINFO_FILENAME).'.scss'))
				){
					if(self::scss($k)===false){
						HTTP::code(404);
						throw new Exception('404');
					}
				}
				else{
					HTTP::code(404);
					throw new Exception('404');
				}
			}
			else{
				HTTP::code(404);
				throw new Exception('404');
			}
		}
	}
	protected static function contentType($type){
		return self::header('Content-Type: '.$type.'; charset:utf-8');
	}
	protected static function cacheExpires($expires=2592000){ //1 month
		self::header('Expires: '.gmdate('D, d M Y H:i:s', time()+$expires).'GMT');
	}
	protected static function header($header){
		if(self::$SEND_HEADERS&&!headers_sent())
			header($header);
	}
	protected static function cacheFile($arg,$ext=''){
		return control::$TMP.'cache/'.trim($arg,'/').($ext?'.'.ltrim($ext,'.'):'');
	}
	protected static function cacheStore($file,$str){
		FS::mkdir($file,true);
		return file_put_contents($file,$str,LOCK_EX);
	}
	protected static function cacheRemote($rf,$day=7,$alias=null){
		$file = self::cacheFile($alias?$alias:str_replace(array('://','/'),'.',$rf));
		if((!is_file($file)||!filesize($file)||$day===true)||($day&&(time()-filemtime($file))>(86400*$day)))
			self::cacheStore($file,file_get_contents($rf));
		readfile($file);
	}
	protected static function minifyJS($f){
		if(!is_file($f))
			return false;
			
		set_time_limit(0);
		$min = dirname($f).'/'.pathinfo($f,PATHINFO_FILENAME).'.min.js';
		$c = JS::minify(file_get_contents($f));
		if(!control::$DEV)
			file_put_contents($min,$c);
		@header('Content-Type:application/javascript; charset=utf-8');
		echo $c;
	}
	protected static function minifyCSS($f){
		if(!is_file($f)
			&&!is_file($f=dirname($f).'/'.pathinfo($f,PATHINFO_FILENAME).'.scss')
			&&!is_file($f=control::$SURIKAT.dirname($f).'/'.pathinfo($f,PATHINFO_FILENAME).'.scss')
			&&!is_file($f=control::$SURIKAT.dirname($f).'/'.basename($f))
		)
			return false;
		$e = pathinfo($f,PATHINFO_EXTENSION);
		if($e=='scss'){
			ob_start();
			self::scss($f);
			$c = ob_get_clean();
		}
		else
			$c = file_get_contents($f);
		$c = CSS::minify($c);
		if(!control::$DEV)
			file_put_contents(dirname($f).'/'.pathinfo($f,PATHINFO_FILENAME).'.min.css',$c);
		@header('Content-Type:text/css; charset=utf-8');
		echo $c;
	}
	protected static function scss($path) {
		set_time_limit(0);
		scssc::$allowImportCSS = true;
		scssc::$allowImportRemote = true;
		$server = new scssc_server(dirname($path));
		$server->serve(pathinfo($path,PATHINFO_FILENAME).'.scss');
	}
}
