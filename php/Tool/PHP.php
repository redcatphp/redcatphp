<?php namespace Surikat\Tool;
use Surikat\Tool;
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
/*
    [T_REQUIRE_ONCE] => 258
    [T_REQUIRE] => 259
    [T_EVAL] => 260
    [T_INCLUDE_ONCE] => 261
    [T_INCLUDE] => 262
    [T_LOGICAL_OR] => 263
    [T_LOGICAL_XOR] => 264
    [T_LOGICAL_AND] => 265
    [T_PRINT] => 266
    [T_SR_EQUAL] => 267
    [T_SL_EQUAL] => 268
    [T_XOR_EQUAL] => 269
    [T_OR_EQUAL] => 270
    [T_AND_EQUAL] => 271
    [T_MOD_EQUAL] => 272
    [T_CONCAT_EQUAL] => 273
    [T_DIV_EQUAL] => 274
    [T_MUL_EQUAL] => 275
    [T_MINUS_EQUAL] => 276
    [T_PLUS_EQUAL] => 277
    [T_BOOLEAN_OR] => 278
    [T_BOOLEAN_AND] => 279
    [T_IS_NOT_IDENTICAL] => 280
    [T_IS_IDENTICAL] => 281
    [T_IS_NOT_EQUAL] => 282
    [T_IS_EQUAL] => 283
    [T_IS_GREATER_OR_EQUAL] => 284
    [T_IS_SMALLER_OR_EQUAL] => 285
    [T_SR] => 286
    [T_SL] => 287
    [T_INSTANCEOF] => 288
    [T_UNSET_CAST] => 289
    [T_BOOL_CAST] => 290
    [T_OBJECT_CAST] => 291
    [T_ARRAY_CAST] => 292
    [T_STRING_CAST] => 293
    [T_DOUBLE_CAST] => 294
    [T_INT_CAST] => 295
    [T_DEC] => 296
    [T_INC] => 297
    [T_CLONE] => 298
    [T_NEW] => 299
    [T_EXIT] => 300
    [T_IF] => 301
    [T_ELSEIF] => 302
    [T_ELSE] => 303
    [T_ENDIF] => 304
    [T_LNUMBER] => 305
    [T_DNUMBER] => 306
    [T_STRING] => 307
    [T_STRING_VARNAME] => 308
    [T_VARIABLE] => 309
    [T_NUM_STRING] => 310
    [T_INLINE_HTML] => 311
    [T_CHARACTER] => 312
    [T_BAD_CHARACTER] => 313
    [T_ENCAPSED_AND_WHITESPACE] => 314
    [T_CONSTANT_ENCAPSED_STRING] => 315
    [T_ECHO] => 316
    [T_DO] => 317
    [T_WHILE] => 318
    [T_ENDWHILE] => 319
    [T_FOR] => 320
    [T_ENDFOR] => 321
    [T_FOREACH] => 322
    [T_ENDFOREACH] => 323
    [T_DECLARE] => 324
    [T_ENDDECLARE] => 325
    [T_AS] => 326
    [T_SWITCH] => 327
    [T_ENDSWITCH] => 328
    [T_CASE] => 329
    [T_DEFAULT] => 330
    [T_BREAK] => 331
    [T_CONTINUE] => 332
    [T_GOTO] => 333
    [T_FUNCTION] => 334
    [T_CONST] => 335
    [T_RETURN] => 336
    [T_TRY] => 337
    [T_CATCH] => 338
    [T_THROW] => 339
    [T_USE] => 340
    [T_INSTEADOF] => 341
    [T_GLOBAL] => 342
    [T_PUBLIC] => 343
    [T_PROTECTED] => 344
    [T_PRIVATE] => 345
    [T_FINAL] => 346
    [T_ABSTRACT] => 347
    [T_STATIC] => 348
    [T_VAR] => 349
    [T_UNSET] => 350
    [T_ISSET] => 351
    [T_EMPTY] => 352
    [T_HALT_COMPILER] => 353
    [T_CLASS] => 354
    [T_TRAIT] => 355
    [T_INTERFACE] => 356
    [T_EXTENDS] => 357
    [T_IMPLEMENTS] => 358
    [T_OBJECT_OPERATOR] => 359
    [T_DOUBLE_ARROW] => 360
    [T_LIST] => 361
    [T_ARRAY] => 362
    [T_CALLABLE] => 363
    [T_CLASS_C] => 364
    [T_TRAIT_C] => 365
    [T_METHOD_C] => 366
    [T_FUNC_C] => 367
    [T_LINE] => 368
    [T_FILE] => 369
    [T_COMMENT] => 370
    [T_DOC_COMMENT] => 371
    [T_OPEN_TAG] => 372
    [T_OPEN_TAG_WITH_ECHO] => 373
    [T_CLOSE_TAG] => 374
    [T_WHITESPACE] => 375
    [T_START_HEREDOC] => 376
    [T_END_HEREDOC] => 377
    [T_DOLLAR_OPEN_CURLY_BRACES] => 378
    [T_CURLY_OPEN] => 379
    [T_PAAMAYIM_NEKUDOTAYIM] => 380
    [T_NAMESPACE] => 381
    [T_NS_C] => 382
    [T_DIR] => 383
    [T_NS_SEPARATOR] => 384
    [T_DOUBLE_COLON] => 380
*/