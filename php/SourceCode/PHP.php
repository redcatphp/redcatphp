<?php namespace Surikat\SourceCode;
abstract class PHP{
	static function extractCode($text){
		$tokens = token_get_all($text);
		$open = null;
		$php = '';
		foreach($tokens as $token){
			if(is_array($token)){
				switch($token[0]){
					case T_OPEN_TAG:
						$open = true;
						$php .= '<?php ';
					break;
					case T_OPEN_TAG_WITH_ECHO:
						$open = true;
						$php .= '<?php echo ';
					break;
					case T_CLOSE_TAG:
						$open = false;
						$php .= '?>';
					break;
					default:
						if($open)
							$php .= $token[1];
					break;
				}
			}
			else{
				if($open)
					$php .= $token;
			}
		}
		return $php;
	}
	static function namespacedConcat(&$str,$surikat=null){
		if($useS=self::namespacedCodeUsed($str,$surikat)){
			$str = strpos($str,'<?php ')===0?substr($str,5):'?>'.$str;
			$str = '<?php use '.($surikat?'\\Surikat\\':'').implode(';use '.($surikat?'\\Surikat\\':''),$useS).'; '.$str;
		}
		return $str;
	}
	static function classNamesInToken($tokens,$i,&$used=[],$rev=null){
		$inc = $rev?-1:1;
		if(isset($tokens[$y=$i+$inc])){
			while($tokens[$y][0]===T_WHITESPACE)
				$y+=$inc;
			if($tokens[$y][0]===T_STRING||$tokens[$y][0]===T_NS_SEPARATOR){
				$use = '';
				while($tokens[$y][0]===T_STRING||$tokens[$y][0]===T_NS_SEPARATOR){
					$use = $rev?$tokens[$y][1].$use:$use.$tokens[$y][1];
					$y+=$inc;
				}
				if(!in_array($use,$used))
					$used[] = $use;
			}
		}
	}
	static function namespacedCodeUsed($str,$surikat=null){
		$used = [];
		$tokens = token_get_all(self::extractCode($str));
		for($i=0;$i<count($tokens)-1;$i++)
			switch($tokens[$i][0]){
				case T_PAAMAYIM_NEKUDOTAYIM:
				case T_DOUBLE_COLON:
					self::classNamesInToken($tokens,$i,$used,true);
				break;
				case T_NEW:
					self::classNamesInToken($tokens,$i,$used);
				break;
			}
		if($surikat){
			$useS = [];
			foreach($used as $use)
				if(strpos(ltrim($use,'\\'),'Surikat\\')!==0&&class_exists($use)&&!is_file(SURIKAT_PATH.str_replace('\\','/',$use.'.php')))
					$useS[] = ltrim($use,'\\');
			return $useS;
		}
		return $used;
	}
	static function getOverriden($class,$c='methods'){
		$rClass = new \ReflectionClass($class);
		$array = [];
		$c = 'get'.ucfirst($c);
		foreach ($rClass->$c() as $rMethod)
			if ($rMethod->getDeclaringClass()->getName()==$rClass->getName())
				$array[] =  $rMethod->getName();
		return $array;
	}
	static function sourceNewArraySyntax($src){
		$code = token_get_all($src);
		$src = '';
		$i = [];
		$I = &$i[count($i)];
		$depth = 0;
		$state = 0;
		foreach($code as $c){
			if(is_array($c)){
				switch($c[0]){
					case T_ARRAY:
						$I = &$i[count($i)];
						$depth++;
						$state = 1;
					break;
					case T_VARIABLE:
						if($state){
							array_pop($i);
							$I = &$i[count($i)-1];
							$depth--;
							$state = 0;
							$src .= 'array ';
						}
					default:
						$src .= $c[1];
					break;
				}
			}
			else{
				if($c=='('){
					if($state){
						$state = 0;
						$src .= '[';
					}
					else{
						$I++;
						$src .= $c;
					}
				}
				elseif($c==')'){
					if($I--||!$depth)
						$src .= $c;
					else{
						$depth--;
						array_pop($i);
						$I = &$i[count($i)-1];
						$src .= ']';
					}
				}
				else
					$src .= $c;
			}
		}
		return $src;
	}
}