<?php namespace Surikat\Component\Model\SQLComposer;
use Surikat\Component\Model\SQLComposer;
/**
 * SQLComposerReplace
 *
 * A REPLACE query
 *
 * @package SQLComposer
 * @author Shane Smith
 */
class Replace extends Base {

	/**
	 * To create an REPLACE INTO ... SELECT ...
	 *
	 * @var SQLComposerSelect
	 */
	protected $select;

	/*******************
	 **  CONSTRUCTOR  **
	 *******************/

	/**
	 * Constructor.
	 *
	 * @param string|array $table
	 */
	public function __construct($table = null) {
		if (isset($table)) $this->into($table);
	}

	/**
	 * REPLACE INTO
	 *
	 * @param string|array $table
	 * @return SQLComposerReplace
	 */
	public function replace_into($table) {
		return $this->into($table);
	}

	/**
	 * REPLACE INTO
	 *
	 * @param string|array $table
	 * @return SQLComposerReplace
	 */
	public function into($table) {
		$this->add_table($table);
		return $this;
	}

	/**
	 * Set the columns for REPLACE INTO table (col1, col2, ...)
	 *
	 * If no columns are specified by rendering time and the first set of values
	 * is an associative array, the array's keys will become the column names.
	 *
	 * @param string|array $column
	 * @return SQLComposerReplace
	 */
	public function columns($column) {
		$this->columns = array_merge($this->columns, (array)$column);
		return $this;
	}

	/**
	 * Provide a set of values to be replaced.
	 *
	 * If no columns are specified by rendering time and the first set of values
	 * is an associative array, the array's keys will become the column names.
	 *
	 * ex:
	 *  SQLComposer::replace_into('table')->values(array( 'id' => '25', 'name' => 'joe', 'fav_color' => 'green' ));
	 *
	 * will result in
	 *
	 *  REPLACE INTO table (id, name, fav_color) VALUES (25, 'joe', 'green')
	 *
	 * @param array $values
	 * @return SQLComposerReplace
	 */
	public function values( array $values) {
		if (isset($this->select)) throw new SQLComposerException("Cannot use 'REPLACE INTO ... VALUES' when a SELECT is already set!");

		return $this->_add_params('values', $values);
	}

	/**
	 * Return a SQLComposerSelect object to be used in a query of the type REPLACE INTO ... SELECT ...
	 *
	 * @param string|array $select
	 * @param array $params
	 * @return SQLComposerSelect
	 */
	public function select($select = null,  array $params = null) {
		if (isset($this->params['values'])) throw new SQLComposerException("Cannot use 'REPLACE INTO ... SELECT' when values are already set!");

		if (!isset($this->select)) {
			$this->select = SQLComposer::select();
		}

		return $this->select->select($select, $params);
	}


	/*****************
	 **  RENDERING  **
	 *****************/

	/**
	 * Render the REPLACE query
	 *
	 * @return string
	 */
	public function render() {
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

	/**
	 * Get the parameters array
	 *
	 * @return array
	 */
	public function getParams() {

		if (isset($this->select)) {

			$params = $this->select->getParams();

		} else {

			$params = [ ];
			$columns = $this->_get_columns();
			$num_cols = $this->_num_columns();
			foreach ($this->params["values"] as $values) {
				if (SQLComposer::is_assoc($values)) {
					foreach ($columns as $col) $params[] = $values[$col];
				} else {
					$params = array_merge($params, array_slice($values, 0, $num_cols));
				}
			}
		}
		return $params;
	}

	/**
	 * Get the currently set columns,
	 * or, if none set, the keys of the first values array if it is associative
	 *
	 * @return array
	 */
	protected function _get_columns() {
		if (!empty($this->columns)) {
			return $this->columns;
		}
		elseif (SQLComposer::is_assoc($this->params['values'][0])) {
			return array_keys($this->params['values'][0]);
		}
		else {
			return [];
		}
	}

	/**
	 * Get the number of defined columns,
	 * or, if none defined, the number of the first values array
	 *
	 * @return int
	 */
	protected function _num_columns() {
		if (!empty($this->columns)) {
			return count($this->columns);
		} else {
			return count($this->params['values'][0]);
		}
	}

}