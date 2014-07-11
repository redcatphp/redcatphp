<?php namespace surikat\model;
use surikat\model\RedBeanPHP\OODBBean;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as RedBeanPHP_SimpleFacadeBeanHelper;
class SimpleFacadeBeanHelper extends RedBeanPHP_SimpleFacadeBeanHelper{
	public function getModelForBean(OODBBean $bean){
		$t = $bean->getMeta('type');
		$c = R::getModelClass($t);
		$model = new $c($t);
		$model->loadBean($bean);
		return $model;
	}
}