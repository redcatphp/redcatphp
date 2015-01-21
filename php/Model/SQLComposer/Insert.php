<?php namespace Surikat\Model\SQLComposer;
use Surikat\Model\SQLComposer;
/**
 * SQLComposerInsert
 *
 * An INSERT query
 *
 * @package SQLComposer
 * @author Shane Smith
 */
class Insert extends Base {

	/**
	 * IGNORE
	 *
	 * @var bool
	 */
	protected $ignore = false;

	/**
	 * To create an INSERT INTO ... SELECT ...
	 *
	 * @var SQLComposerSelect
	 */
	protected $select;

	/**
	 * ON DUPLICATE KEY UPDATE
	 *
	 * @var array
	 */
	protected $on_duplicate = [ ];


	/*******************
	 **  CONSTRUCTOR  **
	 *******************/

	/**
	 * Constructor.
	 *
	 * @param string $table
	 */
	public function __construct($table = null) {
		if (isset($table)) $this->into($table);
	}


	/***************
	 **  METHODS  **
	 ***************/

	/**
	 * INSERT INTO
	 *
	 * @param string $table
	 * @return SQLComposerInsert
	 */
	public function insert_into($table) {
		return $this->into($table);
	}

	/**
	 * INSERT INTO
	 *
	 * @param string $table
	 * @return SQLComposerInsert
	 */
	public function into($table) {
		$this->add_table($table);
		return $this;
	}

	/**
	 * IGNORE
	 *
	 * @param bool $ignore
	 * @return SQLComposerInsert
	 */
	public function ignore($ignore = true) {
		$this->ignore = $ignore;
		return $this;
	}

	/**
	 * Set the columns for INSERT INTO table (col1, col2, ...)
	 *
	 * If no columns are specified by rendering time and the first set of values
	 * is an associative array, the array's keys will become the column names.
	 *
	 * @param string|array $column
	 * @return SQLComposerInsert
	 */
	public function columns($column) {
		$this->columns = array_merge($this->columns, (array)$column);
		return $this;
	}

	/**
	 * Provide a set of values to be inserted.
	 *
	 * If no columns are specified by rendering time and the first set of values
	 * is an associative array, the array's keys will become the column names.
	 *
	 * ex:
	 *  SQLComposer::insert_into('table')->values(array( 'id' => '25', 'name' => 'joe', 'fav_color' => 'green' ));
	 *
	 * will result in
	 *
	 *  INSERT INTO table (id, name, fav_color) VALUES (25, 'joe', 'green')
	 *
	 * @param array $values
	 * @return SQLComposerInsert
	 */
	public function values( array $values) {
		if (isset($this->select)) throw new SQLComposerException("Cannot use 'INSERT INTO ... VALUES' when a SELECT is already set!");

		return $this->_add_params('values', $values);
	}

	/**
	 * Return a SQLComposerSelect object to be used in a query of the type INSERT INTO ... SELECT ...
	 *
	 * @param string|array $select
	 * @param array $params
	 * @return SQLComposerSelect
	 */
	public function select($select = null,  array $params = null) {
		if (isset($this->params['values'])) throw new SQLComposerException("Cannot use 'INSERT INTO ... SELECT' when values are already set!");

		if (!isset($this->select)) {
			$this->select = SQLComposer::select();
		}

		if (isset($select)) {
			$this->select->select($select, $params);
		}

		return $this->select;
	}

	/**
	 * ON DUPLICATE KEY UPDATE
	 *
	 * @param string|array $update
	 * @param array $params
	 * @return SQLComposerInsert
	 */
	public function onDuplicate($update,  array $params = null) {
		$this->on_duplicate = array_merge($this->on_duplicate, (array)$update);
		$this->_add_params('on_duplicate', $params);
		return $this;
	}


	/*****************
	 **  RENDERING  **
	 *****************/

	/**
	 * Render the INSERT query
	 *
	 * @return string
	 */
	public function render() {
		$table = $this->tables[0];

		$ignore = $this->ignore ? "IGNORE" : "";

		$columns = $this->_get_columns();
		$columns = empty($columns) ? "" : "(" . implode(", ", $columns) . ")";

		if (isset($this->select)) {
			$values = "\n" . $this->select->render();
		} else {
			$placeholders = "(" . implode(", ", array_fill(0, $this->_num_columns(), "?")) . ")";

			$num_values = count($this->params['values']);

			$values = "\nVALUES " . implode(", ", array_fill(0, $num_values, $placeholders));
		}

		$on_duplicate =	(empty($this->on_duplicate)) ? "" : "\nON DUPLICATE KEY UPDATE " . implode(", ", $this->on_duplicate);

		return "INSERT {$ignore} INTO {$table} {$columns} {$values} {$on_duplicate}";
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
		return array_merge($params, (array)$this->params['on_duplicate']);
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
			return [ ];
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