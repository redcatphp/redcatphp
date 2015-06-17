<?php
if(!function_exists('n__')){
	function n__($singular,$plural,$number){
		return InterEthnic\Translator::getInstance()->ngettext($singular, $plural, $number);
	}
}
if(!function_exists('__')){
	function __($msgid){
		return InterEthnic\Translator::getInstance()->gettext($msgid);
	}
}