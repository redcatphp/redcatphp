<?php
namespace RedBase\Maphper;
interface Relation {
	public function getData($parentObj);
	public function overwrite($parentObj, &$data);
}