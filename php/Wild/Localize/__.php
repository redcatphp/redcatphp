<?php
if(!function_exists('n__')){
	function n__($singular,$plural,$number){
		return Wild\Localize\Translator::getInstance()->ngettext($singular, $plural, $number);
	}
}
if(!function_exists('__')){
	function __($msgid){
		return Wild\Localize\Translator::getInstance()->gettext($msgid);
	}
}