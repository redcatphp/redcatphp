<?php namespace surikat\control\security;
// +----------------------------------------------------------------------+
// | Shared memory interface for PHP5.                                    |
// | Copyright (C) 2005 Craig Manley                                      |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU Lesser General Public License as       |
// | published by the Free Software Foundation; either version 2.1 of the |
// | License, or (at your option) any later version.                      |
// |                                                                      |
// | This library is distributed in the hope that it will be useful, but  |
// | WITHOUT ANY WARRANTY; without even the implied warranty of           |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU     |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307  |
// | USA                                                                  |
// |                                                                      |
// | LGPL license URL: http://opensource.org/licenses/lgpl-license.php    |
// +----------------------------------------------------------------------+
// | Author: Craig Manley                                                 |
// +----------------------------------------------------------------------+
//
// $Id: ISharedMem.php,v 1.1 2005/01/09 19:05:32 cmanley Exp $
//



/**
 * @author    Craig Manley
 * @copyright Copyright © 2005, Craig Manley. All rights reserved.
 * @package   IPC_SharedMem
 * @version   $Revision: 1.1 $
 */



/**
 * Shared memory interface.
 *
 * @package  IPC_SharedMem
 */
interface IPC_ISharedMem {

  /**
   * This method must open the shared memory and lock it exclusively so that
   * you can safely read and write to it in one blocking transaction. Calling
   * this method multiple times without finishing a transaction should simply
   * return false.
   *
   * @return boolean
   */
  public function transaction_start();


  /**
   * This method must releases the shared memory lock and close it.
   * Calling this method multiple times without starting a transaction
   * should simply return false.
   *
   * @return boolean
   */
  public function transaction_finish();


  /**
   * This method must fetch a string from shared memory as a single atomic operation.
   * If this is called while not in transaction mode, then a read only transaction is
   * automatically used within this call.
   *
   * @return string
   */
  public function fetch();


  /**
   * This method must save a string to shared memory as a single atomic operation.
   * If this is called while not in transaction mode, then an
   * exclusive transaction is automatically used within this call.
   *
   * @param string $value
   * @return integer
   */
  public function store($value);

}
