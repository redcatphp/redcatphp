<?php

namespace Surikat\Model\RedBeanPHP\BeanHelper;

use Surikat\Model\RedBeanPHP\BeanHelper as BeanHelper;
use Surikat\Model\RedBeanPHP\Facade as Facade;
use Surikat\Model\RedBeanPHP\OODBBean as OODBBean;
use Surikat\Model\RedBeanPHP\SimpleModelHelper as SimpleModelHelper;

/**
 * Bean Helper.
 * The Bean helper helps beans to access access the toolbox and
 * FUSE models. This Bean Helper makes use of the facade to obtain a
 * reference to the toolbox.
 *
 * @file    RedBeanPHP/BeanHelperFacade.php
 * @desc    Finds the toolbox for the bean.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleBeanHelper implements BeanHelper
{
	
	protected $database;
	function __construct($db){
		$this->database = $db;
	}
	
	/**
	 * @see BeanHelper::getToolbox
	 */
	public function getToolbox()
	{
		return $this->database->getToolBox();
	}

	/**
	 * @see BeanHelper::getModelForBean
	 */
	public function getModelForBean( OODBBean $bean )
	{
		$t = $bean->getMeta('type');
		$c = $this->database->getModelClass($t);
		$model = new $c($t,$this->database);
		$model->loadBean($bean);
		return $model;
	}

	/**
	 * @see BeanHelper::getExtractedToolbox
	 */
	public function getExtractedToolbox()
	{
		return $this->database->getExtractedToolbox();
	}

}
