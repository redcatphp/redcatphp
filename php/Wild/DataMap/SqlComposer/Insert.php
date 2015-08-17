<?php
namespace Wild\DataMap\SqlComposer;
class Insert extends Base {
	protected $ignore = false;
	protected $select;
	protected $on_duplicate = [];
	function __construct($table = null) {
		if(isset($table))
			$this->into($table);
	}
	function insert_into($table) {
		return $this->into($table);
	}
	function into($table) {
		$this->add_table($table);
		return $this;
	}
	function ignore($ignore = true) {
		$this->ignore = $ignore;
		return $this;
	}
	function columns($column) {
		$this->columns = array_merge($this->columns, (array)$column);
		return $this;
	}
	function values( array $values) {
		if(isset($this->select))
			throw new Exception("Cannot use 'INSERT INTO ... VALUES' when a SELECT is already set!");
		return $this->_add_params('values', $values);
	}
	function select($select = null,  array $params = null) {
		if(isset($this->params['values']))
			throw new Exception("Cannot use 'INSERT INTO ... SELECT' when values are already set!");
		if (!isset($this->select)) 
			$this->select = new Select();
		if (isset($select))
			$this->select->select($select, $params);
		return $this->select;
	}
	function onDuplicate($update,  array $params = null) {
		$this->on_duplicate = array_merge($this->on_duplicate, (array)$update);
		$this->_add_params('on_duplicate', $params);
		return $this;
	}
	function render() {
		$table = $this->tables[0];
		$ignore = $this->ignore ? "IGNORE" : "";
		$columns = $this->_get_columns();
		$columns = empty($columns) ? "" : "(" . implode(", ", $columns) . ")";
		if(isset($this->select)){
			$values = "\n" . $this->select->render();
		}
		else{
			$placeholders = "(" . implode(", ", array_fill(0, $this->_num_columns(), "?")) . ")";
			$num_values = count($this->params['values']);
			$values = "\nVALUES " . implode(", ", array_fill(0, $num_values, $placeholders));
		}
		$on_duplicate =	(empty($this->on_duplicate)) ? "" : "\nON DUPLICATE KEY UPDATE " . implode(", ", $this->on_duplicate);
		return "INSERT {$ignore} INTO {$table} {$columns} {$values} {$on_duplicate}";
	}
	function getParams() {
		if (isset($this->select)) {
			$params = $this->select->getParams();
		}
		else{
			$params = [ ];
			$columns = $this->_get_columns();
			$num_cols = $this->_num_columns();
			foreach ($this->params["values"] as $values) {
				if (self::is_assoc($values)) {
					foreach ($columns as $col)
						$params[] = $values[$col];
				}
				else{
					$params = array_merge($params, array_slice($values, 0, $num_cols));
				}
			}

		}
		return array_merge($params, (array)$this->params['on_duplicate']);
	}
	protected function _get_columns() {
		if (!empty($this->columns)) {
			return $this->columns;
		}
		elseif (self::is_assoc($this->params['values'][0])) {
			return array_keys($this->params['values'][0]);
		}
		else{
			return [];
		}
	}
	protected function _num_columns() {
		if(!empty($this->columns)){
			return count($this->columns);
		}
		else{
			return count($this->params['values'][0]);
		}
	}
	function __clone(){
		if(isset($this->select))
			$this->select = clone $this->select;
	}
}