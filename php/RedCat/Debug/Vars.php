<?php
/*
 * Vars - Lighter alternative to var_dump() with backtrace of the call and syntax highlighting
 *
 * @package Debug
 * @version 1.2
 * @link http://github.com/redcatphp/Debug/
 * @author Jo Surikat <jo@surikat.pro>
 * @website http://redcatphp.com
 */
namespace RedCat\Debug;
abstract class Vars{
	static function debugs(){
		if(!headers_sent())
			header('Content-Type: text/html; charset=utf-8');
		echo self::debug_backtrace_html();
		foreach(func_get_args() as $v){
			echo self::debug_html_return($v);
		}
	}
	static function dbugs(){
		if(!headers_sent())
			header('Content-Type: text/plain; charset=utf-8');
		echo self::debug_backtrace();
		foreach(func_get_args() as $v){
			echo self::debug_return($v);
			echo "\n";
		}
	}
	static function debug_html($variable,$strlen=1000,$width=25,$depth=10,$i=0,&$objects = []){
		if(!headers_sent())
			header('Content-Type: text/html; charset=utf-8');
		echo self::debug_backtrace_html();
		echo self::debug_html_return($variable,$strlen,$width,$depth,$i,$objects);
	}
	static function debug_html_return($variable,$strlen=1000,$width=25,$depth=10,$i=0,&$objects = []){
		$search = ['&',"\r", "\n", ' ','"',"'",'<','>'];
		$replace = ['&#039;',"<br />", "<br />", '&nbsp;','&#34;','&#39;','&lt;','&gt;'];
		$string = '';
		switch(gettype($variable)){
			case 'boolean':
				$string.= $variable?'true':'false';
			break;
			case 'integer':
				$string.= $variable;
			break;
			case 'double':
				$string.= $variable;
			break;
			case 'resource':
				$string.= '[resource]';
			break;
			case 'NULL':
				$string.= "null";
			break;
			case 'unknown type':
				$string.= '???';
			break;
			case 'string':
				$len = strlen($variable);
				if($strlen)
					$variable = substr($variable,0,$strlen);
				$variable = str_replace($search,$replace,$variable);
				if (!$strlen||$len<$strlen)
					$string.= '"'.$variable.'"';
				else
					$string.= 'string('.$len.'): "'.$variable.'"...';
			break;
			case 'array':
				$len = count($variable);
				if($i==$depth)
					$string.= 'array('.$len.') {...}';
				elseif(!$len)
					$string.= 'array(0) {}';
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
							$string.= self::debug_html_return($variable[$key],$strlen,$width,$depth,$i+1,$objects);
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
					$spaces = str_repeat('&nbsp;',($i+1)*4);
					$string.= 'object('.$c.')'."#$id{";
					$array = (array)$variable;
					if(!empty($array)){
						$string .= "<br />".$spaces;
						$y = 0;
						foreach($array as $property=>$value) {
							$name = str_replace("\0",':',trim($property));
							if($y)
								$string.= '<br />'.$spaces;
							if(strpos($name,$c.':')===0)
								$name = '$'.substr($name,strlen($c)+1);
							elseif(strpos($name,'*:')===0)
								$name = '$'.substr($name,2);
							$string.= "[$name] => ";
							$string.= self::debug_html_return($value,$strlen,$width,$depth,$i+1,$objects);
							$y++;
						}
						$spaces = str_repeat('&nbsp;',$i*4);
						$string.= "<br />".$spaces;
					}
					$string.= '}';
				}
			break;
		}
		if ($i>0)
			return $string;
		$maps = [
			'countable' => '/(?P<type>array|int|string)\((?P<count>\d+)\)/',
			'object'    => '/object\((?P<class>[a-z_\\\]+)\)\#(?P<id>\d+)/i',
			'object2'    => '/object\((?P<class>[a-z_\\\]+)\)/i',
		];
		foreach($maps as $function => $pattern)
			$string = preg_replace_callback($pattern, array('self', '_process' . ucfirst($function)), $string);
		$string = '<div style="border:1px solid #bbb;border-radius:4px;font-size:12px;line-height:1.4em;margin:3px;padding:4px;">' . $string . '</div>';
		return $string;
	}
	static function debug($variable,$strlen=1000,$width=25,$depth=10,$i=0,&$objects = []){
		if(!headers_sent())
			header('Content-Type: text/plain; charset=utf-8');
		echo self::debug_backtrace();
		echo self::debug_return($variable,$strlen,$width,$depth,$i,$objects);
	}
	static function debug_return($variable,$strlen=1000,$width=25,$depth=10,$i=0,&$objects = []){
		$string = '';
		switch(gettype($variable)){
			case 'boolean':
				$string.= $variable?'true':'false';
			break;
			case 'integer':
				$string.= $variable;
			break;
			case 'double':
				$string.= $variable;
			break;
			case 'resource':
				$string.= '[resource]';
			break;
			case 'NULL':
				$string.= "null";
			break;
			case 'unknown type':
				$string.= '???';
			break;
			case 'string':
				$len = strlen($variable);
				if($strlen)
					$variable = substr($variable,0,$strlen);
				if (!$strlen||$len<$strlen)
					$string.= '"'.$variable.'"';
				else
					$string.= 'string('.$len.'): "'.$variable.'"...';
			break;
			case 'array':
				$len = count($variable);
				if ($i==$depth)
					$string.= 'array('.$len.') {...}';
				elseif(!$len)
					$string.= 'array(0) {}';
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
						$string.= self::debug_return($variable[$key],$strlen,$width,$depth,$i+1,$objects);
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
					$spaces = str_repeat(' ',$i*2);
					$string.= get_class($variable)."#$id\n".$spaces.'{';
					$array = (array)$variable;
					foreach($array as $property=>$value) {
						$name = str_replace("\0",':',trim($property));
						$string.= "\n".$spaces."	[$name] => ";
						$string.= self::debug_return($value,$strlen,$width,$depth,$i+1,$objects);
					}
					$string.= "\n".$spaces.'}';
				}
			break;
		}
		return $string;
	}
	static function debug_backtrace_html(){
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		do $caller = array_shift($backtrace);
			while ($caller && (!isset($caller['file'])||$caller['file']===__FILE__||$caller['file']===__DIR__.'/functions.inc.php'));
		if($caller)
			return '<div style="color: #50a800;font-size:12px;">'.$caller['file'].'</span>:<span style="color: #ff0000;font-size:12px;">'.$caller['line'].'</div>';
	}
	static function debug_backtrace(){
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		do $caller = array_shift($backtrace);
			while ($caller && (!isset($caller['file'])||$caller['file']===__FILE__||$caller['file']===__DIR__.'/functions.inc.php'));
		if ($caller)
			return "\n".$caller['file'].':'.$caller['line']."\n";
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