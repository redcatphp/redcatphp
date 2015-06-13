<?php
namespace RedBase\DataMapper;
interface Relation {
	public function getData($parentObj);
	public function overwrite($parentObj, &$data);
}