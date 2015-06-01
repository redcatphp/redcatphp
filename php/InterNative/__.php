<?php
if(!function_exists('n__')){
	function n__($singular,$plural,$number){
		return InterNative\Translator::getInstance()->ngettext($singular, $plural, $number);
	}
}
if(!function_exists('__')){
	function __($msgid){
		return InterNative\Translator::getInstance()->gettext($msgid);
	}
}