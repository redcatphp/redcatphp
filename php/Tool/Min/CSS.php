<?php namespace Surikat\Tool\Min; 
abstract class CSS{
	static function minify($str){
		return str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    ',"\ r \ n", "\ r", "\ n", "\ t"],'',preg_replace( '! / \ *[^*]* \ *+([^/][^*]* \ *+)*/!','',preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!','',$str)));
	}
}
