<?php namespace Surikat\HTTP;
use Surikat\DependencyInjection\MutatorProperty;
class Request{
	use MutatorProperty;
	protected $server;
	function __construct($server=null){
		if(!$server)
			$server = &$_SERVER;
		$this->server = $server;
	}
	function reloadLocation(){
		header('Location: '.$this->HTTP_URL->getLocation(),false,302);
	}
	function isAjax(){
		return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])&&strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])=='xmlhttprequest';
	}
	function getallheaders(){ //for ngix compatibility
		if(function_exists('getallheaders')){
			return call_user_func_array('getallheaders',func_get_args());
		}
		elseif(function_exists('apache_request_headers')){
			return call_user_func_array('apache_request_headers',func_get_args());
		}
		else{
			$headers = [];
			foreach($_SERVER as $name=>$value){
				if(substr($name, 0, 5)=='HTTP_'){
					$headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
				}
			}
			return $headers;
		}
	}
	function fileCache($output){
		$mtime = filemtime($output);
		$etag = $this->HTTP_Request->FileEtag($output);
		header('Last-Modified: '.gmdate('D, d M Y H:i:s',$mtime).' GMT', true);
		header('Etag: '.$etag);
		if(!$this->HTTP_Request->isModified($mtime,$etag)){
			$this->HTTP_Request->code(304);
			exit;
		}
	}
	function isModified($mtime,$etag){
		return !((isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])&&@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE'])>=$mtime)
			||(isset($_SERVER['HTTP_IF_NONE_MATCH'])&&$_SERVER['HTTP_IF_NONE_MATCH'] == $etag));
	}
	function getRealIpAddr(){
		return !empty($_SERVER['HTTP_CLIENT_IP'])?$_SERVER['HTTP_CLIENT_IP']:(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']);
	}
	function nocacheHeaders(){
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
		header("Last-Modified: " . gmdate("D, d M Y H:i:s" ) . " GMT" );
		header("Pragma: no-cache");
		header("Cache-Control: no-cache");
		header("Expires: -1");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Cache-Control: no-store, no-cache, must-revalidate");
	}
	function fix_magic_quotes(){
		if(get_magic_quotes_gpc()){
			$strip_slashes_deep = function ($value) use (&$strip_slashes_deep) {
				return is_array($value) ? array_map($strip_slashes_deep, $value) : stripslashes($value);
			};
			$_GET = array_map($strip_slashes_deep, $_GET);
			$_POST = array_map($strip_slashes_deep, $_POST);
			$_COOKIE = array_map($strip_slashes_deep, $_COOKIE);
			$_REQUEST = array_map($strip_slashes_deep, $_REQUEST);
		}
	}
	protected $codes = [
		505=>'HTTP Version Not Supported',
		504=>'Gateway Timeout',
		503=>'Service Unavailable',
		502=>'Bad Gateway',
		501=>'Not Implemented',
		500=>'Internal Server Error',
		417=>'Expectation Failed',
		416=>'Requested Range Not Satisfiable',
		415=>'Unsupported Media Type',
		414=>'Request-URI Too Long',
		413=>'Request Entity Too Large',
		412=>'Precondition Failed',
		411=>'Length Required',
		410=>'Gone',
		409=>'Conflict',
		408=>'Request Timeout',
		407=>'Proxy Authentication Required',
		406=>'Not Acceptable',
		405=>'Method Not Allowed',
		404=>'Not Found',
		403=>'Forbidden',
		402=>'Payment Required', //available in future
		401=>'Unauthorized',
		400=>'Bad Request',
		307=>'Temporary Redirect',
		306=>'', //unused
		305=>'Use Proxy',
		304=>'Not Modified',
		303=>'See Other',
		302=>'Found',
		301=>'Moved Permanently',
		206=>'Partial Content',
		205=>'Reset Content',
		204=>'No Content',
		203=>'Non-Authoritative Information',
		202=>'Accepted',
		201=>'Created',
		200=>'OK',
		101=>'Switching Protocols',
		100=>'Continue',
	];
	function code($n=505){
		if(headers_sent()) return false;
		if(!isset($this->codes[$n])) $n = 500;
		header($_SERVER['SERVER_PROTOCOL'].' '.$n.' '.$this->codes[$n]);
		return true;

	}
	function verifPlageIP($IP,$PlageIP){
		$result=TRUE;
		$tabIP=explode(".",$IP);
		if(is_array($PlageIP)){
			foreach($PlageIP as $valeur){
				$tabPlageIP[]=explode(".",$valeur);
			}
			for($i=0;$i<4;$i++){
				if(($tabIP[$i]<$tabPlageIP[0][$i]) || ($tabIP[$i]>$tabPlageIP[1][$i])){
					$result=FALSE;
				}
			}
		}
		else{
			$tabPlageIP=explode(".",$PlageIP);
			for($i=0;$i<4;$i++){
				if(($tabIP[$i]!=$tabPlageIP[$i])){
					$result=FALSE;
				}
			}
		}
		return ($result);		
	}

	function startNewHTTPHeader(){
		ob_end_clean();
		header("Connection: close \r\n");
		header("Content-Encoding: none\r\n");
		ignore_user_abort( true );
		ob_start();
	}
	/* Note that this must be called after startNewHTTPHeader!
	Ends the header (and so the connection) setup with the user.
	All HTTP and echo'd text will not be sent after calling this.
	This allows you to continue performing processing on server side. */
	function endHTTPHeader(){
		header('Content-Length: '.ob_get_length());
		ob_end_flush();
		flush();
		ob_end_clean();
	}
	
	function stripWWW(){
		if(preg_match('/^www.(.+)$/i', $_SERVER['HTTP_HOST'], $matches)){
			header("Status: 301 Move permanently", false, 301);
			header('Location: http://'.$matches[1].$_SERVER['REQUEST_URI']);
			exit;
		}
	}
	function forceSSL($ssl=true){
		if($ssl){
			if(!isset($_SERVER['HTTPS'])||$_SERVER['HTTPS']!='on'){
				header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
				exit;
			}
		}
		else{
			if(isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']=='on'){
				header('Location: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
				exit;
			}
		}
	}
	function requestheader($key){
		$headers = $this->getallheaders();
		if(func_num_args()>1)
			return isset($headers[$key])&&$headers[$key]==func_get_arg(1);
		else
			return isset($headers[$key])?$headers[$key]:false;
	}
	function FileEtag($file){
		$s = stat($file);
		return sprintf('%x-%s', $s['size'], base_convert(str_pad($s['mtime'], 16, "0"),10,16));
	}
	function reArrange(&$arr){
		$new = [];
		if(
			isset($arr['name'])
			&&isset($arr['type'])
			&&isset($arr['size'])
			&&isset($arr['tmp_name'])
			&&isset($arr['error'])
		){
			if(is_array($arr['name'])){
				foreach(array_keys($arr['name']) as $k){
					if(is_array($arr['name'][$k])){
						foreach(array_keys($arr['name'][$k]) as $key){
							$new[] = [
								'name'		=>&$arr['name'][$k][$key],
								'type'		=>&$arr['type'][$k][$key],
								'size'		=>&$arr['size'][$k][$key],
								'tmp_name'	=>&$arr['tmp_name'][$k][$key],
								'error'		=>&$arr['error'][$k][$key],
							];
						}
					}
					else{
						$new[] = [
							'name'		=>&$arr['name'][$k],
							'type'		=>&$arr['type'][$k],
							'size'		=>&$arr['size'][$k],
							'tmp_name'	=>&$arr['tmp_name'][$k],
							'error'		=>&$arr['error'][$k],
						];
					}
				}
			}
			else{
				$new[] = $arr;
			}
		}
		else{
			foreach($arr as &$a){
				$new = array_merge($new,$this->reArrange($a));
			}
		}
		return $new;
	}
	function gzipSupport(){
		if(isset($_SERVER['HTP_ACCEPT_ENCODING'])&&strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip')!==false)
			return 'x-gzip';
		elseif(isset($_SERVER['HTTP_ACCEPT_ENCODING'])&&strpos($_SERVER['HTTP_ACCEPT_ENCODING'],'gzip')!==false)
			return 'gzip';
	}
	function filterDotDotSlash($val){
		if(is_integer($val))
			return $val;
		elseif(is_array($val)){
			foreach(array_keys($val) as $k){
				if(!is_integer($k)){
					$tmp = $k;
					$k = $this->filterDotDotSlash($k);
					if($k!=$tmp){
						$val[$k] = $val[$tmp];
						unset($val[$tmp]);
					}
				
				}
				$val[$k] = $this->filterDotDotSlash($val[$k]);
			}
			return $val;
		}
		while(!(stripos($val,'./')===false&&stripos($val,'..')===false))
			$val = str_replace(['./','..'],'',$val);
		return $val;
	}
}