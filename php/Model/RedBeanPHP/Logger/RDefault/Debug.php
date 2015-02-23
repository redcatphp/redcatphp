<?php
namespace Surikat\Model\RedBeanPHP\Logger\RDefault;

use Surikat\DependencyInjection\MutatorMagic;
use Surikat\Model\RedBeanPHP\Logger as Logger;
use Surikat\Model\RedBeanPHP\Logger\RDefault as RDefault;
use Surikat\Model\RedBeanPHP\RedException as RedException;
use Surikat\Model\RedBeanPHP\RedException\Security as Security;
/**
 * Debug logger.
 * A special logger for debugging purposes.
 *
 * @file    RedBean/Logger/RDefault/Debug.php
 * @desc    Debug Logger
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * Provides a debugging logging functions for RedBeanPHP.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Debug extends RDefault implements Logger
{
	use MutatorMagic;

	/**
	 * Writes a query for logging with all bindings / params filled
	 * in.
	 *
	 * @param string $newSql   the query
	 * @param array  $bindings the bindings to process (key-value pairs)
	 *
	 * @return string
	 */
	private function writeQuery( $newSql, $newBindings )
	{
		$newStr = $newSql;
		uksort( $newBindings, function($a, $b){
			return strlen($b)-strlen($a);
		});
		foreach( $newBindings as $slot => $value ) {
			if ( strpos( $slot, ':' ) === 0 ) {
				$newStr = str_replace( $slot, $this->fillInValue( $value ), $newStr );
			}
		}
		return $newStr;
	}

	/**
	 * Fills in a value of a binding and truncates the
	 * resulting string if necessary.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected function fillInValue( $value )
	{
		
		if ( is_null( $value ) ) $value = 'NULL';
		if(is_numeric( $value ))
			$value = str_replace(',','.',$value);
		elseif ( $value !== 'NULL')
			$value = "'".str_replace("'","\'",$value)."'";
		$value = htmlentities($value);
		return $value;
	}

	/**
	 * Dependending on the current mode of operation,
	 * this method will either log and output to STDIN or
	 * just log.
	 *
	 * @param string $str string to log or output and log
	 *
	 * @return void
	 */
	protected function output( $str )
	{
		$this->logs[] = $str;
		if ( !$this->mode )
			echo '<pre class="debug-model">'.$str.'</pre>';
	}

	/**
	 * Normalizes the slots in an SQL string.
	 * Replaces question mark slots with :slot1 :slot2 etc.
	 *
	 * @param string $sql sql to normalize
	 *
	 * @return string
	 */
	protected function normalizeSlots( $sql )
	{
		$i = 0;
		$newSql = $sql;
		while(strpos($newSql, '?') !== FALSE ){
			$pos   = strpos( $newSql, '?' );
			$slot  = ':slot'.$i;
			$begin = substr( $newSql, 0, $pos );
			$end   = substr( $newSql, $pos+1 );
			$newSql = $begin . $slot . $end;
			$i++;
		}
		return $newSql;
	}

	/**
	 * Normalizes the bindings.
	 * Replaces numeric binding keys with :slot1 :slot2 etc.
	 *
	 * @param array $bindings bindings to normalize
	 *
	 * @return array
	 */
	protected function normalizeBindings( $bindings )
	{
		$i = 0;
		$newBindings = [];
		foreach( $bindings as $key => $value ) {
			if ( is_numeric($key) ) {
				$newKey = ':slot'.$i;
				$newBindings[$newKey] = $value;
				$i++;
			} else {
				$newBindings[$key] = $value;
			}
		}
		return $newBindings;
	}

	/**
	 * Logger method.
	 *
	 * Takes a number of arguments tries to create
	 * a proper debug log based on the available data.
	 *
	 * @return void
	 */
	public function log()
	{
		if ( func_num_args() < 1 ) return;

		$sql = func_get_arg( 0 );

		if ( func_num_args() < 2) {
			$bindings = [];
		} else {
			$bindings = func_get_arg( 1 );
		}

		if ( !is_array( $bindings ) ) {
			return $this->output( $sql );
		}

		$newSql = $this->normalizeSlots( $sql );
		$newBindings = $this->normalizeBindings( $bindings );
		$newStr = $this->writeQuery( $newSql, $newBindings );
		$this->output( $newStr );
	}
	public function logOpen(){
		if(!headers_sent())
			header('Content-Type: text/html; charset=utf-8');
		echo '<div style="'.$this->Dev_Debug->debugWrapInlineCSS.'">';
		
	}
	public function logClose(){
		echo '</div>';
	}
}