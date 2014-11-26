<?php namespace Surikat\Control\security;
// +----------------------------------------------------------------------+
// | File based shared memory class for PHP5.                             |
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
// $Id: File.php,v 1.2 2005/01/09 22:39:54 cmanley Exp $
//



/**
 * @author    Craig Manley
 * @copyright Copyright © 2005, Craig Manley. All rights reserved.
 * @package   IPC_SharedMem
 * @version   $Revision: 1.2 $
 */


/**
 * @ignore Require interface class.
 */
// require_once(dirname(__FILE__) . '/../SharedMem.php');


/**
 * File based shared memory class.
 * Makes use of the regular file functions (fopen(), flock(), etc.) to use a file
 * for shared memory. This is great if you're using Linux or a similar operating
 * system that supports "tmpfs" and you have one of those available because it that
 * case this class will be using real RAM memory with regular file functions.
 *
 * This is how to configure tmpfs on a per user basis in Linux:
 * <pre>
 *  Get root to do this:
 *  mkdir /home/myname/tmpfs
 *  chown myname:mygroup /home/myname/tmpfs
 *  ... and place this in a script executed at boot time:
 *  mount -t tmpfs /mnt/tmpfs /home/myuser/tmpfs
 * </pre>
 *
 * @package  IPC_SharedMem
 * @see      http://www.php.net/manual/en/function.flock.php
 */
class IPC_SharedMem_File implements IPC_ISharedMem {

  // Private members
  private $file          = null;
  private $option_perms  = null;
  private $option_create = true;
  private $option_remove = false;
  private $handle        = null; // only set if in transaction mode.


  /**
   * Constructor.
   *
   * If the file with the name passed in the 1st parameter does not exist,
   * then it will be created automatically when needed.
   *
   * The following options can be set in the 2nd parameter:
   * <ul>
   *  <li>perms  - the octal shared memory permissions.</li>
   *  <li>create - boolean, create the shared memory if it does not exist, default true.</li>
   *  <li>remove - boolean, delete the shared memory when this object is destroyed, default false.</li>
   * </ul>
   *
   * @param string $file the name of the file to use as shared memory.
   * @param array $options associative array of options.
   * @return object
   */
  public function __construct($file, $options = null) {
    $this->file = $file;
    // Set options
    if (isset($options)) {
      if (isset($options['perms'])) {
        $this->option_perms = $options['perms'];
      }
      if (isset($options['create'])) {
        $this->option_create = $options['create'];
      }
      if (isset($options['remove'])) {
        $this->option_remove = $options['remove'];
      }
    }
    if (is_null($this->option_perms)) {
      $this->option_perms = 0666 & ~umask();
    }
  }


  /**
   * Destructor. Removes the shared memory file if the 'remove' option is true.
   */
  public function __destruct() {
    if (isset($this->handle)) {
      $this->transaction_finish();
    }
    if ($this->option_remove) {
      @unlink($this->file);
    }
  }


  /**
   * Opens the file and returns the file handle on success, else it throws an exception.
   *
   * @return resource
   */
  protected function _open() {
    $mode = $this->option_create ? 'a+' : 'r+';
	//if($this->option_create&&!is_dir(dirname($this->file))) mkdir(dirname($this->file),0755,true);
	if($this->option_create&&!is_dir(dirname($this->file))){
		$old_mask = umask();		
		mkdir(dirname($this->file),0777,true);
	}
    // r+ : Open for reading and writing; place the file pointer at the beginning of the file.
    // a+ : Open for reading and writing; place the file pointer at the end of the file. If the file does not exist, attempt to create it.
    $file = $this->file;
    $mask = 0666 & ~$this->option_perms;
    $old_mask = umask($mask);
    $result = fopen($this->file, $mode);
    umask($old_mask);
    if (!$result) {
      throw new Exception("fopen(\"$file\", \"$mode\") failed.");
    }
    return $result;
  }


  /**
   * Opens the file and locks it exclusively so that you can safely read
   * and write to it in one blocking transaction.
   *
   * @return boolean
   */
  public function transaction_start() {
    if (!isset($this->handle)) {
      $this->handle = $this->_open();
      return flock($this->handle, LOCK_EX); // exclusive lock
    }
    return false;
  }


  /**
   * Releases the file lock and closes the file.
   *
   * @return boolean
   */
  public function transaction_finish() {
    if (isset($this->handle)) {
      $h = $this->handle;
      $this->handle = null;
      flock($h, LOCK_UN);
      return fclose($h);
    }
    return false;
  }


  /**
   * Returns all data from the shared memory.
   * If this is called while not in transaction mode, then a
   * read only transaction is automatically used within this call.
   *
   * @return string
   */
  public function fetch() {
    $h = null;
    if (isset($this->handle)) {
      $h = $this->handle;
    }
    else {
      $h = $this->_open();
      flock($h, LOCK_SH); // shared lock for reading
    }
    fseek($h,0);
    $result = '';
    while (!feof($h)) {
      $result .= fread($h, 8192);
    }
    if (is_null($this->handle)) {
      flock($h, LOCK_UN);
      fclose($h);
    }
    return $result;
  }


  /**
   * Writes the given string to the shared memory.
   * If this is called while not in transaction mode, then an
   * exclusive transaction is automatically used within this call.
   * Returns the number of bytes written.
   *
   * @param string $value
   * @return integer
   */
  public function store($value) {
    $h = null;
    if (isset($this->handle)) {
      $h = $this->handle;
    }
    else {
      $h = $this->_open();
      flock($h, LOCK_EX); // exclusive lock for writing
    }
    if ($this->option_create) { // mode == 'a+'
      ftruncate($h,0);
    }
    $result = fwrite($h, $value);
    if (is_null($this->handle)) {
      flock($h, LOCK_UN);
      fclose($h);
    }
  }

}
