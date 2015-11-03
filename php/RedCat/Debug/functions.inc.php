<?php
function dbug(){
	return call_user_func_array(['RedCat\Debug\Vars','debug'],func_get_args());
}
function debug(){
	return call_user_func_array(['RedCat\Debug\Vars','debug_html'],func_get_args());
}
function dbugs(){
	return call_user_func_array(['RedCat\Debug\Vars','dbugs'],func_get_args());
}
function debugs(){
	return call_user_func_array(['RedCat\Debug\Vars','debugs'],func_get_args());
}