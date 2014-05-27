<?php namespace surikat\model\SQLComposer; 
// require_once ".class.php";

/**
 * Where
 *
 * A helper class for any query classes that contain a WHERE clause
 */
abstract class Where extends Base {

	/**
	 * WHERE clause
	 *
	 * @var array
	 */
	protected $where = array( );


	/***************
	 **  METHODS  **
	 ***************/

	/**
	 * Add a where expression
	 *
	 * @param string $where
	 * @param array $params
	 * @param string $mysqli_types
	 * @return Where
	 */
	public function where($where, array $params = null, $mysqli_types = "") {
		$this->where[] = $where;
		$this->_add_params('where', $params, $mysqli_types);
		return $this;
	}

	/**
	 * Add a where expression by using SQLComposer::in()
	 *
	 * @see SQLComposer::in()
	 * @param string $where
	 * @param array $params
	 * @param string $mysqli_types
	 * @return Where
	 */
	public function where_in($where, array $params, $mysqli_types = "") {
		list($where, $params, $mysqli_types) = SQLComposer::in($where, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}

	/**
	 * Add a where expression by using SQLComposer::applyOperator()
	 *
	 * @param string $column
	 * @param string $op
	 * @param array $params
	 * @param string $mysqli_types
	 * @return Where
	 */
	public function where_op($column, $op, array $params=null, $mysqli_types="") {
		list($where, $params, $mysqli_types) = SQLComposer::applyOperator($column, $op, $params, $mysqli_types);
		return $this->where($where, $params, $mysqli_types);
	}

	/**
	 * Open a paranthesis for sub-expressions using 'AND'
	 *
	 * @return Where
	 */
	public function open_where_and() {
		$this->where[] = array( '(', 'AND' );
		return $this;
	}

	/**
	 * Open a paranthesis for sub-expressions using 'OR'
	 *
	 * @return Where
	 */
	public function open_where_or() {
		$this->where[] = array( '(', 'OR' );
		return $this;
	}

	/**
	 * Open a paranthesis preceded by a 'NOT' for sub-expressions using 'AND'
	 *
	 * @return Where
	 */
	public function open_where_not_and() {
		$this->where[] = array('(', 'NOT');
		$this->open_where_and();
		return $this;
	}

	/**
	 * Open a paranthesis preceded by a 'NOT' for sub-expressions using 'OR'
	 *
	 * @return Where
	 */
	public function open_where_not_or() {
		$this->where[] = array( '(', 'NOT' );
		$this->open_where_or();
		return $this;
	}

	/**
	 * Close a paranthesis with for sub-expressions
	 *
	 * @return Where
	 */
	public function close_where() {
		$this->where[] = array( ')' );
		return $this;
	}

	/**
	 * Render the where clause (without the starting 'WHERE')
	 *
	 * @return string
	 */
	protected function _render_where() {
		return Base::_render_bool_expr($this->where);
	}


}
