<?php
namespace Wild\DataMap;
interface Observer{
	function beforeRecursive();
	function beforePut();
	function beforeCreate();
	function beforeRead();
	function beforeUpdate();
	function beforeDelete();
	function afterPut();
	function afterCreate();
	function afterRead();
	function afterUpdate();
	function afterDelete();
	function afterRecursive();
}