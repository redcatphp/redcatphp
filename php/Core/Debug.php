<?php namespace Surikat\Core;
class Debug{
	private static $errorHandler;
	private static $registeredErrorHandler;
	private static $debugLines = 5;
	private static $errorType;
	private static $debugStyle = '<style>code br{line-height:0.1em;}pre.error{display:block;position:relative;z-index:99999;}pre.error span:first-child{color:#d00;}</style>';
	static $debugWrapInlineCSS = 'margin:4px;padding:4px;border:solid 1px #ccc;border-radius:5px;overflow-x:auto;background-color:#fff;';
	static function errorHandler($set=true){
		self::$errorHandler = $set;
		if($set){
			error_reporting(-1);
			ini_set('display_startup_errors',true);
			ini_set('display_errors','stdout');
			if(!self::$registeredErrorHandler){
				self::$registeredErrorHandler = true;
				set_error_handler(['Surikat\Core\Debug','errorHandle']);
				register_shutdown_function(['Surikat\Core\Debug','fatalErrorHandle']);
				set_exception_handler(['Surikat\Core\Debug','catchException']);
			}
		}
		else{
			error_reporting(0);
			ini_set('display_startup_errors',false);
			ini_set('display_errors',false);
		}
	}
	static function catchException($e){
		$html = false;
		if(!headers_sent()){
			header("Content-Type: text/html; charset=utf-8");
			$html = true;
		}
		$msg = 'Exception: '.$e->getMessage();
		if($html){
			echo self::$debugStyle;
			echo '<pre class="error" style="'.self::$debugWrapInlineCSS.'"><span>'.$msg."</span>\nStackTrace:\n";
			echo '#'.get_class($e);
			if(method_exists($e,'getData')){
				echo ':';
				var_dump($e->getData());
			}
			echo htmlentities($e->getTraceAsString());
			echo '</pre>';
		}
		else{
			echo strip_tags($msg);
		}
		return false;
	}
	static function errorHandle($code, $message, $file, $line){
		if(!self::$errorHandler||!ini_get('error_reporting'))
			return;
		$html = false;
		if(!headers_sent()){
			header("Content-Type: text/html; charset=utf-8");
			$html = true;
		}
		$msg = self::$errorType[$code]."\t$message\nFile\t$file\nLine\t$line";
		if($html){
			echo self::$debugStyle;
			echo "<pre class=\"error\" style=\"".self::$debugWrapInlineCSS."\"><span>".$msg."</span>\nContext:\n";
			$f = file($file);
			$c = count($f);
			$start = $line-self::$debugLines;
			$end = $line+self::$debugLines;
			if($start<0)
				$start = 0;
			if($end>($c-1))
				$end = $c-1;
			$e = '';
			for($i=$start;$i<=$end;$i++){
				$e .= $f[$i];
			}
			$e = highlight_string('<?php '.$e,true);
			$e = str_replace('<br />',"\n",$e);
			$e = substr($e,35);
			$x = explode("\n",$e);
			$e = '<code><span style="color: #000000">';
			for($i=0;$i<count($x);$i++){
				$y = $start+$i;
				$e .= '<span style="color:#'.($y==$line?'d00':'070').';">'.$y."\t</span>";
				$e .= $x[$i]."\n";
			}
			$p = strpos($e,'&lt;?php');
			$e = substr($e,0,$p).substr($e,$p+8);
			echo $e;
			echo '</pre>';
		}
		else{
			echo strip_tags($msg);
		}
		return true;
	}
	static function fatalErrorHandle(){
		if(!self::$errorHandler)
			return;
		$error = error_get_last();
		if($error['type']===E_ERROR){
			self::errorHandle(E_ERROR,$error['message'],$error['file'],$error['line']);
		}
	}
	static function initialize(){
		self::$errorType = [
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
			E_USER_NOTICE     => 'user notice'
		];
		if(defined('E_STRICT'))
		  self::$errorType[E_STRICT] = 'runtime notice';
	}
	static function errorType($code){
		return isset(self::$errorType[$code])?self::$errorType[$code]:null;
	}
}
Debug::initialize();