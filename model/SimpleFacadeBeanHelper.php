<?php namespace surikat\model;
use surikat\model;
use surikat\model\RedBeanPHP\OODBBean;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as RedBeanPHP_SimpleFacadeBeanHelper;
class SimpleFacadeBeanHelper extends RedBeanPHP_SimpleFacadeBeanHelper{
	public function getModelForBean(OODBBean $bean){
		$c = model::getModelClass($bean->getMeta('type'));
		//foreach($c::getDefColumns('write'))
			//R::bindFunc('write', '$table.$col', '$func');
		$model = new $c();
		$model->loadBean($bean);
		return $model;
	}
}
