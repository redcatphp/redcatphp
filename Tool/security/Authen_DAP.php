<?php namespace Surikat\Tool\security;
// +----------------------------------------------------------------------+
// | Dictionary Attack Protection class for PHP5.                         |
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
// $Id: DAP.php,v 1.1 2005/01/09 22:44:19 cmanley Exp $
//



/**
 * @author    Craig Manley
 * @copyright Copyright © 2004, Craig Manley. All rights reserved.
 * @package   com.craigmanley.classes.authen.DAP
 * @version   $Revision: 1.1 $
 */




/**
 * Offers protection against dictionary attacks.
 *
 * @package  com.craigmanley.classes.authen.DAP
 */
class Authen_DAP {

  // Private members
  private $shm          = null; // Object implementing IPC_ISharedMem interface.
  private $max_attempts = null;
  private $period       = null;


  /**
   * Constructor.
   *
   * @param object  $shm shared memory object that implements the IPC_ISharedMem interface.
   * @param integer $max_attempts maximum failed (login) attempts after which to block the identity, default 3.
   * @param integer $period time in seconds to block an identity for after too many failed access attempts, default 180.
   */
  public function __construct($shm, $max_attempts = 3, $period = 180) {
    if (!(isset($shm) && ($shm instanceof IPC_ISharedMem))) {
      throw Exception('You must pass a shared memory management object that implements the IPC_ISharedMem interface as the 1st parameter!');
    }
    $this->shm = $shm;
    $this->max_attempts($max_attempts);
    $this->period($period);
  }


  /**
   * Checks if the parameter is a positive integer (representation).
   *
   * @param mixed $value
   * @return boolean
   */
  protected function _is_pos_int($value) {
    return (is_int($value) || (is_string($value) && preg_match('/^\+?\d+$/', $value))) && ($value > 0);
  }


  /**
   * Gets or sets the maximum number of failed access attempts after which an identity
   * will be temporarily blocked. The default value is 3.
   *
   * @param value $name Optional new value. Must be an integer >= 1.
   * @return integer
   */
  public function max_attempts() {
    if (func_num_args()) {
      $value = func_get_arg(0);
      if (!(isset($value) && $this->_is_pos_int($value))) {
        throw Exception("Method parameter is not a positive integer!");
      }
      $this->max_attempts = intval($value);;
    }
    return $this->max_attempts;
  }


  /**
   * Gets or sets the period (in seconds) that identities are blocked for after
   * too many failed access attempts. The default value is 180.
   *
   * @param value $name Optional new value. Must be an integer >= 1.
   * @return integer
   */
  public function period() {
    if (func_num_args()) {
      $value = func_get_arg(0);
      if (!(isset($value) && $this->_is_pos_int($value))) {
        throw Exception("Parameter is not a positive integer!");
      }
      $this->period = intval($value);;
    }
    return $this->period;
  }


  /**
   * Checks to see if the identity's access has been blocked using the given record structure.
   * Returns the seconds the identity is (still) blocked for (0 means not blocked).
   *
   * @param scalar $identity Anything used to identify someone such as an IP, alias, etc.
   * @param array $records array of [$identity, $last_attempt] arrays.
   * @return integer
   */
  private function _blocked($identity, &$records) {
    $attempts = 0;
    $max_attempts = $this->max_attempts();
    $last_attempt = null;
    // Search for the last $max_attempts records if any.
    for ($i = count($records) - 1; $i >= 0; $i--) {
      if ($records[$i][0] == $identity) {
        $attempts++;
        if (is_null($last_attempt)) {
          $last_attempt = $records[$i][1];
        }
        if ($attempts >= $max_attempts) {
          break;
        }
      }
    }
    $block_for = null;
    if ($attempts >= $max_attempts) {
      $block_for = $last_attempt + $this->period() - time();
      if ($block_for < 0) { // could happen in theory if code executes slowly.
        $block_for = 0;
      }
    }
    else {
      $block_for = 0;
    }
    return $block_for;
  }


  /**
   * Deletes expired records from the record structure passed as parameter.
   * Returns the seconds the identity is (still) blocked for (0 means not blocked).
   * Returns the amount of records deleted.
   *
   * @param array $records array of [identity, time] arrays.
   * @return integer
   */
  private function _delete_expired_records(&$records) {
    $result = 0;
    $expire_before = time() - $this->period();
    while (count($records)) {
      if ($records[0][1] <= $expire_before) {
        $result++;
        array_shift($records);
      }
      else {
        break;
      }
    }
    return $result;
  }


  /**
   * Loads the recorded failed access events structure from shared memory,
   * cleans out all expired records if any, saves the structure back into
   * shared memory (if changed), and returns the new record structure.
   *
   * @return array
   */
  private function &_records() {
    $records = null;
    $shm = $this->shm;
    $shm->transaction_start();
    try {
      $s = $shm->fetch();
      if (isset($s) && strlen($s)) {
      	$records = unserialize($s);
        if ($this->_delete_expired_records($records)) {
          $s = serialize((array)$records);
          $shm->store($s);
        }
      }
      $shm->transaction_finish();
    }
    catch(Exception $e) {
      $shm->transaction_finish();
      throw $e;
    }
    if (!isset($records)) {
      $records = [];
    }
    return $records;
  }


  /**
   * Records a failed access attempt and returns the number of seconds the identity
   * has been blocked for (0 meaning not blocked).
   *
   * @param string $identity Anything used to identify somebody such as an IP address, login alias, session key, etc.
   * @return integer
   */
  public function record_failed_attempt($identity) {
    $records = null;
    $shm = $this->shm;
    $shm->transaction_start();
    try {
      $s = $shm->fetch();
      if (isset($s) && strlen($s)) {
      	$records = unserialize($s);
        $this->_delete_expired_records($records);
      }
      else {
        $records = [];
      }
      array_push($records, [$identity, time()]);
	  $s = serialize((array)$records);
      $shm->store($s);
      $shm->transaction_finish();
    }
    catch(Exception $e) {
      $shm->transaction_finish();
      throw $e;
    }
    return $this->_blocked($identity,$records);
  }


  /**
   * Dumps the records currently stored in shared memory to stdout. Only useful for debugging.
   */
  public function dump_records() {
    $records = $this->_records();
    print_r($records);
  }


  /**
   * Clears all interal records of failed access attempts for the given identity.
   *
   * @param string $identity Anything used to identify somebody such as an IP address, login alias, session key, etc.
  */
  public function clear($identity) {
    $shm = $this->shm;
    $shm->transaction_start();
    try {
      $s = $shm->fetch();
      if (isset($s) && strlen($s)) {
        $records = unserialize($s);
        $changed = false;
        if ($this->_delete_expired_records($result)) {
          $changed = true;
        }
        $i = 0;
        while (count($records) && ($i < count($records))) {
          if ($records[$i][0] == $identity) {
            array_splice($records,$i,1);
            $changed = true;
          }
          else {
            $i++;
          }
        }
        if ($changed) {
          $s = serialize((array)$result);
          $shm->store($s);
        }
      }
      $shm->transaction_finish();
    }
    catch(Exception $e) {
      $shm->transaction_finish();
      throw $e;
    }
  }


  /**
   * Checks to see if the indentity's access has been blocked.
   * Returns the number of seconds the identity has been blocked for (0 means not blocked).
   *
   * @param string $identity Anything used to identify somebody such as an IP address, login alias, session key, etc.
   * @return integer
   */
  public function blocked($identity) {
    return $this->_blocked($identity, $this->_records());
  }

}
