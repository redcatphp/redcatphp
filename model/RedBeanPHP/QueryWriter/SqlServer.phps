<?php
/**
 * Created by PhpStorm.
 * User: dreiling
 * Date: 26.07.14
 * Time: 16:00
 */

namespace surikat\model\RedBeanPHP;


use surikat\model\RedBeanPHP\QueryWriter\AQueryWriter;
use surikat\model\RedBeanPHP\Driver\RPDO as RPDO;
use surikat\model\RedBeanPHP\Adapter\DBAdapter as DBAdapter;


class SqlServer extends AQueryWriter implements QueryWriter
{

    protected $adapter = null;

    const C_DATATYPE_BOOL = 0; // BIT
    const C_DATATYPE_INT = 1; // => 'INT', <= 2,147,483,648
    const C_DATATYPE_BIGINT = 2; // => 'BIGINT', <= 9,223,372,036,854,775,808
    const C_DATATYPE_FLOAT = 3; //  => ' FLOAT '
    const C_DATATYPE_REAL = 4; //  => ' //really big numbers, unused for now
    const C_DATATYPE_VARCHAR = 10; // => ' VARCHAR(255) '
    const C_DATATYPE_TEXT = 11; // => ' TEXT '
    const C_DATATYPE_SPECIAL_DATETIME = 20; // => ' DATETIME '
    const C_DATATYPE_SPECIFIED = 99;


    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;

        $this->typeno_sqltype = array(
            self::C_DATATYPE_BOOL => " BIT ",
            self::C_DATATYPE_INT => " INT ",
            self::C_DATATYPE_BIGINT => " BIGINT ",
            self::C_DATATYPE_FLOAT => " FLOAT ",
            self::C_DATATYPE_REAL => " REAL ",
            self::C_DATATYPE_VARCHAR => " VARCHAR(255) ",
            self::C_DATATYPE_TEXT => " TEXT ",
            self::C_DATATYPE_SPECIAL_DATETIME => " DATETIME "
        );

        $this->sqltype_typeno = array();

        foreach ($this->typeno_sqltype as $k => $v) {
            $this->sqltype_typeno[trim(strtolower($v))] = $k;
        }
    }


    /**
     * @see QueryWriter::getTables
     */
    public function getTables()
    {
        return $this->adapter->getCol('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES');
    }

    /**
     * @see QueryWriter::createTable
     */
    public function createTable($type)
    {
        $table = $this->esc($type);
        $sql = "CREATE TABLE $table
            ( id BIGINT NOT NULL IDENTITY(1,1),
            PRIMARY KEY (id))";

        $this->adapter->exec($sql);
    }


    /**
     * @see QueryWriter::getColumns
     */
    public function getColumns($type)
    {

        $table = $this->esc($type);

        $result = $this->adapter->get("SELECT *
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = N'$table'");

        $columns = array();
        foreach ($result as $r) {
            $columns[$r["COLUMN_NAME"]] = $r["DATA_TYPE"];
        }

        return $columns;
    }


    /**
     * @see AQueryWriter::insertReord
     */
    protected function insertRecord($type, $insertcolumns, $insertvalues)
    {

        $table = $this->esc($type);
        $suffix = $this->getInsertSuffix($type);

        if (count($insertvalues) > 0 && is_array($insertvalues[0]) && count($insertvalues[0]) > 0) {

            foreach ($insertcolumns as $k => $v) {
                $insertcolumns[$k] = $this->esc($v);
            }

            $insertSQL = "INSERT INTO $table ( " . implode(',', $insertcolumns) . " ) VALUES
			( " . implode(',', array_fill(0, count($insertcolumns), ' ? ')) . " ) $suffix";

            $ids = array();
            foreach ($insertvalues as $i => $insertvalue) {
                $ids[] = $this->adapter->getCell($insertSQL, $insertvalue, $i);
            }

            $result = count($ids) === 1 ? array_pop($ids) : $ids;

        } else {
            $result = $this->adapter->getCell("INSERT INTO $table DEFAULT VALUES");
        }

        if ($suffix) return $result;

        $last_id = $this->adapter->getInsertID();
        return $last_id;
    }

    /**
     * @see QueryWriter::scanType
     */
    public function scanType($value, $alsoScanSpecialForTypes = FALSE)
    {
        if (is_null($value)) return self::C_DATATYPE_BOOL;

        if ($alsoScanSpecialForTypes) {
            if (preg_match('/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value)) {
                return self::C_DATATYPE_SPECIAL_DATETIME;
            }
        }

        $value = strval($value);

        if (!$this->startsWithZeros($value)) {

            if ($value === TRUE || $value === FALSE || $value === '1' || $value === '' || $value === '0') {
                return self::C_DATATYPE_BOOL;
            }

            if (is_numeric($value) && (floor($value) == $value)) {
                if (abs($value) <= 2147483648) {
                    return self::C_DATATYPE_INT;
                }
                if (abs($value) <= 9223372036854775808) {
                    return self::C_DATATYPE_BIGINT;
                }
            }

            if (is_numeric($value)) {
                return self::C_DATATYPE_FLOAT;
            }
        }

        if (mb_strlen($value, 'UTF-8') <= 255) {
            return self::C_DATATYPE_VARCHAR;
        }

        return self::C_DATATYPE_TEXT;
    }

    /**
     * @see QueryWriter::code
     */
    public function code($typedescription, $includeSpecials = FALSE)
    {

        //FIXME: better fetch length from information schema
        if ($typedescription == "varchar") {
            $typedescription = "varchar(255)";
        }

        if (isset($this->sqltype_typeno[$typedescription])) {
            $r = $this->sqltype_typeno[$typedescription];
        } else {
            $r = self::C_DATATYPE_SPECIFIED;
        }

        if ($includeSpecials) {
            return $r;
        }

        if ($r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL) {
            return self::C_DATATYPE_SPECIFIED;
        }

        return $r;
    }

    /**
     * @see QueryWriter::ggetTypeForID
     */
    public function getTypeForID()
    {
        return self::C_DATATYPE_BIGINT;
    }

    /**
     * @see QueryWriter::addUniqueIndex
     */
    public function addUniqueIndex($type, $columns)
    {
        // TODO: Implement addUniqueIndex() method.
        echo "TODO: addUniqueIndex";

    }

    /**
     * @see QueryWriter::sqlStateIn
     */
    public function sqlStateIn($state, $list)
    {

        $stateMap = array(
            '42S02' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
            '42S22' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
            '23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
        );

        return in_array((isset($stateMap[$state]) ? $stateMap[$state] : '0'), $list);
    }

    /**
     * @see QueryWriter::addFK
     */
    public function addFK($type, $targetType, $field, $targetField, $isDependent = FALSE)
    {

        $cfks = $this->adapter->getCell('
			SELECT CONSTRAINT_NAME
				FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
			WHERE
				TABLE_NAME = ?
				AND COLUMN_NAME = ? AND
				CONSTRAINT_NAME != \'PRIMARY\'
		', array($type, $field)); //AND REFERENCED_TABLE_NAME IS NOT NULL

        if ($cfks) return;

        try {
            $fkName = 'fk_' . ($type . '_' . $field);
            $cName = 'c_' . $fkName;
            $this->adapter->exec("
				ALTER TABLE  {$this->esc($type)}
				ADD CONSTRAINT $cName
				FOREIGN KEY $fkName ( {$this->esc($field)} ) REFERENCES {$this->esc($targetType)} (
				{$this->esc($targetField)}) ON DELETE " . ($isDependent ? 'CASCADE' : 'SET NULL') . ' ON UPDATE ' . ($isDependent ? 'CASCADE' : 'SET NULL') . ';');

        } catch (\Exception $e) {
            // Failure of fk-constraints is not a problem
        }

    }

    /**
     * @see QueryWriter::addIndex
     */
    public function addIndex($type, $name, $column)
    {

        $table = $this->esc($type);
        $name = preg_replace('/\W/', '', $name);
        $column = $this->esc($column);

        try {
            foreach ($this->adapter->get("SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = N'$table'") as $ind) if ($ind['CONSTRAINT_NAME'] === $name) return;

            $this->adapter->exec("CREATE INDEX $name ON $table ($column) ");
        } catch (\Exception $e) {
        }
    }

    /**
     * @see QueryWriter::wipeAll
     */
    public function wipeAll()
    {

        foreach ($this->getTables() as $t) {
            try {
                $this->adapter->exec("ALTER TABLE $t NOCHECK CONSTRAINT ALL");
                $this->adapter->exec("IF OBJECT_ID('$t', 'U') IS NOT NULL DROP TABLE $t");
            } catch (\Exception $e) {
            }

            //TODO dropping views
        }

    }


    /**
     * @see QueryWriter::widenColumn
     */
    public function widenColumn($type, $column, $datatype)
    {
        if (!isset($this->typeno_sqltype[$datatype])) return;

        $table = $this->esc($type);
        $column = $this->esc($column);
        $newtype = $this->typeno_sqltype[$datatype];

        $this->adapter->exec("ALTER TABLE $table ALTER COLUMN $column $newtype NULL");
    }

}