<?php
namespace RedBase;
interface Observer{
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
}