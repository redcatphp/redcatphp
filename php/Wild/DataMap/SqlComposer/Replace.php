<?php
namespace Wild\DataMap\SqlComposer;
class Replace extends Base {
	protected $select;
	function __construct($table = null) {
		if (isset($table)) $this->into($table);
	}
	function replace_into($table) {
		return $this->into($table);
	}
	function into($table) {
		$this->add_table($table);
		return $this;
	}
	function columns($column) {
		$this->columns = array_merge($this->columns, (array)$column);
		return $this;
	}
	function values( array $values) {
		if (isset($this->select)) throw new Exception("Cannot use 'REPLACE INTO ... VALUES' when a SELECT is already set!");

		return $this->_add_params('values', $values);
	}
	function select($select = null,  array $params = null) {
		if (isset($this->params['values'])) throw new Exception("Cannot use 'REPLACE INTO ... SELECT' when values are already set!");

		if (!isset($this->select)) {
			$this->select = new Select();
		}

		return $this->select->select($select, $params);
	}
	function render() {
		$table = $this->tables[0];

		$columns = $this->_get_columns();
		$columns = empty($columns) ? "" : "(" . implode(", ", $columns) . ")";

		if (isset($this->select)) {
			$values = "\n" . $this->select->render();
		} else {
			$placeholders = "(" . implode(", ", array_fill(0, $this->_num_columns(), "?")) . ")";

			$num_values = count($this->params['values']);

			$values = "\nVALUES " . implode(", ", array_fill(0, $num_values, $placeholders));
		}

		return "REPLACE INTO {$table} {$columns} {$values}";
	}
	function getParams() {

		if (isset($this->select)) {

			$params = $this->select->getParams();

		} else {

			$params = [ ];
			$columns = $this->_get_columns();
			$num_cols = $this->_num_columns();
			foreach ($this->params["values"] as $values) {
				if (self::is_assoc($values)) {
					foreach ($columns as $col) $params[] = $values[$col];
				} else {
					$params = array_merge($params, array_slice($values, 0, $num_cols));
				}
			}
		}
		return $params;
	}
	protected function _get_columns() {
		if (!empty($this->columns)) {
			return $this->columns;
		}
		elseif (self::is_assoc($this->params['values'][0])) {
			return array_keys($this->params['values'][0]);
		}
		else {
			return [];
		}
	}
	protected function _num_columns() {
		if (!empty($this->columns)) {
			return count($this->columns);
		} else {
			return count($this->params['values'][0]);
		}
	}

}