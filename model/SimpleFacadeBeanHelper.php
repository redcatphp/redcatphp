<?php namespace surikat\model;
use surikat\model\RedBeanPHP\OODBBean;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as RedBeanPHP_SimpleFacadeBeanHelper;
class SimpleFacadeBeanHelper extends RedBeanPHP_SimpleFacadeBeanHelper{
	public function getModelForBean(OODBBean $bean){
		$c = R::getModelClass($bean->getMeta('type'));
		$model = new $c();
		$model->loadBean($bean);
		return $model;
	}
}
