<?php
namespace RedBase\DataSource\Relational;
class Logger {
	protected $echo;
	protected $keep;
	protected $logs = [];
	function __construct($echo=null,$keep=null){
		$this->setEcho($echo);
		$this->setKeep($keep);
	}
	function setEcho($b=true){
		$this->echo = (bool)$b;
	}
	function setKeep($b=true){
		$this->keep = (bool)$b;
	}
	function getLogs(){
		return $this->logs;
	}
	function clear(){
		$this->logs = [];
	}
	private function writeQuery( $newSql, $newBindings ){
		uksort( $newBindings, function( $a, $b ) {
			return ( strlen( $b ) - strlen( $a ) );
		} );
		$newStr = $newSql;
		foreach( $newBindings as $slot => $value ) {
			if ( strpos( $slot, ':' ) === 0 ) {
				$newStr = str_replace( $slot, $this->fillInValue( $value ), $newStr );
			}
		}
		return $newStr;
	}
	protected function fillInValue( $value ){
		if ( is_null( $value ) ) $value = 'NULL';
		if(is_numeric( $value ))
			$value = str_replace(',','.',$value);
		elseif ( $value !== 'NULL')
			$value = "'".str_replace("'","\'",$value)."'";
		return $value;
	}
	protected function output( $str ){
		if($this->keep)
			$this->logs[] = $str;
		if($this->echo)
			echo '<pre class="debug-model">',htmlentities($str),'</pre><br />';
	}
	protected function normalizeSlots( $sql ){
		$i = 0;
		$newSql = $sql;
		while($i < 20 && strpos($newSql, '?') !== FALSE ){
			$pos   = strpos( $newSql, '?' );
			$slot  = ':slot'.$i;
			$begin = substr( $newSql, 0, $pos );
			$end   = substr( $newSql, $pos+1 );
			$newSql = $begin . $slot . $end;
			$i++;
		}
		return $newSql;
	}
	protected function normalizeBindings( $bindings ){
		$i = 0;
		$newBindings = array();
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
	function log(){
		if ( func_num_args() < 1 || !($this->keep||$this->echo))
			return;
		$sql = func_get_arg( 0 );
		if ( func_num_args() < 2)
			$bindings = array();
		else
			$bindings = func_get_arg( 1 );
		if ( !is_array( $bindings ) ) 
			return $this->output( $sql );
		$newSql = $this->normalizeSlots( $sql );
		$newBindings = $this->normalizeBindings( $bindings );
		$newStr = $this->writeQuery( $newSql, $newBindings );
		$this->output( $newStr );
	}
}