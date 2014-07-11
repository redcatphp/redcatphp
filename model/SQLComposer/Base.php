<?php namespace surikat\model\SQLComposer;
/**
 * SQLComposerBase
 *
 * The base class that all query classes should extend.
 *
 * @package SQLComposer
 * @author Shane Smith
 */
abstract class SQLComposerBase {

	function __unset($k){ //reset var
		if(property_exists($this,$k)){
			$v = get_class_vars(get_class($this));
			if(isset($this->params[$k]))
				unset($this->params[$k]);
			$this->$k = $v[$k];
		}
	}
	
	/**
	 * The query's columns
	 *
	 * @var array
	 */
	protected $columns = array( );

	/**
	 * The query's tables
	 *
	 * @var array
	 */
	protected $tables = array( );

	/**
	 * The query's parameters (for prepared statements)
	 *
	 * @var array
	 */
	protected $params = array( );

	/**
	 * The 'types' string needed for parameters in a mysqli prepared statement
	 *
	 * @var array
	 */
	protected $mysqli_types = array( );


	/***************
	 **  METHODS  **
	 ***************/

	/**
	 * Add a table to the query
	 *
	 * @param string|array $table
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerBase
	 */
	public function add_table($table, array $params = null, $mysqli_types = "") {
		$this->tables = array_merge($this->tables, (array)$table);
		$this->_add_params('tables', $params, $mysqli_types);
		return $this;
	}

	/**
	 * Alias for add_table
	 *
	 * @see add_table()
	 * @param string|array $table
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerBase
	 */
	public function join($table, array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}

	/**
	 * Alias for add_table
	 *
	 * @see add_table()
	 * @param string|array $table
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerBase
	 */
	public function from($table, array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}


	/******************
	 **  PARAMETERS  **
	 ******************/

	/**
	 * Add a parameter to the list
	 *
	 * @param string $clause
	 * @param array $params
	 * @param string $mysqli_types
	 * @return SQLComposerBase
	 */
	protected function _add_params($clause, array $params = null, $mysqli_types = "") {
		if (isset($params)) {

			if (!empty($mysqli_types)) {
				$this->mysqli_types[$clause] .= $mysqli_types;
			}

			if (!isset($this->params[$clause])) {
				$this->params[$clause] = array( );
			}

			$this->params[$clause] = array_merge($this->params[$clause], $params);

		}
		return $this;
	}

	/**
	 * Get the array of parameters, merged by the given order
	 *
	 * @param string $order,...
	 * @return array
	 */
	protected function _get_params($order) {
		if (!is_array($order)) $order = func_get_args();

		$params = array( );

		$mysqli_types = "";

		foreach ($order as $clause) {
			if (empty($this->params[$clause])) continue;

			$params = array_merge($params, $this->params[$clause]);

			$mysqli_types .= $this->mysqli_types[$clause];
		}

		if (!empty($this->mysqli_types)) {
			array_unshift($params, $mysqli_types);
		}

		return $params;
	}

	/**
	 * Get the array of parameters
	 *
	 * @abstract
	 * @return array
	 */
	abstract public function getParams();


	/*****************
	 **  RENDERING  **
	 *****************/

	/**
	 * Alias for render()
	 *
	 * @see render()
	 * @return string
	 */
	public function getQuery() {
		return $this->render();
	}

	/**
	 * Get the rendered SQL query
	 *
	 * @abstract
	 * @return string
	 */
	abstract public function render();

	/**
	 * Get a string of the SQL query and parameters for debugging
	 *
	 * @return string
	 */
	public function debug() {
		return $this->getQuery() . "\n\n" . print_r($this->getParams(), true);
	}

	/**
	 * Helper to render a boolean expression, such as in a WHERE or HAVING clause.
	 *
	 * @param array $expression
	 * @return string
	 */
	protected static function _render_bool_expr(array $expression) {

		$str = "";

		$stack = array( );

		$op = "AND";

		$first = true;
		foreach ($expression as $expr) {

			if (is_array($expr)) {

				if ($expr[0] == '(') {
					array_push($stack, $op);

					if (!$first)
						$str .= " " . $op;

					if ($expr[1] == "NOT") {
						$str .= " NOT";
					} else {
						$str .= " (";
						$op = $expr[1];
					}

					$first = true;
					continue;
				}
				elseif ($expr[0] == ')') {
					$op = array_pop($stack);
					$str .= " )";
				}

			}
			else {

				if (!$first)
					$str .= " " . $op;

				$str .= " (" . $expr . ")";

			}

			$first = false;
		}

		$str .= str_repeat(" )", count($stack));

		return $str;
	}

}