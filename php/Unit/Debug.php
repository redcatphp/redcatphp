<?php
namespace Unit{
	class Debug{
		private static $errorType;
		private $handleErrors;
		private $registeredErrorHandler;
		private $debugLines = 5;
		private $debugStyle = '<style>code br{line-height:0.1em;}pre.error{display:block;position:relative;z-index:99999;}pre.error span:first-child{color:#d00;}</style>';
		public $debugWrapInlineCSS = 'margin:4px;padding:4px;border:solid 1px #ccc;border-radius:5px;overflow-x:auto;background-color:#fff;';
		function handleErrors(){
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
			if(!$this->handleErrors||!ini_get('error_reporting'))
				return;
			$html = false;
			if(!headers_sent()){
				header("Content-Type: text/html; charset=utf-8");
				$html = true;
			}
			$msg = self::$errorType[$code]."\t$message\nFile\t$file\nLine\t$line";
			if(is_file($file)){
				if($html){
					echo $this->debugStyle;
					echo "<pre class=\"error\" style=\"".$this->debugWrapInlineCSS."\"><span>".$msg."</span>\nContext:\n";
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
			}
			//else{
				//echo "$message in $file on line $line";
			//}
			return true;
		}
		function fatalErrorHandle(){
			if(!$this->handleErrors)
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
				E_USER_NOTICE     => 'user notice',
				E_STRICT          => 'strict standard error',
				E_RECOVERABLE_ERROR => 'recoverable error',
				E_DEPRECATED      => 'deprecated error',
				E_USER_DEPRECATED => 'user deprecated error',
			];
			if(defined('E_STRICT'))
			  self::$errorType[E_STRICT] = 'runtime notice';
		}
		static function errorType($code){
			return isset(self::$errorType[$code])?self::$errorType[$code]:null;
		}
		static function var_debug_html($variable,$strlen=100,$width=25,$depth=10,$i=0,&$objects = array(),$return = false){
			$search = array("\r", "\n", ' ','&','"',"'",'<','>');
			$replace = array("<br />", "<br />", '&nbsp;','&amp;','&quot;','&#039;','&lt;','&gt;');
			$string = '';
			switch(gettype($variable)) {
				case 'boolean':			$string.= $variable?'true':'false'; break;
				case 'integer':			$string.= $variable;								break;
				case 'double':			 $string.= $variable;								break;
				case 'resource':		 $string.= '[resource]';						 break;
				case 'NULL':				 $string.= "null";									 break;
				case 'unknown type': $string.= '???';										break;
				case 'string':
					$len = strlen($variable);
					$variable = str_replace($search,$replace,substr($variable,0,$strlen),$count);
					$variable = substr($variable,0,$strlen);
					if ($len<$strlen) $string.= '"'.$variable.'"';
					else $string.= 'string('.$len.'): "'.$variable.'"...';
				break;
				case 'array':
					$len = count($variable);
					if ($i==$depth) $string.= 'array('.$len.') {...}';
					elseif(!$len) $string.= 'array(0) {}';
					else {
						$keys = array_keys($variable);
						$spaces = str_repeat('&nbsp;',($i+1)*4);
						$string.= "array($len){";
						if(!empty($keys)){
							$string.= '<br />'.$spaces;
							$count=0;
							foreach($keys as $y=>$key) {
								if ($count==$width) {
									$string.= "<br />".$spaces."...";
									break;
								}
								if($y)
									$string.= '<br />'.$spaces;
								$string.= "[$key] => ";
								$string.= self::var_debug_html($variable[$key],$strlen,$width,$depth,$i+1,$objects);
								$count++;
							}
							$spaces = str_repeat('&nbsp;',$i*4);
							$string.="<br />".$spaces;
						}
						$string.='}';
					}
				break;
				case 'object':
					$c = get_class($variable);
					$id = array_search($variable,$objects,true);
					if ($id!==false)
					$string.='object('.$c.')'.'#'.($id+1).'{...}';
						else if($i==$depth)
						$string.='object('.$c.'){...}';
					else {
						$id = array_push($objects,$variable);
						$array = (array)$variable;
						$spaces = str_repeat('&nbsp;',($i+1)*4);
						$string.= 'object('.$c.')'."#$id{";
						$properties = array_keys($array);
						if(!empty($properties)){
							$string .= "<br />".$spaces;
							foreach($properties as $y=>$property) {
								$name = str_replace("\0",':',trim($property));
								if($y)
									$string.= '<br />'.$spaces;
								if(strpos($name,$c.':')===0)
									$name = '$'.substr($name,strlen($c)+1);
								elseif(strpos($name,'*:')===0)
									$name = '$'.substr($name,2);
								$string.= "[$name] => ";
								$string.= self::var_debug_html($array[$property],$strlen,$width,$depth,$i+1,$objects);
							}
							$spaces = str_repeat('&nbsp;',$i*4);
							$string.= "<br />".$spaces;
						}
						$string.= '}';
					}
				break;
			}
			if ($i>0) return $string;
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			do $caller = array_shift($backtrace); while ($caller && (!isset($caller['file'])||$caller['file']===__FILE__));
			
			$maps = [
                'countable' => '/(?P<type>array|int|string)\((?P<count>\d+)\)/',
                'object'    => '/object\((?P<class>[a-z_\\\]+)\)\#(?P<id>\d+)/i',
                'object2'    => '/object\((?P<class>[a-z_\\\]+)\)/i',
            ];
            foreach($maps as $function => $pattern)
                $string = preg_replace_callback($pattern, array('self', '_process' . ucfirst($function)), $string);
            $string = '<div style="border:1px solid #bbb;border-radius:4px;font-size:12px;line-height:1.4em;margin:3px;padding:4px;">' . $string . '</div>';
			
			if ($caller) $string = '<br /><span style="color: #50a800;font-size:12px;">'.$caller['file'].'</span>:<span style="color: #ff0000;font-size:12px;">'.$caller['line'].'</span>'.$string;
			
			if($return)
				return $string;
			echo $string;
		}
		static function var_debug($variable,$strlen=100,$width=25,$depth=10,$i=0,&$objects = array(),$return = false){
			$search = array("\0", "\a", "\b", "\f", "\n", "\r", "\t", "\v");
			$replace = array('\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v');
			$string = "\n";
			switch(gettype($variable)) {
			case 'boolean':			$string.= $variable?'true':'false'; break;
			case 'integer':			$string.= $variable;								break;
			case 'double':			 $string.= $variable;								break;
			case 'resource':		 $string.= '[resource]';						 break;
			case 'NULL':				 $string.= "null";									 break;
			case 'unknown type': $string.= '???';										break;
			case 'string':
				$len = strlen($variable);
				$variable = str_replace($search,$replace,substr($variable,0,$strlen),$count);
				$variable = substr($variable,0,$strlen);
				if ($len<$strlen) $string.= '"'.$variable.'"';
				else $string.= 'string('.$len.'): "'.$variable.'"...';
				break;
			case 'array':
				$len = count($variable);
				if ($i==$depth) $string.= 'array('.$len.') {...}';
				elseif(!$len) $string.= 'array(0) {}';
				else {
				$keys = array_keys($variable);
				$spaces = str_repeat(' ',$i*2);
				$string.= "array($len)\n".$spaces.'{';
				$count=0;
				foreach($keys as $key) {
					if ($count==$width) {
					$string.= "\n".$spaces."	...";
					break;
					}
					$string.= "\n".$spaces."	[$key] => ";
					$string.= self::var_debug($variable[$key],$strlen,$width,$depth,$i+1,$objects);
					$count++;
				}
				$string.="\n".$spaces.'}';
				}
				break;
			case 'object':
				$id = array_search($variable,$objects,true);
				if ($id!==false)
				$string.=get_class($variable).'#'.($id+1).' {...}';
				else if($i==$depth)
				$string.=get_class($variable).' {...}';
				else {
				$id = array_push($objects,$variable);
				$array = (array)$variable;
				$spaces = str_repeat(' ',$i*2);
				$string.= get_class($variable)."#$id\n".$spaces.'{';
				$properties = array_keys($array);
				foreach($properties as $property) {
					$name = str_replace("\0",':',trim($property));
					$string.= "\n".$spaces."	[$name] => ";
					$string.= self::var_debug($array[$property],$strlen,$width,$depth,$i+1,$objects);
				}
				$string.= "\n".$spaces.'}';
				}
				break;
			}
			if ($i>0) return $string;
			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			do $caller = array_shift($backtrace); while ($caller && (!isset($caller['file'])||$caller['file']===__FILE__));
			if ($caller) $string = "\n".$caller['file'].':'.$caller['line'].$string;
			if($return)
				return $string;
			echo $string;
		}
		
		private static function _processCountable(array $matches){
			$type = '<span style="color: #0000FF;">' . $matches['type'] . '</span>';
			$count = '(<span style="color: #1287DB;">' . $matches['count'] . '</span>)';
	 
			return $type . $count;
		}
		private static function _processObject(array $matches){
			return '<span style="color: #0000FF;">object</span>(<span style="color: #4D5D94;">' . $matches['class'] . '</span>)#' . $matches['id'];
		}
		private static function _processObject2(array $matches){
			return '<span style="color: #0000FF;">object</span>(<span style="color: #4D5D94;">' . $matches['class'] . '</span>)';
		}
	}
	Debug::initialize();
}
namespace{
	function dbug(){
		return call_user_func_array(['Unit\Debug','var_debug'],func_get_args());
	}
	function debug(){
		return call_user_func_array(['Unit\Debug','var_debug_html'],func_get_args());
	}
	function dbugs(){
		foreach(func_get_args() as $v){
			echo "\n";
			Unit\Debug::var_debug($v);
		}
	}
	function debugs(){
		foreach(func_get_args() as $v){
			echo '<br />';
			Unit\Debug::var_debug_html($v);
		}
	}
}