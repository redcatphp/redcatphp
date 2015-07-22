<?php
namespace RedBase;
interface Observer{
	function beforeCreate();
	function beforeRead();
	function beforeUpdate();
	function beforeDelete();
	function afterCreate();
	function afterRead();
	function afterUpdate();
	function afterDelete();
}