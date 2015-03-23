<?php namespace Surikat\Component\Dev;
class Debug{
	private $errorHandler;
	private $registeredErrorHandler;
	private $debugLines = 5;
	private $errorType;
	private $debugStyle = '<style>code br{line-height:0.1em;}pre.error{display:block;position:relative;z-index:99999;}pre.error span:first-child{color:#d00;}</style>';
	public $debugWrapInlineCSS = 'margin:4px;padding:4px;border:solid 1px #ccc;border-radius:5px;overflow-x:auto;background-color:#fff;';
	function errorHandler($set=true){
		$this->errorHandler = $set;
		if($set){
			error_reporting(-1);
			ini_set('display_startup_errors',true);
			ini_set('display_errors','stdout');
			if(!$this->registeredErrorHandler){
				$this->registeredErrorHandler = true;
				set_error_handler([$this,'errorHandle']);
				register_shutdown_function([$this,'fatalErrorHandle']);
				set_exception_handler([$this,'catchException']);
			}
		}
		else{
			error_reporting(0);
			ini_set('display_startup_errors',false);
			ini_set('display_errors',false);
		}
	}
	function catchException($e){
		$html = false;
		if(!headers_sent()){
			header("Content-Type: text/html; charset=utf-8");
			$html = true;
		}
		$msg = 'Exception: '.$e->getMessage();
		if($html){
			echo $this->debugStyle;
			echo '<pre class="error" style="'.$this->debugWrapInlineCSS.'"><span>'.$msg."</span>\nStackTrace:\n";
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
	function errorHandle($code, $message, $file, $line){
		if(!$this->errorHandler||!ini_get('error_reporting'))
			return;
		$html = false;
		if(!headers_sent()){
			header("Content-Type: text/html; charset=utf-8");
			$html = true;
		}
		$msg = $this->errorType[$code]."\t$message\nFile\t$file\nLine\t$line";
		if($html){
			echo $this->debugStyle;
			echo "<pre class=\"error\" style=\"".$this->debugWrapInlineCSS."\"><span>".$msg."</span>\nContext:\n";
			//$f = file($file);
			$f = explode("\n",str_replace(["\r\n","\r"],"\n",file_get_contents($file)));
			foreach($f as &$x)
				$x .= "\n";
			$c = count($f);			
			$start = $line-$this->debugLines;
			$end = $line+$this->debugLines;
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
			$count = count($x);
			for($i=0;$i<$count;$i++){
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
	function fatalErrorHandle(){
		if(!$this->errorHandler)
			return;
		$error = error_get_last();
		if($error['type']===E_ERROR){
			self::errorHandle(E_ERROR,$error['message'],$error['file'],$error['line']);
		}
	}
	function __construct(){
		$this->errorType = [
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
		  $this->errorType[E_STRICT] = 'runtime notice';
	}
	function errorType($code){
		return isset($this->errorType[$code])?$this->errorType[$code]:null;
	}
}