<?php
/**
 * Object representing a SQL query.
 * The various parts of the SQL query can be manipulated individually.
 * 
 * Caution: Only supports SELECT (default) and DELETE at the moment.
 * 
 * @todo Add support for INSERT and UPDATE queries
 * 
 * @package framework
 * @subpackage model
 */
class SQLQuery {
	
	/**
	 * An array of SELECT fields, keyed by an optional alias.
	 * @var array
	 */
	protected $select = array();
	
	/**
	 * An array of FROM clauses. The first one is just the table name.
	 * @var array
	 */
	protected $from = array();
	
	/**
	 * An array of WHERE clauses.
	 * @var array
	 */
	protected $where = array();
	
	/**
	 * An array of ORDER BY clauses, functions. Stores as an associative
	 * array of column / function to direction.
	 *
	 * @var string
	 */
	protected $orderby = array();
	
	/**
	 * An array of GROUP BY clauses.
	 * @var array
	 */
	protected $groupby = array();
	
	/**
	 * An array of having clauses.
	 * @var array
	 */
	protected $having = array();
	
	/**
	 * An array containing limit and offset keys for LIMIT clause.
	 * @var array
	 */
	protected $limit = array();
	
	/**
	 * If this is true DISTINCT will be added to the SQL.
	 * @var boolean
	 */
	protected $distinct = false;
	
	/**
	 * If this is true, this statement will delete rather than select.
	 * @var boolean
	 */
	protected $delete = false;
	
	/**
	 * The logical connective used to join WHERE clauses. Defaults to AND.
	 * @var string
	 */
	protected $connective = 'AND';
	
	/**
	 * Keep an internal register of find/replace pairs to execute when it's time to actually get the
	 * query SQL.
	 * @var array
	 */
	protected $replacementsOld = array();

	/**
	 * Keep an internal register of find/replace pairs to execute when it's time to actually get the
	 * query SQL.
	 * @var array
	 */
	protected $replacementsNew = array();

	/**
	 * Construct a new SQLQuery.
	 *
	 * @param array $select An array of SELECT fields.
	 * @param array $from An array of FROM clauses. The first one should be just the table name.
	 * @param array $where An array of WHERE clauses.
	 * @param array $orderby An array ORDER BY clause.
	 * @param array $groupby An array of GROUP BY clauses.
	 * @param array $having An array of HAVING clauses.
	 * @param array|string $limit A LIMIT clause or array with limit and offset keys
	 */
	public function __construct($select = "*", $from = array(), $where = array(), $orderby = array(),
			$groupby = array(), $having = array(), $limit = array()) {

		$this->setSelect($select);
		$this->setFrom($from);
		$this->setWhere($where);
		$this->setOrderBy($orderby);
		$this->setGroupBy($groupby);
		$this->setHaving($having);
		$this->setLimit($limit);
	}

	public function __get($field) {
		if(strtolower($field) == 'select') Deprecation::notice('3.0', 'Please use getSelect() instead');
		if(strtolower($field) == 'from') Deprecation::notice('3.0', 'Please use getFrom() instead');
		if(strtolower($field) == 'groupby') Deprecation::notice('3.0', 'Please use getGroupBy() instead');
		if(strtolower($field) == 'orderby') Deprecation::notice('3.0', 'Please use getOrderBy() instead');
		if(strtolower($field) == 'having') Deprecation::notice('3.0', 'Please use getHaving() instead');
		if(strtolower($field) == 'limit') Deprecation::notice('3.0', 'Please use getLimit() instead');
		if(strtolower($field) == 'delete') Deprecation::notice('3.0', 'Please use getDelete() instead');
		if(strtolower($field) == 'connective') Deprecation::notice('3.0', 'Please use getConnective() instead');
		if(strtolower($field) == 'distinct') Deprecation::notice('3.0', 'Please use getDistinct() instead');

		return $this->$field;
	}

	public function __set($field, $value) {
		if(strtolower($field) == 'select') Deprecation::notice('3.0', 'Please use setSelect() or addSelect() instead');
		if(strtolower($field) == 'from') Deprecation::notice('3.0', 'Please use setFrom() or addFrom() instead');
		if(strtolower($field) == 'groupby') {
			Deprecation::notice('3.0', 'Please use setGroupBy() or addGroupBy() instead');
		}
		if(strtolower($field) == 'orderby') {
			Deprecation::notice('3.0', 'Please use setOrderBy() or addOrderBy() instead');
		}
		if(strtolower($field) == 'having') Deprecation::notice('3.0', 'Please use setHaving() or addHaving() instead');
		if(strtolower($field) == 'limit') Deprecation::notice('3.0', 'Please use setLimit() instead');
		if(strtolower($field) == 'delete') Deprecation::notice('3.0', 'Please use setDelete() instead');
		if(strtolower($field) == 'connective') Deprecation::notice('3.0', 'Please use setConnective() instead');
		if(strtolower($field) == 'distinct') Deprecation::notice('3.0', 'Please use setDistinct() instead');

		return $this->$field = $value;
	}

	/**
	 * Set the list of columns to be selected by the query.
	 *
	 * <code>
	 *  // pass fields to select as single parameter array
	 *  $query->setSelect(array("Col1","Col2"))->setFrom("MyTable");
	 *
	 *  // pass fields to select as multiple parameters
	 *  $query->setSelect("Col1", "Col2")->setFrom("MyTable");
	 * </code>
	 *
	 * @param string|array $fields
	 * @param boolean $clear Clear existing select fields?
	 * @return SQLQuery
	 */
	public function setSelect($fields) {
		$this->select = array();
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
		}
		return $this->addSelect($fields);
	}

	/**
	 * Add to the list of columns to be selected by the query.
	 *
	 * <code>
	 *  // pass fields to select as single parameter array
	 *  $query->addSelect(array("Col1","Col2"))->setFrom("MyTable");
	 *
	 *  // pass fields to select as multiple parameters
	 *  $query->addSelect("Col1", "Col2")->setFrom("MyTable");
	 * </code>
	 *
	 * @param string|array $fields
	 * @param boolean $clear Clear existing select fields?
	 * @return SQLQuery
	 */
	public function addSelect($fields) {
		if (func_num_args() > 1) {
			$fields = func_get_args();
		} else if(!is_array($fields)) {
			$fields = array($fields);
		}

		foreach($fields as $idx => $field) {
			if(preg_match('/^(.*) +AS +"?([^"]*)"?/i', $field, $matches)) {
				Deprecation::notice("3.0", "Use selectField() to specify column aliases");
				$this->selectField($matches[1], $matches[2]);
			} else {
				$this->selectField($field, is_numeric($idx) ? null : $idx);
			}
		}
		
		return $this;
	}

	public function select($fields) {
		Deprecation::notice('3.0', 'Please use setSelect() or addSelect() instead!');
		$this->setSelect($fields);
	}

	/**
	 * Select an additional field.
	 *
	 * @param $field String The field to select (escaped SQL statement)
	 * @param $alias String The alias of that field (escaped SQL statement).
	 * Defaults to the unquoted column name of the $field parameter.
	 * @return SQLQuery
	 */
	public function selectField($field, $alias = null) {
		if(!$alias) {
			if(preg_match('/"([^"]+)"$/', $field, $matches)) $alias = $matches[1];
			else $alias = $field;
		}
		$this->select[$alias] = $field;
		return $this;
	}

	/**
	 * Return the SQL expression for the given field alias.
	 * Returns null if the given alias doesn't exist.
	 * See {@link selectField()} for details on alias generation.
	 * 
	 * @param String $field
	 * @return String
	 */
	public function expressionForField($field) {
		return isset($this->select[$field]) ? $this->select[$field] : null;
	}
	
	/**
	 * Set table for the SELECT clause.
	 *
	 * @example $query->setFrom('"MyTable"'); // SELECT * FROM "MyTable"
	 *
	 * @param string|array $from Single, or list of, ANSI quoted table names
	 * @return self
	 */
	public function setFrom($from) {
		$this->from = array();
		return $this->addFrom($from);
	}

	/**
	 * Add a table to the SELECT clause.
	 *
	 * @example $query->addFrom('"MyTable"'); // SELECT * FROM "MyTable"
	 *
	 * @param string|array $from Single, or list of, ANSI quoted table names
	 * @return self Self reference
	 */
	public function addFrom($from) {
		if(is_array($from)) {
			$this->from = array_merge($this->from, $from);
		} elseif(!empty($from)) {
			$this->from[str_replace(array('"','`'), '', $from)] = $from;
		}

		return $this;
	}

	public function from($from) {
		Deprecation::notice('3.0', 'Please use setFrom() or addFrom() instead!');
		return $this->setFrom($from);
	}
	
	/**
	 * Add a LEFT JOIN criteria to the FROM clause.
	 *
	 * @param string $table Unquoted table name
	 * @param string $onPredicate The "ON" SQL fragment in a "LEFT JOIN ... AS ... ON ..." statement, Needs to be valid 
	 *                            (quoted) SQL.
	 * @param string $tableAlias Optional alias which makes it easier to identify and replace joins later on
	 * @param int $order A numerical index to control the order that joins are added to the query; lower order values 
	 *                   will cause the query to appear first. The default is 20, and joins created automatically by the
	 *                   ORM have a value of 10.
	 * @return SQLQuery
	 */
	public function addLeftJoin($table, $onPredicate, $tableAlias = '', $order = 20) {
		if(!$tableAlias) {
			$tableAlias = $table;
		}
		$this->from[$tableAlias] = array(
			'type' => 'LEFT',
			'table' => $table,
			'filter' => array($onPredicate), 
			'order' => $order
		);
		return $this;
	}

	public function leftjoin($table, $onPredicate, $tableAlias = null, $order = 20) {
		Deprecation::notice('3.0', 'Please use addLeftJoin() instead!');
		$this->addLeftJoin($table, $onPredicate, $tableAlias);
	}

	/**
	 * Add an INNER JOIN criteria to the FROM clause.
	 *
	 * @param string $table Unquoted table name
	 * @param string $onPredicate The "ON" SQL fragment in an "INNER JOIN ... AS ... ON ..." statement. Needs to be 
	 *                            valid (quoted) SQL.
	 * @param string $tableAlias Optional alias which makes it easier to identify and replace joins later on
	 * @param int $order A numerical index to control the order that joins are added to the query; lower order values 
	 *                   will cause the query to appear first. The default is 20, and joins created automatically by the
	 *                   ORM have a value of 10.
	 * @return SQLQuery
	 */
	public function addInnerJoin($table, $onPredicate, $tableAlias = null, $order = 20) {
		if(!$tableAlias) $tableAlias = $table;
		$this->from[$tableAlias] = array(
			'type' => 'INNER',
			'table' => $table,
			'filter' => array($onPredicate),
			'order' => $order
		);
		return $this;
	}

	public function innerjoin($table, $onPredicate, $tableAlias = null, $order = 20) {
		Deprecation::notice('3.0', 'Please use addInnerJoin() instead!');
		return $this->addInnerJoin($table, $onPredicate, $tableAlias, $order);
	}

	/**
	 * Add an additional filter (part of the ON clause) on a join.
	 *
	 * @param string $table Table to join on from the original join
	 * @param string $filter The "ON" SQL fragment (escaped)
	 * @return SQLQuery
	 */
	public function addFilterToJoin($table, $filter) {
		$this->from[$table]['filter'][] = $filter;
		return $this;
	}

	/**
	 * Set the filter (part of the ON clause) on a join.
	 *
	 * @param string $table Table to join on from the original join
	 * @param string $filter The "ON" SQL fragment (escaped)
	 * @return SQLQuery
	 */
	public function setJoinFilter($table, $filter) {
		$this->from[$table]['filter'] = array($filter);
		return $this;
	}
	
	/**
	 * Returns true if we are already joining to the given table alias
	 * 
	 * @return boolean
	 */
	public function isJoinedTo($tableAlias) {
		return isset($this->from[$tableAlias]);
	}
	
	/**
	 * Return a list of tables that this query is selecting from.
	 * 
	 * @return array Unquoted table names
	 */
	public function queriedTables() {
		$tables = array();
		
		foreach($this->from as $key => $tableClause) {
			if(is_array($tableClause)) {
				$table = '"'.$tableClause['table'].'"';
			} else if(is_string($tableClause) && preg_match('/JOIN +("[^"]+") +(AS|ON) +/i', $tableClause, $matches)) {
				$table = $matches[1];
			} else {
				$table = $tableClause;
			}

			// Handle string replacements
			if($this->replacementsOld) $table = str_replace($this->replacementsOld, $this->replacementsNew, $table);
			
			$tables[] = preg_replace('/^"|"$/','',$table);
		}
		
		return $tables;	
	}

	/**
	 * Set distinct property.
	 * @param boolean $value
	 */
	public function setDistinct($value) {
		$this->distinct = $value;
	}

	/**
	 * Get the distinct property.
	 * @return boolean
	 */
	public function getDistinct() {
		return $this->distinct;
	}

	/**
	 * Set the delete property.
	 * @param boolean $value
	 */
	public function setDelete($value) {
		$this->delete = $value;
	}

	/**
	 * Get the delete property.
	 * @return boolean
	 */
	public function getDelete() {
		return $this->delete;
	}

	/**
	 * Set the connective property.
	 * @param boolean $value
	 */
	public function setConnective($value) {
		$this->connective = $value;
	}

	/**
	 * Get the connective property.
	 * @return string
	 */
	public function getConnective() {
		return $this->connective;
	}

	/**
	 * Get the limit property.
	 * @return array
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Pass LIMIT clause either as SQL snippet or in array format.
	 * Internally, limit will always be stored as a map containing the keys 'start' and 'limit'
	 *
	 * @param int|string|array $limit If passed as a string or array, assumes SQL escaped data.
	 *                                Only applies for positive values, or if an $offset is set as well.
	 * @param int $offset
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return SQLQuery This instance
	 */
	public function setLimit($limit, $offset = 0) {
		if((is_numeric($limit) && $limit < 0) || (is_numeric($offset) && $offset < 0)) {
			throw new InvalidArgumentException("SQLQuery::setLimit() only takes positive values");
		}

		if(is_numeric($limit) && ($limit || $offset)) {
			$this->limit = array(
				'start' => $offset,
				'limit' => $limit,
			);
		} else if($limit && is_string($limit)) {
			if(strpos($limit, ',') !== false) {
				list($start, $innerLimit) = explode(',', $limit, 2);
			}
			else {
				list($innerLimit, $start) = explode(' OFFSET ', strtoupper($limit), 2);
			}

			$this->limit = array(
				'start' => trim($start),
				'limit' => trim($innerLimit),
			);
		} else {
			$this->limit = $limit;
		}

		return $this;
	}

	public function limit($limit, $offset = 0) {
		Deprecation::notice('3.0', 'Please use setLimit() instead!');
		return $this->setLimit($limit, $offset);
	}

	/**
	 * Set ORDER BY clause either as SQL snippet or in array format.
	 *
	 * @example $sql->setOrderBy("Column");
	 * @example $sql->setOrderBy("Column DESC");
	 * @example $sql->setOrderBy("Column DESC, ColumnTwo ASC");
	 * @example $sql->setOrderBy("Column", "DESC");
	 * @example $sql->setOrderBy(array("Column" => "ASC", "ColumnTwo" => "DESC"));
	 *
	 * @param string|array $orderby Clauses to add (escaped SQL statement)
	 * @param string $dir Sort direction, ASC or DESC
	 *
	 * @return SQLQuery
	 */
	public function setOrderBy($clauses = null, $direction = null) {
		$this->orderby = array();
		return $this->addOrderBy($clauses, $direction);
	}

	/**
	 * Add ORDER BY clause either as SQL snippet or in array format.
	 *
	 * @example $sql->addOrderBy("Column");
	 * @example $sql->addOrderBy("Column DESC");
	 * @example $sql->addOrderBy("Column DESC, ColumnTwo ASC");
	 * @example $sql->addOrderBy("Column", "DESC");
	 * @example $sql->addOrderBy(array("Column" => "ASC", "ColumnTwo" => "DESC"));
	 *
	 * @param string|array $clauses Clauses to add (escaped SQL statements)
	 * @param string $dir Sort direction, ASC or DESC
	 *
	 * @return SQLQuery
	 */
	public function addOrderBy($clauses = null, $direction = null) {
		if(!$clauses) {
			return $this;
		}
		
		if(is_string($clauses)) {
			if(strpos($clauses, "(") !== false) {
				$sort = preg_split("/,(?![^()]*+\\))/", $clauses);
			} else {
				$sort = explode(",", $clauses);
			}

			$clauses = array();
			
			foreach($sort as $clause) {
				list($column, $direction) = $this->getDirectionFromString($clause, $direction);
				$clauses[$column] = $direction;
			}
		}
		
		if(is_array($clauses)) {
			foreach($clauses as $key => $value) {
				if(!is_numeric($key)) {
					$column = trim($key);
					$columnDir = strtoupper(trim($value));
				} else {
					list($column, $columnDir) = $this->getDirectionFromString($value);
				}

				$this->orderby[$column] = $columnDir;
			}
		} else {
			user_error('SQLQuery::orderby() incorrect format for $orderby', E_USER_WARNING);
		}

		// If sort contains a public function call, let's move the sort clause into a
		// separate selected field.
		//
		// Some versions of MySQL choke if you have a group public function referenced
		// directly in the ORDER BY
		if($this->orderby) {
			$i = 0;
			$orderby = array();
			foreach($this->orderby as $clause => $dir) {

				// public function calls and multi-word columns like "CASE WHEN ..."
				if(strpos($clause, '(') !== false || strpos($clause, " ") !== false ) {
					
					// Move the clause to the select fragment, substituting a placeholder column in the sort fragment.
					$clause = trim($clause);
					$column = "_SortColumn{$i}";
					$this->selectField($clause, $column);
					$clause = '"' . $column . '"';
					$i++;
				}
				
				$orderby[$clause] = $dir;
			}
			$this->orderby = $orderby;
		}

		return $this;
	}

	public function orderby($clauses = null, $direction = null) {
		Deprecation::notice('3.0', 'Please use setOrderBy() instead!');
		return $this->setOrderBy($clauses, $direction);
	}

	/**
	 * Extract the direction part of a single-column order by clause.
	 * 
	 * @param String
	 * @param String
	 * @return Array A two element array: array($column, $direction)
	 */
	private function getDirectionFromString($value, $defaultDirection = null) {
		if(preg_match('/^(.*)(asc|desc)$/i', $value, $matches)) {
			$column = trim($matches[1]);
			$direction = strtoupper($matches[2]);
		} else {
			$column = $value;
			$direction = $defaultDirection ? $defaultDirection : "ASC";
		}
		return array($column, $direction);
	}

	/**
	 * Returns the current order by as array if not already. To handle legacy
	 * statements which are stored as strings. Without clauses and directions,
	 * convert the orderby clause to something readable.
	 *
	 * @return array
	 */
	public function getOrderBy() {
		$orderby = $this->orderby;
		if(!$orderby) $orderby = array();

		if(!is_array($orderby)) {
			// spilt by any commas not within brackets
			$orderby = preg_split('/,(?![^()]*+\\))/', $orderby);
		}

		foreach($orderby as $k => $v) {
			if(strpos($v, ' ') !== false) {
				unset($orderby[$k]);

				$rule = explode(' ', trim($v));
				$clause = $rule[0];
				$dir = (isset($rule[1])) ? $rule[1] : 'ASC';

				$orderby[$clause] = $dir;
			}
		}

		return $orderby;
	}
	
	/**
	 * Reverses the order by clause by replacing ASC or DESC references in the
	 * current order by with it's corollary.
	 *
	 * @return SQLQuery
	 */
	public function reverseOrderBy() {
		$order = $this->getOrderBy();
		$this->orderby = array();

		foreach($order as $clause => $dir) {
			$dir = (strtoupper($dir) == 'DESC') ? 'ASC' : 'DESC';
			$this->addOrderBy($clause, $dir);
		}

		return $this;
	}
	
	/**
	 * Set a GROUP BY clause.
	 *
	 * @param string|array $groupby Escaped SQL statement
	 * @return SQLQuery
	 */
	public function setGroupBy($groupby) {
		$this->groupby = array();
		return $this->addGroupBy($groupby);
	}

	/**
	 * Add a GROUP BY clause.
	 *
	 * @param string|array $groupby Escaped SQL statement
	 * @return SQLQuery
	 */
	public function addGroupBy($groupby) {
		if(is_array($groupby)) {
			$this->groupby = array_merge($this->groupby, $groupby);
		} elseif(!empty($groupby)) {
			$this->groupby[] = $groupby;
		}

		return $this;
	}

	public function groupby($where) {
		Deprecation::notice('3.0', 'Please use setGroupBy() or addHaving() instead!');
		return $this->setGroupBy($where);
	}

	/**
	 * Set a HAVING clause.
	 *
	 * @param string|array $having
	 * @return SQLQuery
	 */
	public function setHaving($having) {
		$this->having = array();
		return $this->addHaving($having);
	}

	/**
	 * Add a HAVING clause
	 *
	 * @param string|array $having Escaped SQL statement
	 * @return SQLQuery
	 */
	public function addHaving($having) {
		if(is_array($having)) {
			$this->having = array_merge($this->having, $having);
		} elseif(!empty($having)) {
			$this->having[] = $having;
		}

		return $this;
	}

	public function having($having) {
		Deprecation::notice('3.0', 'Please use setHaving() or addHaving() instead!');
		return $this->setHaving($having);
	}

	/**
	 * Set a WHERE clause.
	 *
	 * There are two different ways of doing this:
	 *
	 * <code>
	 *  // the entire predicate as a single string
	 *  $query->where("Column = 'Value'");
	 *
	 *  // multiple predicates as an array
	 *  $query->where(array("Column = 'Value'", "Column != 'Value'"));
	 * </code>
	 *
	 * @param string|array $where Predicate(s) to set, as escaped SQL statements.
	 * @return SQLQuery
	 */
	public function setWhere($where) {
		$this->where = array();
		return $this->addWhere($where);
	}

	/**
	 * Add a WHERE predicate.
	 *
	 * There are two different ways of doing this:
	 *
	 * <code>
	 *  // the entire predicate as a single string
	 *  $query->where("Column = 'Value'");
	 *
	 *  // multiple predicates as an array
	 *  $query->where(array("Column = 'Value'", "Column != 'Value'"));
	 * </code>
	 *
	 * @param string|array $where Predicate(s) to set, as escaped SQL statements.
	 * @return SQLQuery
	 */
	public function addWhere($where) {
		if(is_array($where)) {
			$this->where = array_merge($this->where, $where);
		} elseif(!empty($where)) {
			$this->where[] = $where;
		}
		
		return $this;
	}

	public function where($where) {
		Deprecation::notice('3.0', 'Please use setWhere() or addWhere() instead!');
		return $this->setWhere($where);
	}

	public function whereAny($where) {
		Deprecation::notice('3.0', 'Please use setWhereAny() or setWhereAny() instead!');
		return $this->setWhereAny($where);
	}

	/**
	 * @param String|array $filters Predicate(s) to set, as escaped SQL statements.
	 */
	public function setWhereAny($filters) {
		if(is_string($filters)) $filters = func_get_args();
		$clause = implode(" OR ", $filters);
		return $this->setWhere($clause);
	}

	/**
	 * @param String|array $filters Predicate(s) to set, as escaped SQL statements.
	 */
	public function addWhereAny($filters) {
		if(is_string($filters)) $filters = func_get_args();
		$clause = implode(" OR ", $filters);
		return $this->addWhere($clause);
	}
		
	/**
	 * Use the disjunctive operator 'OR' to join filter expressions in the WHERE clause.
	 */
	public function useDisjunction() {
		$this->connective = 'OR';
	}

	/**
	 * Use the conjunctive operator 'AND' to join filter expressions in the WHERE clause.
	 */
	public function useConjunction() {
		$this->connective = 'AND';
	}
	
	/**
	 * Swap the use of one table with another.
	 * 
	 * @param string $old Name of the old table (unquoted, escaped)
	 * @param string $new Name of the new table (unquoted, escaped)
	 */
	public function renameTable($old, $new) {
		$this->replaceText("`$old`", "`$new`");
		$this->replaceText("\"$old\"", "\"$new\"");
	}
	
	/**
	 * Swap some text in the SQL query with another.
	 * 
	 * @param string $old The old text (escaped)
	 * @param string $new The new text (escaped)
	 */
	public function replaceText($old, $new) {
		$this->replacementsOld[] = $old;
		$this->replacementsNew[] = $new;
	}

	public function getFilter() {
		Deprecation::notice('3.0', 'Please use itemized filters in getWhere() instead of getFilter()');
		return DB::getConn()->sqlWhereToString($this->getWhere(), $this->getConnective());
	}

	/**
	 * Return a list of FROM clauses used internally.
	 * @return array
	 */
	public function getFrom() {
		return $this->from;
	}

	/**
	 * Return a list of HAVING clauses used internally.
	 * @return array
	 */
	public function getHaving() {
		return $this->having;
	}

	/**
	 * Return a list of GROUP BY clauses used internally.
	 * @return array
	 */
	public function getGroupBy() {
		return $this->groupby;
	}

	/**
	 * Return a list of WHERE clauses used internally.
	 * @return array
	 */
	public function getWhere() {
		return $this->where;
	}

	/**
	 * Return an itemised select list as a map, where keys are the aliases, and values are the column sources.
	 * Aliases will always be provided (if the alias is implicit, the alias value will be inferred), and won't be
	 * quoted.
	 * E.g., 'Title' => '"SiteTree"."Title"'.
	 */
	public function getSelect() {
		return $this->select;
	}

	/**
	 * Generate the SQL statement for this query.
	 * 
	 * @return string
	 */
	public function sql() {
		// TODO: Don't require this internal-state manipulate-and-preserve - let sqlQueryToString() handle the new
		// syntax
		$origFrom = $this->from;
		// Sort the joins
		$this->from = $this->getOrderedJoins($this->from);
		// Build from clauses
		foreach($this->from as $alias => $join) {
			// $join can be something like this array structure
			// array('type' => 'inner', 'table' => 'SiteTree', 'filter' => array("SiteTree.ID = 1", 
			// "Status = 'approved'", 'order' => 20))
			if(is_array($join)) {
				if(is_string($join['filter'])) $filter = $join['filter'];
				else if(sizeof($join['filter']) == 1) $filter = $join['filter'][0];
				else $filter = "(" . implode(") AND (", $join['filter']) . ")";

				// Ensure tables are quoted, unless the table is actually a sub-select
				$table = preg_match('/\bSELECT\b/i', $join['table'])
					? $join['table']
					: "\"{$join['table']}\"";
				$aliasClause = ($alias != $join['table'])
						? " AS \"{$alias}\""
						: "";
				$this->from[$alias] = strtoupper($join['type']) . " JOIN " 
					. $table . "$aliasClause ON $filter";
			}
		}

		$sql = DB::getConn()->sqlQueryToString($this);
		
		if($this->replacementsOld) {
			$sql = str_replace($this->replacementsOld, $this->replacementsNew, $sql);
		}

		$this->from = $origFrom;

		// The query was most likely just created and then exectued.
		if(trim($sql) === 'SELECT * FROM') {
			return '';
		}
		return $sql;
	}
	
	/**
	 * Return the generated SQL string for this query
	 * 
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->sql();
		} catch(Exception $e) {
			return "<sql query>";
		}
	}
	
	/**
	 * Execute this query.
	 * @return SS_Query
	 */
	public function execute() {
		return DB::query($this->sql(), E_USER_ERROR);
	}
	
	/**
	 * Checks whether this query is for a specific ID in a table
	 * 
	 * @todo Doesn't work with combined statements (e.g. "Foo='bar' AND ID=5")
	 *
	 * @return boolean
	 */
	public function filtersOnID() {
		$regexp = '/^(.*\.)?("|`)?ID("|`)?\s?=/';
		
		// Sometimes the ID filter will be the 2nd element, if there's a ClasssName filter first.
		if(isset($this->where[0]) && preg_match($regexp, $this->where[0])) return true;
		if(isset($this->where[1]) && preg_match($regexp, $this->where[1])) return true;
		
		return  false;
	}
	
	/**
	 * Checks whether this query is filtering on a foreign key, ie finding a has_many relationship
	 * 
	 * @todo Doesn't work with combined statements (e.g. "Foo='bar' AND ParentID=5")
	 *
	 * @return boolean
	 */
	public function filtersOnFK() {
		return (
			$this->where
			&& preg_match('/^(.*\.)?("|`)?[a-zA-Z]+ID("|`)?\s?=/', $this->where[0])
		);
	}
	
	/// VARIOUS TRANSFORMATIONS BELOW
	
	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets. 
	 * @return int 
	 */ 
	public function unlimitedRowCount($column = null) {
		// we can't clear the select if we're relying on its output by a HAVING clause
		if(count($this->having)) {
			$records = $this->execute();
			return $records->numRecords();
		}

		$clone = clone $this;
		$clone->limit = null;
		$clone->orderby = null;

		// Choose a default column
		if($column == null) {
			if($this->groupby) {
				$countQuery = new SQLQuery();
				$countQuery->setSelect("count(*)");
				$countQuery->setFrom('(' . $clone->sql() . ') all_distinct');

				return $countQuery->execute()->value();

			} else {
				$clone->setSelect(array("count(*)"));
			}
		} else {
			$clone->setSelect(array("count($column)"));
		}

		$clone->setGroupBy(array());;
		return $clone->execute()->value();
	}

	/**
	 * Returns true if this query can be sorted by the given field.
	 */
	public function canSortBy($fieldName) {
		$fieldName = preg_replace('/(\s+?)(A|DE)SC$/', '', $fieldName);
		
		return isset($this->select[$fieldName]);
	}


	/**
	 * Return the number of rows in this query if the limit were removed.  Useful in paged data sets.
	 * 
	 * @todo Respect HAVING and GROUPBY, which can affect the result-count
	 * 
	 * @param String $column Quoted, escaped column name
	 * @return int
	 */
	public function count( $column = null) {
		// we can't clear the select if we're relying on its output by a HAVING clause
		if(!empty($this->having)) {
			$records = $this->execute();
			return $records->numRecords();
		}
		// Choose a default column
		elseif($column == null) {
			if($this->groupby) {
				$column = 'DISTINCT ' . implode(", ", $this->groupby);
			} else {
				$column = '*';
			}
		}

		$clone = clone $this;
		$clone->select = array("count($column)");
		$clone->limit = null;
		$clone->orderby = null;
		$clone->groupby = null;
		
		$count = $clone->execute()->value();
		// If there's a limit set, then that limit is going to heavily affect the count
		if($this->limit) {
			if($count >= ($this->limit['start'] + $this->limit['limit']))
				return $this->limit['limit'];
			else
				return max(0, $count - $this->limit['start']);
			
		// Otherwise, the count is going to be the output of the SQL query
		} else {
			return $count;
		}
	}

	/**
	 * Return a new SQLQuery that calls the given aggregate functions on this data.
	 * 
	 * @param $column An aggregate expression, such as 'MAX("Balance")', or a set of them (as an escaped SQL statement)
	 * @param $alias An optional alias for the aggregate column.
	 */
	public function aggregate($column, $alias = null) {
		$clone = clone $this;

		// don't set an ORDER BY clause if no limit has been set. It doesn't make
		// sense to add an ORDER BY if there is no limit, and it will break
		// queries to databases like MSSQL if you do so. Note that the reason
		// this came up is because DataQuery::initialiseQuery() introduces
		// a default sort.
		if($this->limit) {
			$clone->setLimit($this->limit);
			$clone->setOrderBy($this->orderby);
		} else {
			$clone->setOrderBy(array());
		}

		$clone->setGroupBy($this->groupby);
		if($alias) {
			$clone->setSelect(array());
			$clone->selectField($column, $alias);
		} else {
			$clone->setSelect($column);
		}

		return $clone;
	}
	
	/**
	 * Returns a query that returns only the first row of this query
	 */
	public function firstRow() {
		$query = clone $this;
		$offset = $this->limit ? $this->limit['start'] : 0;
		$query->setLimit(1, $offset);
		return $query;
	}

	/**
	 * Returns a query that returns only the last row of this query
	 */
	public function lastRow() {
		$query = clone $this;
		$offset = $this->limit ? $this->limit['start'] : 0;
		
		// Limit index to start in case of empty results
		$index = max($this->count() + $offset - 1, 0);
		$query->setLimit(1, $index);
		return $query;
	}
	
	/**
	 * Ensure that framework "auto-generated" table JOINs are first in the finalised SQL query.
	 * This prevents issues where developer-initiated JOINs attempt to JOIN using relations that haven't actually 
	 * yet been scaffolded by the framework. Demonstrated by PostGres in errors like: 
	 *"...ERROR: missing FROM-clause..."
	 * 
	 * @param $from array - in the format of $this->select
	 * @return array - and reorderded list of selects
	 */
	protected function getOrderedJoins($from) {
		// shift the first FROM table out from so we only deal with the JOINs
		$baseFrom = array_shift($from);
		$this->mergesort($from, function($firstJoin, $secondJoin) {
			if(
				!is_array($firstJoin) 
				|| !is_array($secondJoin)
				|| $firstJoin['order'] == $secondJoin['order']
			) {
				return 0;
			} else {
				return ($firstJoin['order'] < $secondJoin['order']) ?  -1 : 1;
			}
		});
		
		// Put the first FROM table back into the results 
		array_unshift($from, $baseFrom);
		return $from;
	}

	/**
	 * Since uasort don't preserve the order of an array if the comparison is equal
	 * we have to resort to a merge sort. It's quick and stable: O(n*log(n)).
	 *
	 * @see http://stackoverflow.com/q/4353739/139301
	 * 
	 * @param array &$array - the array to sort
	 * @param callable $cmpFunction - the function to use for comparison
	 */ 
	protected function mergesort(&$array, $cmpFunction = 'strcmp') {
		// Arrays of size < 2 require no action.
		if (count($array) < 2) {
			return;
		}
		// Split the array in half
		$halfway = count($array) / 2;
		$array1 = array_slice($array, 0, $halfway);
		$array2 = array_slice($array, $halfway);
		// Recurse to sort the two halves
		$this->mergesort($array1, $cmpFunction);
		$this->mergesort($array2, $cmpFunction);
		// If all of $array1 is <= all of $array2, just append them.
		if(call_user_func($cmpFunction, end($array1), reset($array2)) < 1) {
			$array = array_merge($array1, $array2);
			return;
		}
		// Merge the two sorted arrays into a single sorted array
		$array = array();
		$val1 = reset($array1);
		$val2 = reset($array2);
		do {
			if (call_user_func($cmpFunction, $val1, $val2) < 1) {
				$array[key($array1)] = $val1;
				$val1 = next($array1);
			} else {
				$array[key($array2)] = $val2;
				$val2 = next($array2);
			}
		} while($val1 && $val2);
		
		// Merge the remainder
		while($val1) {
			$array[key($array1)] = $val1;
			$val1 = next($array1);
		}
		while($val2) {
			$array[key($array2)] = $val2;
			$val2 = next($array2);
		}
		return;
	}
}

