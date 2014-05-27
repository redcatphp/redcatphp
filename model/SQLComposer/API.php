<?php namespace surikat\model\SQLComposer;  
/**
 * 
 *
 * @package 
 * @author Shane Smith
 * @version 0.1
 */

// require_once "Base.class.php";
// require_once "Where.class.php";

// require_once "Select.class.php";
// require_once "Insert.class.php";
// require_once "Replace.class.php";
// require_once "Update.class.php";
// require_once "Delete.class.php";

/**
 * 
 *
 * A factory class for queries.
 *
 * ex:
 *  SQLComposer::select(array("id", "name", "role"))->from("users");
 *
 * @package 
 * @author Shane Smith
 */
abstract class API {

	/**
	 * A useful list of valid SQL operator
	 *
	 * @var array
	 */
	public static $operators = array(
		'greater than' => '>',
		'greater than or equal' => '>=',
		'less than' => '<',
		'less than or equal' => '<=',
		'equal' => '=',
		'not equal' => '!=',
		'between' => 'between',
		'in' => 'in'
	);

	/**************
	 **  SELECT  **
	 **************/

	/**
	 * Start a new SELECT statement
	 *
	 * @see Select::__construct()
	 * @param array $params
	 * @param string|array $select
	 * @param string $mysqli_types
	 * @return Select
	 */
	public static function select($select = null, array $params = null, $mysqli_types = null) {
		return new Select($select, $params, $mysqli_types);
	}


	/**************
	 **  INSERT  **
	 **************/

	/**
	 * Start a new INSERT statement
	 *
	 * @see Insert::__construct()
	 * @param string $table
	 * @return Insert
	 */
	public static function insert($table=null) {
		return self::insert_into($table);
	}

	/**
	 * Start a new INSERT statement
	 *
	 * @see Insert::__construct()
	 * @param string $table
	 * @return Insert
	 */
	public static function insert_into($table = null) {
		return new Insert($table);
	}


	/***************
	 **  REPLACE  **
	 ***************/

	/**
	 * Start a new REPLACE statement
	 *
	 * @see Replace::__construct()
	 * @param string $table
	 * @return Replace
	 */
	public static function replace($table = null) {
		return self::replace_into($table);
	}

	/**
	 * Start a new REPLACE statement
	 *
	 * @see Replace::__construct()
	 * @param string $table
	 * @return Replace
	 */
	public static function replace_into($table = null) {
		return new Replace($table);
	}

	/**************
	 **  UPDATE  **
	 **************/

	/**
	 * Start a new UPDATE statement
	 *
	 * @see Update::__construct()
	 * @param string|array $table
	 * @return Update
	 */
	public static function update($table=null) {
		return new Update($table);
	}


	/**************
	 **  DELETE  **
	 **************/

	/*
	 *  Left out delete($table) to enforce the DELETE FROM ... USING ... style of query
	 */

	/**
	 * Start a new DELETE statement
	 *
	 * @see Delete::__construct()
	 * @param string|array $table
	 * @return Delete
	 */
	public static function delete_from($table=null) {
		return new Delete($table);
	}


	/***************
	 **  HELPERS  **
	 ***************/

	/**
	 * Given an sql snippet in the form "column in (?)"
	 * and an array of parameters to be used as operands,
	 * will return an array of the form array(sql, params, mysqli_types)
	 * with the sql's '?' expanded to the number of parameters.
	 * If the given mysqli_types is only one character, it will be repeated
	 * the number of parameters.
	 *
	 * ex:
	 *  $sizes = array(24, 64, 84, 13, 95);
	 *  SQLComposer::in("size in (?)", $sizes, "i");
	 *
	 * will return
	 *
	 *  array("size in (?, ?, ?, ?, ?)", array(24, 64, 84, 13, 95), "iiiii")
	 *
	 * @param string $sql
	 * @param array $params
	 * @param string $mysqli_types
	 * @return array
	 */
	public static function in($sql, array $params, $mysqli_types="") {
		$given_params = $params;

		$placeholders = array( );
		$params = array();

		foreach ($given_params as $p) {
			if ($p instanceof Expr) {
				$placeholders[] = $p->value;
				if (!empty($p->params)) {
					$params = array_merge($params, $p->params);
				}
			} else {
				$placeholders[] = "?";
				$params[] = $p;
			}
		}

		if (strlen($mysqli_types) == 1) {
			$mysqli_types = str_repeat($mysqli_types, sizeof($params));
		}

		$placeholders = implode(", ", $placeholders);
		$sql = str_replace("?", $placeholders, $sql);
		return array($sql, $params, $mysqli_types);
	}

	/**
	 * Whether the given array is associative
	 *
	 * @param $array
	 * @return bool
	 */
	public static function is_assoc($array) {
		return (array_keys($array) !== range(0, count($array) - 1));
	}

	/**
	 * Whether the given operator is a valid SQL operator
	 *
	 * @param string $op
	 * @return bool
	 */
	public static function isValidOperator($op) {
		return in_array($op, self::$operators);
	}

	/**
	 * Returns the SQL relating to the operator
	 *
	 * @param string $column
	 * @param string $op
	 * @param array $params
	 * @param string $mysqli_types
	 * @return string
	 */
	public static function applyOperator($column, $op, array $params=null, $mysqli_types="") {
		switch ($op) {
			case '>': case '>=':
			case '<': case '<=':
			case '=': case '!=':
				return array("{$column} {$op} ?", $params, $mysqli_types);
			case 'in':
				return self::in("{$column} in (?)", $params, $mysqli_types);
			case 'between':
				$sql = "{$column} between ";
				$p = array_shift($params);
				if ($p instanceof Expr) {
					$sql .= $p->value;
				} else {
					$sql .= "?";
					array_push($params, $p);
				}
				$sql .= " and ";
				$p = array_shift($params);
				if ($p instanceof Expr) {
					$sql .= $p->value;
				} else {
					$sql .= "?";
					array_push($params, $p);
				}
				return array($sql, $params, $mysqli_types);
			default:
				throw new Exception("Invalid operator: {$op}");
		}
	}

	/**
	 * A factory for Expr
	 *
	 * @param string $val
	 * @param array $params
	 * @param string $mysqli_types
	 * @return Expr
	 */
	public static function expr($val, array $params=array(), $mysqli_types="") {
		return new Expr($val, $params, $mysqli_types);
	}
}

/**
 * A container to denote an expression to be directly embedded
 */
class Expr {
	public $value, $params, $mysqli_types;
	public function __construct($val, array $params=array(), $mysqli_types="") {
		$this->value = $val;
		$this->params = $params;
		$this->mysqli_types = $mysqli_types;
	}
}

/**
 * Exception
 *
 * The main exception to be used within these classes
 */
class Exception extends \Exception {}

?>
