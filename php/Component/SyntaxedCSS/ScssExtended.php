<?php namespace Surikat\Component\SyntaxedCSS;
class ScssExtended extends Scss{
	function phpScssSupport($code){
		$code = $this->mixinSphpSupport($code);
		$code = $this->autoloadSphpSupport($code);
		$code = $this->shortOpentagSupport($code);
		$code = $this->autoloadScssSupport($code);
		$code = $this->fontSupport($code);
		$code = $this->evalFree($code);
		return $code;
	}
	protected function mixinSphpSupport($code){
		preg_match_all('/@\\?mixin\\s+([a-zA-Z0-9-]+)\\{(.*?)\\}\\?@/s',$code,$matches);
		if(!empty($matches)&&!empty($matches[0])){
			$pos = 0;
			foreach(array_keys($matches[0]) as $i){
				$fname = str_replace('-','_',$matches[1][$i]);
				$rep = '<?php if(!function_exists("'.$matches[1][$i].'")){ function scss_'.$fname.'($args){?>'.$matches[2][$i].'<?php }}?>';
				$code = substr($code,0,$pos=strpos($code,$matches[0][$i],$pos)).$rep.substr($code,$pos+strlen($matches[0][$i]));
			}
		}
		return $code;
	}
	protected function shortOpentagSupport($code){ //support of short open tag even if not supported by php.ini
		$r = [
			'<?'=>'<?php ',
			'<?php php'=>'<?php ',
			'<?php ='=>'<?=',
		];
		$code = str_replace(array_keys($r),array_values($r),$code);
		$tokens = token_get_all($code);
		$code = '';
		$opec = false;
		foreach($tokens as $token){ 
			if(is_array($token)){
				switch($token[0]){
					case T_OPEN_TAG:
						$code .= '<?php ';
					break;
					case T_OPEN_TAG_WITH_ECHO:
						$opec = true;
						$code .= '<?php echo ';
					break;
					case T_CLOSE_TAG:
						if($opec&&substr(trim($code),-1)!=';')
							$code .= ';';
						$code .= '?>';
						$opec = false;
					break;
					default:
						$code .= $token[1];
					break;
				}
			}
			else
				$code .= $token;
		}
		return $code;
	}
	protected function autoloadSphpSupport($code){
		$pos = 0;
		preg_match_all('/\/\/@\\?([^\\r\\n]+)/s',$code,$matches); //strip
		if(!empty($matches)&&!empty($matches[0]))
			foreach(array_keys($matches[0]) as $i)
				$code = substr($code,0,$pos=strpos($code,$matches[0][$i],$pos)).substr($code,$pos+1+strlen($matches[0][$i]));
		preg_match_all('/@\\?include\\s+([a-zA-Z0-9-]+)\\((.*?)\\);/s',$code,$matches);
		$pos = 0;
		if(!empty($matches)&&!empty($matches[0])){
			foreach(array_keys($matches[0]) as $i){
				$fname = str_replace('-','_',$matches[1][$i]);
				$func = 'scss_'.$fname;
				$arg = $matches[2][$i];
				$r = [
					'['=>'(',
					']'=>')',
					'{'=>'(',
					'}'=>')',
					'=>'=>':',
					'='=>':',
					':'=>'=>',
					"\t"=>"",
					"\n"=>"",
					"\r"=>"",
					')('=>'),(',
					'('=>'array(',
				];
				$arg = str_replace(array_keys($r),array_values($r),'('.trim($arg).')');
				preg_match_all('/([a-zA-Z0-9-$*]+)/s',$arg,$am);
				if(isset($am[0])){
					$_pos = 0;
					foreach($am[0] as $y=>$m){
						if(
							$m!='array'
							&&$m!='true'
							&&$m!='false'
							&&!is_numeric($am[1][$y])
							&&strpos($m,'$')===false
						){
							$s = substr($arg,0,$_pos=strpos($arg,$m,$_pos+2));
							$e = substr($arg,$_pos+strlen($m));
							if(($_s=substr($s,-1))!='"'&&$_s!="'")
								$s .= '"';
							if(($_e=substr($e,0,1))!='"'&&$_e!="'")
								$e = '"'.$e;
							$arg = $s.$am[1][$y].$e;
						}
					}
				}
				if(!function_exists($func)&&($path = $this->findImport('include/'.$matches[1][$i])))
					$this->importFile($path,$this);
				if(!function_exists($func))
					$this->throwError('Call to undefined mixin at "@?include '.$fname.'( ..."');
				$arg = "<?$func($arg);?>";
				$code = substr($code,0,$pos=strpos($code,$matches[0][$i],$pos)).$arg.substr($code,$pos+strlen($matches[0][$i]));
			}
		}
		return $code;
	}
	protected function autoloadScssSupport($code){
		preg_match_all('/@include\\s+([^\\(\\);]+)/s',$code,$matches);
		if(!empty($matches)&&!empty($matches[0])){
			foreach(array_keys($matches[0]) as $i){
				if(strpos($matches[1][$i],'#{')!==false)
					continue;
				$code = "@import 'include/{$matches[1][$i]}';\r\n$code";
			}
		}
		preg_match_all('/@extend\\s+([^;]+)/s',$code,$matches);
		if(!empty($matches)&&!empty($matches[0])){
			foreach(array_keys($matches[0]) as $i){
				if(strpos($matches[1][$i],'#{')!==false)
					continue;
				$inc = ltrim(str_replace('%','-',$matches[1][$i]),'-');
				$code = "@import 'extend/$inc';\r\n$code";
			}
		}
		return $code;
	}
	protected function fontSupport($code){
		$pos = 0;
		$tmpCode = $code;
		preg_match_all('/#\\{([^\\}]+)/s',$tmpCode,$matches); //strip
		if(!empty($matches)&&!empty($matches[0]))
			foreach(array_keys($matches[0]) as $i)
				$tmpCode = substr($tmpCode,0,$pos=strpos($tmpCode,$matches[0][$i],$pos)).'#var#'.substr($tmpCode,$pos+1+strlen($matches[0][$i]));
		preg_match_all('/\/\/([^\\r\\n]+)/s',$tmpCode,$matches); //strip
		if(!empty($matches)&&!empty($matches[0]))
			foreach(array_keys($matches[0]) as $i)
				$tmpCode = substr($tmpCode,0,$pos=strpos($tmpCode,$matches[0][$i],$pos)).substr($tmpCode,$pos+1+strlen($matches[0][$i]));
		preg_match_all('/\/\*(.*)\*\//s',$tmpCode,$matches); //strip
		if(!empty($matches)&&!empty($matches[0]))
			foreach(array_keys($matches[0]) as $i)
				$tmpCode = substr($tmpCode,0,$pos=strpos($tmpCode,$matches[0][$i],$pos)).substr($tmpCode,$pos+1+strlen($matches[0][$i]));
		preg_match_all('/@font-face([^\\}]+)/s',$tmpCode,$matches); //strip
		if(!empty($matches)&&!empty($matches[0]))
			foreach(array_keys($matches[0]) as $i)
				$tmpCode = substr($tmpCode,0,$pos=strpos($tmpCode,$matches[0][$i],$pos)).substr($tmpCode,$pos+1+strlen($matches[0][$i]));
		preg_match_all('/font-family(\\s+|):([^\\(\\);]+)/s',$tmpCode,$matches);
		if(!empty($matches)&&!empty($matches[0])){
			foreach(array_keys($matches[0]) as $i){
				$font = str_replace(' ','-',strtolower(trim(str_replace([':','"',"'"],'',$matches[2][$i]))));
				$x = explode(',',$font);
				foreach($x as $f)
					$code = "@import 'font/$f';\r\n$code";
				$tmpCode = substr($tmpCode,0,$pos=strpos($tmpCode,$matches[0][$i],$pos)).substr($tmpCode,$pos+1+strlen($matches[0][$i])); //strip
			}
		}
		preg_match_all('/font(\\s+|):([^\\(\\);]+)/s',$tmpCode,$matches);
		if(!empty($matches)&&!empty($matches[0])&&trim($matches[2][0])){
			foreach(array_keys($matches[0]) as $i){
				if(strpos($matches[2][$i],'#var#')!==false)
					continue;
				$font = strtolower(trim(str_replace([':','"',"'"],'',$matches[2][$i])));
				$y = [];
				$x = explode(' ',$font);
				foreach($x as $f)
					if(strpos($f,'/')===false&&(!(int)substr($f,0,-2)||(($e=substr($f,-2))!='px'&&$e!='em'))&&(!(int)substr($f,0,-1)||(substr($f,-1)!='%')))
						$y[] = $f;
				$font = implode('-',$y);
				$x = explode(',',$font);
				foreach($x as $f)
					$code = "@import 'font/$f';\r\n$code";
			}
		}
		return $code;
	}
	protected function evalFree($__code){
		ob_start();
		$o = &$this;
		$h = set_error_handler(function($errno, $errstr, $errfile, $errline)use($o,$__code){
			if(0===error_reporting())
				return false;
			ob_get_clean();
			$o->throwError(" error in eval php: %s \r\n in code: %s",$errstr,$__code);
		});
		if($this->Dev_Level->CSS&&strpos($__code,'//:eval_debug'))
			exit(print($__code));
		eval('?>'.$__code);
		$c = ob_get_clean();
		set_error_handler($h);
		return $c;
	}
}