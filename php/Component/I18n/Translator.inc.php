<?php
use Surikat\Component\I18n\Translator;
function __($msgid,$lang=null,$domain=null){
	return Translator::__($msgid,$lang,$domain);
}
function n__($singular,$plural,$number,$lang=null,$domain=null){
	return Translator::n__($singular, $plural, $number,$lang,$domain);
}