<?php

namespace Surikat\Model\RedBeanPHP\Repository;

use Surikat\Model\RedBeanPHP\OODBBean as OODBBean;
use Surikat\Model\RedBeanPHP\Observable as Observable;
use Surikat\Model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use Surikat\Model\RedBeanPHP\BeanHelper\FacadeBeanHelper as FacadeBeanHelper;
use Surikat\Model\RedBeanPHP\QueryWriter as QueryWriter;
use Surikat\Model\RedBeanPHP\RedException as RedException;
use Surikat\Model\RedBeanPHP\RedException\Security as Security;
use Surikat\Model\RedBeanPHP\SimpleModel as SimpleModel;
use Surikat\Model\RedBeanPHP\BeanHelper as BeanHelper;
use Surikat\Model\RedBeanPHP\RedException\SQL as SQLException;
use Surikat\Model\RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use Surikat\Model\RedBeanPHP\Repository as Repository;

/**
 * Frozen Repository
 *
 * @file    RedBeanPHP/Repository/Frozen.php
 * @desc    RedBeanPHP Object Database
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * OODB manages two repositories, a fluid one that
 * adjust the database schema on-the-fly to accomodate for
 * new bean types (tables) and new properties (columns) and
 * a frozen one for use in a production environment. OODB
 * allows you to swap the repository instances using the freeze()
 * method.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Frozen extends Repository
{
	/**
	 * Handles\Exceptions. Suppresses exceptions caused by missing structures.
	 *
	 * @param \Exception $exception exception
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function handleException( \Exception $exception )
	{
		throw $exception;
	}

	/**
	 * Stores a cleaned bean; i.e. only scalar values. This is the core of the store()
	 * method. When all lists and embedded beans (parent objects) have been processed and
	 * removed from the original bean the bean is passed to this method to be stored
	 * in the database.
	 *
	 * @param OODBBean $bean the clean bean
	 *
	 * @return void
	 */
	protected function storeBean( OODBBean $bean )
	{
		if ( $bean->getMeta( 'changed' ) ) {

			list( $properties, $table ) = $bean->getPropertiesAndType();
			$id = $properties['id'];
			unset($properties['id']);
			$updateValues = [];
			$k1 = 'property';
			$k2 = 'value';
			foreach( $properties as $key => $value ) {
				$updateValues[] = [ $k1 => $key, $k2 => $value ];
			}
			$bean->id = $this->writer->updateRecord( $table, $updateValues, $id );
			$bean->setMeta( 'changed', FALSE );

		}
		$bean->setMeta( 'tainted', FALSE );
	}

	/**
	 * Part of the store() functionality.
	 * Handles all new additions after the bean has been saved.
	 * Stores addition bean in own-list, extracts the id and
	 * adds a foreign key. Also adds a constraint in case the type is
	 * in the dependent list.
	 *
	 * @param OODBBean $bean         bean
	 * @param array            $ownAdditions list of addition beans in own-list
	 *
	 * @return void
	 *
	 * @throws Security
	 */
	protected function processAdditions( $bean, $ownAdditions )
	{
		$beanType = $bean->getMeta( 'type' );

		$cachedIndex = [];
		foreach ( $ownAdditions as $addition ) {
			if ( $addition instanceof SimpleModel )
				$addition = $addition->unbox();
			if ( $addition instanceof OODBBean ) {

				$myFieldLink = $beanType . '_id';
				$alias = $bean->getMeta( 'sys.alias.' . $addition->getMeta( 'type' ) );
				if ( $alias ) $myFieldLink = $alias . '_id';

				$addition->$myFieldLink = $bean->id;
				$addition->setMeta( 'cast.' . $myFieldLink, 'id' );
				$this->store( $addition );

			} else {
				throw new RedException( 'Array may only contain OODBBeans' );
			}
		}
	}

	/**
	 * Dispenses a new bean (a OODBBean Bean Object)
	 * of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 *
	 * @param string  $type              type of bean you want to dispense
	 * @param string  $number            number of beans you would like to get
	 * @param boolean $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return OODBBean
	 */
	public function dispense( $type, $number = 1, $alwaysReturnArray = FALSE )
	{
		$beans = [];
		for ( $i = 0; $i < $number; $i++ ) {
			$OODBBEAN = defined( 'REDBEAN_OODBBEAN_CLASS' ) ? REDBEAN_OODBBEAN_CLASS : 'Surikat\Model\RedBeanPHP\OODBBean';
			$bean = new $OODBBEAN($this->writer->getDatabase());
			$bean->initializeForDispense( $type, $this->oodb->getBeanHelper() );
			$this->oodb->signal( 'dispense', $bean );
			$beans[] = $bean;
		}

		return ( count( $beans ) === 1 && !$alwaysReturnArray ) ? array_pop( $beans ) : $beans;
	}

	/**
	 * Loads a bean from the object database.
	 * It searches for a OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 *
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 *
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 *
	 * @throws SQL
	 *
	 * @return OODBBean
	 *
	 */
	public function load( $type, $id )
	{
		$bean = $this->dispense( $type );
		if ( isset( $this->stash[$this->nesting][$id] ) ) {
			$row = $this->stash[$this->nesting][$id];
		} else {
			try {
				$rows = $this->writer->queryRecord( $type, [ 'id' => [ $id ] ] );
			} catch ( SQLException $exception ) {
				if ( $this->writer->sqlStateIn( $exception->getSQLState(),
					[
						QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
						QueryWriter::C_SQLSTATE_NO_SUCH_TABLE ]
				)
				) {
					throw $exception; //only throw if frozen

				}
			}
			if ( empty( $rows ) ) {
				return $bean;
			}
			$row = array_pop( $rows );
		}
		$bean->importRow( $row );
		$this->nesting++;
		$this->oodb->signal( 'open', $bean );
		$this->nesting--;

		return $bean->setMeta( 'tainted', FALSE );
	}
}