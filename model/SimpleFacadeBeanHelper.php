<?php namespace surikat\model;
use surikat\model\RedBeanPHP\OODBBean;
use surikat\model\RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as RedBeanPHP_SimpleFacadeBeanHelper;
class SimpleFacadeBeanHelper extends RedBeanPHP_SimpleFacadeBeanHelper{
	public function getModelForBean(OODBBean $bean){
		$prefix = defined('REDBEAN_MODEL_PREFIX')?constant('REDBEAN_MODEL_PREFIX'):'\\model\\Table_';
		$model = $bean->getMeta('type');
		$c = class_exists($c=$prefix.ucfirst($model))?$c:rtrim($prefix,'_');
		$model = new $c();
		$model->loadBean($bean);
		return $model;
	}
}
